<?php

use App\Models\AgendaUjian;
use App\Models\Jadwal;
use App\Models\MataKuliah;
use App\Models\Materi;
use App\Models\Tugas;
use App\Models\TugasStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('requires an authenticated verified user for the JSON API', function () {
    $this->getJson('/api/v1/dashboard')->assertUnauthorized();
    $this->get('/app')->assertRedirect('/login');

    $user = User::factory()->create(['role' => 'user']);
    $this->actingAs($user)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('data.role', 'user');

    $this->actingAs($user)
        ->get('/app/tugas')
        ->assertOk()
        ->assertSee('academic-app');
});

it('returns all dashboard sections from the combined dashboard query', function () {
    $user = User::factory()->create();
    $course = MataKuliah::create([
        'user_id' => $user->id,
        'nama' => 'Pemrograman Dashboard',
        'kode_mk' => 'DASH101',
        'dosen' => 'Dosen Dashboard',
        'sks' => 3,
    ]);
    $weekday = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ][today()->dayOfWeekIso];

    Jadwal::create([
        'user_id' => $user->id,
        'id_mata_kuliah' => $course->id,
        'nama_mata_kuliah' => $course->nama,
        'hari' => $weekday,
        'waktu_mulai' => '08:00',
        'waktu_selesai' => '10:00',
        'ruang' => 'A101',
        'dosen' => 'Dosen Dashboard',
        'mode' => 'Offline',
    ]);
    Tugas::create([
        'user_id' => $user->id,
        'id_mata_kuliah' => $course->id,
        'nama_mata_kuliah' => $course->nama,
        'judul' => 'Tugas Dashboard',
        'deskripsi' => 'Deskripsi tugas.',
        'deadline' => today()->addDay(),
        'prioritas' => 'Tinggi',
    ]);
    Materi::create([
        'user_id' => $user->id,
        'id_mata_kuliah' => $course->id,
        'nama_mata_kuliah' => $course->nama,
        'judul' => 'Materi Dashboard',
        'pertemuan' => '1',
        'tipe' => 'Link',
        'detail' => 'https://example.test/materi',
    ]);
    AgendaUjian::create([
        'user_id' => $user->id,
        'id_mata_kuliah' => $course->id,
        'jenis' => 'UTS',
        'tanggal' => today()->addDays(2),
        'waktu_mulai' => '09:00',
        'waktu_selesai' => '11:00',
        'mode' => 'Offline',
    ]);

    $dashboardQueries = [];
    DB::listen(function ($query) use (&$dashboardQueries) {
        $dashboardQueries[] = $query->sql;
    });

    $response = $this->actingAs($user)->getJson('/api/v1/dashboard');

    expect($dashboardQueries)->toHaveCount(1);
    expect(collect($dashboardQueries)->filter(fn ($query) => str_contains(strtolower($query), 'union all')))->toHaveCount(1);

    $response
        ->assertOk()
        ->assertJsonPath('data.jadwal_hari_ini.0.course_name', $course->nama)
        ->assertJsonPath('data.tugas_mendekat.0.judul', 'Tugas Dashboard')
        ->assertJsonPath('data.tugas_mendekat.0.is_admin_shared', false)
        ->assertJsonPath('data.materi_terbaru.0.judul', 'Materi Dashboard')
        ->assertJsonPath('data.materi_terbaru.0.is_admin_shared', false)
        ->assertJsonPath('data.ujian_mendatang.0.jenis', 'UTS');
});

it('filters materials and assignments by search, course, and their relevant dates', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create(['role' => 'user']);
    $otherStudent = User::factory()->create(['role' => 'user']);
    $webCourse = MataKuliah::create([
        'user_id' => $admin->id,
        'nama' => 'Pemrograman Web',
        'kode_mk' => 'WEB101',
        'dosen' => 'Dosen Web',
        'sks' => 3,
    ]);
    $databaseCourse = MataKuliah::create([
        'user_id' => $admin->id,
        'nama' => 'Basis Data',
        'kode_mk' => 'DB101',
        'dosen' => 'Dosen Database',
        'sks' => 3,
    ]);

    $matchingMaterial = Materi::create([
        'user_id' => $admin->id,
        'id_mata_kuliah' => $webCourse->id,
        'nama_mata_kuliah' => $webCourse->nama,
        'judul' => 'Dasar Jaringan',
        'pertemuan' => '1',
        'tipe' => 'Link',
        'detail' => 'Catatan kelas',
    ]);
    $matchingMaterial->forceFill(['created_at' => today()->toDateTimeString()])->save();
    $oldMaterial = Materi::create([
        'user_id' => $admin->id,
        'id_mata_kuliah' => $webCourse->id,
        'nama_mata_kuliah' => $webCourse->nama,
        'judul' => 'Jaringan Lanjutan',
        'pertemuan' => '2',
        'tipe' => 'Link',
        'detail' => 'https://example.test/jaringan',
    ]);
    $oldMaterial->forceFill(['created_at' => today()->subDays(2)->toDateTimeString()])->save();
    Materi::create([
        'user_id' => $admin->id,
        'id_mata_kuliah' => $databaseCourse->id,
        'nama_mata_kuliah' => $databaseCourse->nama,
        'judul' => 'Modul Basis Data',
        'pertemuan' => '1',
        'tipe' => 'File',
        'detail' => 'Modul.pdf',
    ]);
    Materi::create([
        'user_id' => $otherStudent->id,
        'id_mata_kuliah' => $webCourse->id,
        'nama_mata_kuliah' => $webCourse->nama,
        'judul' => 'Jaringan Milik Mahasiswa Lain',
        'pertemuan' => '3',
        'tipe' => 'Link',
        'detail' => 'Catatan sendiri',
    ]);

    Tugas::create([
        'user_id' => $admin->id,
        'id_mata_kuliah' => $webCourse->id,
        'nama_mata_kuliah' => $webCourse->nama,
        'judul' => 'Latihan Jaringan',
        'deskripsi' => 'Praktik konfigurasi.',
        'deadline' => today()->addDays(3)->toDateString(),
        'prioritas' => 'Sedang',
    ]);
    Tugas::create([
        'user_id' => $admin->id,
        'id_mata_kuliah' => $databaseCourse->id,
        'nama_mata_kuliah' => $databaseCourse->nama,
        'judul' => 'Latihan Jaringan Basis Data',
        'deskripsi' => 'Tugas mata kuliah lain.',
        'deadline' => today()->addDays(3)->toDateString(),
        'prioritas' => 'Sedang',
    ]);
    Tugas::create([
        'user_id' => $admin->id,
        'id_mata_kuliah' => $webCourse->id,
        'nama_mata_kuliah' => $webCourse->nama,
        'judul' => 'Latihan Jaringan Mendatang',
        'deskripsi' => 'Deadline di luar rentang.',
        'deadline' => today()->addDays(10)->toDateString(),
        'prioritas' => 'Rendah',
    ]);
    Tugas::create([
        'user_id' => $otherStudent->id,
        'id_mata_kuliah' => $webCourse->id,
        'nama_mata_kuliah' => $webCourse->nama,
        'judul' => 'Latihan Jaringan Pribadi',
        'deskripsi' => 'Tidak boleh terlihat.',
        'deadline' => today()->addDays(3)->toDateString(),
        'prioritas' => 'Tinggi',
    ]);
    $personalAssignment = Tugas::create([
        'user_id' => $student->id,
        'id_mata_kuliah' => $webCourse->id,
        'nama_mata_kuliah' => $webCourse->nama,
        'judul' => 'Tugas Pribadi Mahasiswa',
        'deskripsi' => 'Dikerjakan sendiri.',
        'deadline' => today()->addDays(3)->toDateString(),
        'prioritas' => 'Sedang',
    ]);
    TugasStatus::create([
        'tugas_id' => $personalAssignment->id,
        'user_id' => $student->id,
        'status' => 'Selesai',
    ]);

    $this->actingAs($student)
        ->getJson('/api/v1/materis?search=JARINGAN&course_id='.$webCourse->id.'&date_from='.today()->toDateString())
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.judul', 'Dasar Jaringan');

    $this->getJson('/api/v1/tugas?search=jaringan&course_id='.$webCourse->id.'&date_from='.today()->addDays(1)->toDateString().'&date_to='.today()->addDays(5)->toDateString())
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.judul', 'Latihan Jaringan');

    $this->getJson('/api/v1/tugas?source=personal')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.judul', 'Tugas Pribadi Mahasiswa')
        ->assertJsonPath('data.0.is_admin_shared', false);

    $this->getJson('/api/v1/tugas?source=shared')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.is_admin_shared', true);

    $this->getJson('/api/v1/tugas?status=Selesai')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.judul', 'Tugas Pribadi Mahasiswa')
        ->assertJsonPath('data.0.my_status', 'Selesai');

    $this->getJson('/api/v1/tugas?status=Belum%20dikerjakan')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.my_status', 'Belum dikerjakan');

    $this->getJson('/api/v1/materis?type=File')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.judul', 'Modul Basis Data');

    $this->getJson('/api/v1/materis?type=invalid')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);

    $this->getJson('/api/v1/materis?course_id=999999')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['course_id']);

    $this->actingAs($admin)
        ->getJson('/api/v1/tugas?source=personal')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['source']);

    $this->getJson('/api/v1/tugas?status=Selesai')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);
});

it('serves shared academic resources as JSON and keeps management admin-only where required', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create(['role' => 'user']);
    $this->actingAs($admin);

    $courseResponse = $this->postJson('/api/v1/mata-kuliahs', [
        'nama' => 'Pemrograman API',
        'kode_mk' => 'API101',
        'dosen' => 'Dosen API',
        'sks' => 3,
    ])->assertCreated()
        ->assertJsonPath('data.nama', 'Pemrograman API');
    $courseId = $courseResponse->json('data.id');

    $scheduleResponse = $this->postJson('/api/v1/jadwals', [
        'id_mata_kuliah' => $courseId,
        'hari' => 'Senin',
        'waktu_mulai' => '08:00',
        'waktu_selesai' => '10:00',
        'ruang' => 'A101',
        'dosen' => 'Dosen API',
        'mode' => 'Offline',
    ])->assertCreated();
    $scheduleId = $scheduleResponse->json('data.id');

    $examResponse = $this->postJson('/api/v1/agenda-ujians', [
        'id_mata_kuliah' => $courseId,
        'jenis' => 'UTS',
        'tanggal' => now()->addDays(10)->toDateString(),
        'waktu_mulai' => '09:00',
        'waktu_selesai' => '11:00',
        'mode' => 'Offline',
    ])->assertCreated();
    $examId = $examResponse->json('data.id');

    $taskResponse = $this->postJson('/api/v1/tugas', [
        'id_mata_kuliah' => $courseId,
        'judul' => 'Tugas API',
        'deskripsi' => 'Dibagikan untuk kelas.',
        'deadline' => now()->addDays(2)->toDateString(),
        'prioritas' => 'Tinggi',
    ])->assertCreated();
    $taskId = $taskResponse->json('data.id');
    $this->postJson('/api/v1/materis', [
        'id_mata_kuliah' => $courseId,
        'judul' => 'Materi API',
        'pertemuan' => '1',
        'tipe' => 'Link',
        'detail' => 'https://example.test/materi',
    ])->assertCreated();

    $this->actingAs($student)
        ->getJson('/api/v1/mata-kuliahs')
        ->assertOk()
        ->assertJsonFragment(['nama' => 'Pemrograman API']);

    $this->getJson('/api/v1/jadwals')->assertOk()->assertJsonFragment(['ruang' => 'A101']);
    $this->getJson('/api/v1/agenda-ujians')->assertOk()->assertJsonFragment(['jenis' => 'UTS']);
    $this->getJson('/api/v1/tugas')
        ->assertOk()
        ->assertJsonFragment(['judul' => 'Tugas API'])
        ->assertJsonPath('data.0.my_status', 'Belum dikerjakan')
        ->assertJsonPath('data.0.is_admin_shared', true);
    $this->getJson('/api/v1/materis')
        ->assertOk()
        ->assertJsonPath('data.0.is_admin_shared', true);

    $this->postJson('/api/v1/jadwals', [])->assertForbidden();
    $this->postJson('/api/v1/agenda-ujians', [])->assertForbidden();
    $this->patchJson("/api/v1/tugas/{$taskId}/status", ['status' => 'Selesai'])->assertOk();
    $this->actingAs($admin)
        ->patchJson("/api/v1/tugas/{$taskId}/status", ['status' => 'Selesai'])
        ->assertForbidden();

    $this->patchJson("/api/v1/jadwals/{$scheduleId}", [
        'id_mata_kuliah' => $courseId,
        'hari' => 'Selasa',
        'waktu_mulai' => '09:00',
        'waktu_selesai' => '11:00',
        'ruang' => 'A102',
        'dosen' => 'Dosen API',
        'mode' => 'Offline',
    ])->assertOk()->assertJsonPath('data.hari', 'Selasa');

    $this->patchJson("/api/v1/agenda-ujians/{$examId}", [
        'id_mata_kuliah' => $courseId,
        'jenis' => 'UAS',
        'tanggal' => now()->addDays(15)->toDateString(),
        'waktu_mulai' => '09:00',
        'waktu_selesai' => '11:00',
        'mode' => 'Offline',
    ])->assertOk()->assertJsonPath('data.jenis', 'UAS');

    $this->deleteJson("/api/v1/jadwals/{$scheduleId}")->assertNoContent();
    $this->deleteJson("/api/v1/agenda-ujians/{$examId}")->assertNoContent();
});

it('rejects invalid API payloads and exposes the React app behind web authentication', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)
        ->postJson('/api/v1/mata-kuliahs', ['nama' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nama', 'kode_mk', 'dosen', 'sks']);

    $this->get('/app/dashboard')->assertOk()->assertSee('academic-app');
});

it('keeps API material attachments private and downloadable only by visible users', function () {
    Storage::fake('local');
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $course = MataKuliah::create([
        'user_id' => $owner->id,
        'nama' => 'Materi API',
        'kode_mk' => 'MATAPI',
        'dosen' => 'Dosen API',
        'sks' => 2,
    ]);

    $response = $this->actingAs($owner)->post('/api/v1/materis', [
        'id_mata_kuliah' => $course->id,
        'judul' => 'PDF API',
        'pertemuan' => '1',
        'tipe' => 'File',
        'file' => UploadedFile::fake()->createWithContent('materi.pdf', 'isi pdf'),
    ])->assertCreated()
        ->assertJsonMissingPath('data.file_path')
        ->assertJsonPath('data.is_admin_shared', false);
    $material = Materi::findOrFail($response->json('data.id'));

    $this->get("/api/v1/materis/{$material->id}/download")->assertOk();
    Storage::disk('local')->assertExists($material->file_path);
    $this->actingAs($otherUser)
        ->getJson("/api/v1/materis/{$material->id}/download")
        ->assertNotFound();
});

it('returns credentialed CORS headers for configured SPA origins', function () {
    $this->withHeaders([
        'Origin' => 'http://localhost',
        'Access-Control-Request-Method' => 'GET',
    ])->options('/api/v1/dashboard')
        ->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', 'http://localhost')
        ->assertHeader('Access-Control-Allow-Credentials', 'true');
});

it('allows admins to add courses while students manage their own materials and assignments', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create(['role' => 'user']);

    $courseResponse = $this->actingAs($admin)->postJson('/api/v1/mata-kuliahs', [
        'nama' => 'Basis Data',
        'kode_mk' => 'BD101',
        'dosen' => 'Dosen Basis Data',
        'sks' => 3,
    ])->assertCreated();
    $courseId = $courseResponse->json('data.id');

    $this->actingAs($student)
        ->getJson("/api/v1/mata-kuliahs/{$courseId}")
        ->assertOk()
        ->assertJsonPath('data.nama', 'Basis Data');

    $materialResponse = $this->postJson('/api/v1/materis', [
        'id_mata_kuliah' => $courseId,
        'judul' => 'Materi Relasi',
        'pertemuan' => '1',
        'tipe' => 'Link',
        'detail' => 'https://example.test/relasi',
    ])->assertCreated()->assertJsonPath('data.is_admin_shared', false);
    $materialId = $materialResponse->json('data.id');
    $this->getJson("/api/v1/materis/{$materialId}")->assertOk();
    $this->patchJson("/api/v1/materis/{$materialId}", [
        'id_mata_kuliah' => $courseId,
        'judul' => 'Materi Relasi Baru',
        'pertemuan' => '2',
        'tipe' => 'Link',
        'detail' => 'https://example.test/relasi-baru',
    ])->assertOk()->assertJsonPath('data.judul', 'Materi Relasi Baru');

    $taskResponse = $this->postJson('/api/v1/tugas', [
        'id_mata_kuliah' => $courseId,
        'judul' => 'Latihan SQL',
        'deskripsi' => 'Buat query.',
        'deadline' => now()->addDays(5)->toDateString(),
        'prioritas' => 'Sedang',
    ])->assertCreated()->assertJsonPath('data.is_admin_shared', false);
    $taskId = $taskResponse->json('data.id');
    $this->getJson("/api/v1/tugas/{$taskId}")->assertOk();
    $this->patchJson("/api/v1/tugas/{$taskId}", [
        'id_mata_kuliah' => $courseId,
        'judul' => 'Latihan SQL Baru',
        'deskripsi' => 'Buat query select dan join.',
        'deadline' => now()->addDays(6)->toDateString(),
        'prioritas' => 'Tinggi',
    ])->assertOk()->assertJsonPath('data.judul', 'Latihan SQL Baru');

    $this->actingAs($admin)->deleteJson("/api/v1/mata-kuliahs/{$courseId}")->assertStatus(409);
    $this->actingAs($student)->deleteJson("/api/v1/tugas/{$taskId}")->assertNoContent();
    $this->deleteJson("/api/v1/materis/{$materialId}")->assertNoContent();
    $this->actingAs($admin)->deleteJson("/api/v1/mata-kuliahs/{$courseId}")->assertNoContent();
});

it('restricts adding mata kuliah to admins in the API and legacy web routes', function () {
    $student = User::factory()->create(['role' => 'user']);
    $admin = User::factory()->create(['role' => 'admin']);
    $payload = [
        'nama' => 'Matematika Diskrit',
        'kode_mk' => 'MD101',
        'dosen' => 'Dosen Diskrit',
        'sks' => 3,
    ];

    $this->actingAs($student)
        ->postJson('/api/v1/mata-kuliahs', $payload)
        ->assertForbidden();
    $this->get(route('matakuliah.create'))->assertForbidden();
    $this->post(route('matakuliah.store'), $payload)->assertForbidden();
    $this->get(route('matakuliah.index'))->assertOk()->assertDontSee('Tambah Mata Kuliah');

    $this->actingAs($admin)
        ->postJson('/api/v1/mata-kuliahs', $payload)
        ->assertCreated();
    $this->get(route('matakuliah.index'))->assertOk()->assertSee('Tambah Mata Kuliah');
});
