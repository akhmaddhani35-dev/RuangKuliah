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
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('assigns the student role to self-registered accounts', function () {
    $this->post(route('register'), [
        'name' => 'New Student',
        'email' => 'student@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'admin',
    ])->assertRedirect(route('dashboard'));

    expect(User::where('email', 'student@example.test')->value('role'))->toBe('user');
});

it('supports CRUD for schedules, materials, and assignments', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $this->actingAs($user);

    $mataKuliah = MataKuliah::create([
        'user_id' => $user->id,
        'nama' => 'Pemrograman Web',
        'kode_mk' => 'IF101',
        'dosen' => 'Dosen A',
        'sks' => 3,
    ]);

    $this->get(route('jadwal.create'))->assertOk();
    $this->post(route('jadwal.store'), [
        'id_mata_kuliah' => $mataKuliah->id,
        'hari' => 'Senin',
        'waktu_mulai' => '08:00',
        'waktu_selesai' => '10:00',
        'ruang' => 'A101',
        'dosen' => 'Dosen A',
        'mode' => 'Offline',
        'link_zoom' => null,
    ])->assertRedirect(route('jadwal.index'));

    $jadwal = Jadwal::firstOrFail();
    expect($jadwal->nama_mata_kuliah)->toBe('Pemrograman Web');
    $this->get(route('jadwal.index'))->assertOk()->assertSee('Pemrograman Web');
    $this->get(route('jadwal.show', $jadwal))->assertOk();
    $this->get(route('jadwal.edit', $jadwal))->assertOk();
    $this->put(route('jadwal.update', $jadwal), [
        'id_mata_kuliah' => $mataKuliah->id,
        'hari' => 'Selasa',
        'waktu_mulai' => '09:00',
        'waktu_selesai' => '11:00',
        'ruang' => 'A102',
        'dosen' => 'Dosen B',
        'mode' => 'Online',
        'link_zoom' => 'https://example.test/meeting',
    ])->assertRedirect(route('jadwal.index'));
    $this->delete(route('jadwal.destroy', $jadwal))->assertRedirect(route('jadwal.index'));
    $this->assertDatabaseMissing('jadwals', ['id' => $jadwal->id]);

    $this->get(route('materi.create'))->assertOk();
    $this->post(route('materi.store'), [
        'id_mata_kuliah' => $mataKuliah->id,
        'nama_mata_kuliah' => '',
        'judul' => 'Materi Laravel',
        'pertemuan' => '1',
        'tipe' => 'Link',
        'detail' => 'https://example.test/materi',
    ])->assertRedirect(route('materi.index'));

    $materi = Materi::firstOrFail();
    expect($materi->nama_mata_kuliah)->toBe('Pemrograman Web');
    $this->get(route('materi.index'))->assertOk()->assertSee('Pemrograman Web');
    $this->get(route('materi.show', $materi))->assertOk();
    $this->get(route('materi.edit', $materi))->assertOk();
    $this->put(route('materi.update', $materi), [
        'id_mata_kuliah' => null,
        'nama_mata_kuliah' => 'Materi Umum',
        'judul' => 'Materi PHP',
        'pertemuan' => '2',
        'tipe' => 'File',
        'detail' => 'php.pdf',
    ])->assertRedirect(route('materi.index'));
    $this->assertDatabaseHas('materis', ['id' => $materi->id, 'id_mata_kuliah' => null, 'nama_mata_kuliah' => 'Materi Umum']);
    $this->delete(route('materi.destroy', $materi))->assertRedirect(route('materi.index'));
    $this->assertDatabaseMissing('materis', ['id' => $materi->id]);

    $this->get(route('tugas.create'))->assertOk();
    $this->post(route('tugas.store'), [
        'id_mata_kuliah' => $mataKuliah->id,
        'judul' => 'Tugas Laravel',
        'deskripsi' => 'Membuat aplikasi.',
        'deadline' => '2026-12-01',
        'prioritas' => 'Tinggi',
    ])->assertRedirect(route('tugas.index'));

    $tugas = Tugas::firstOrFail();
    expect($tugas->nama_mata_kuliah)->toBe('Pemrograman Web');
    $this->get(route('tugas.index'))->assertOk()->assertSee('Pemrograman Web');
    $this->get(route('tugas.show', $tugas))->assertOk();
    $this->get(route('tugas.edit', $tugas))->assertOk();
    $this->put(route('tugas.update', $tugas), [
        'id_mata_kuliah' => $mataKuliah->id,
        'judul' => 'Tugas PHP',
        'deskripsi' => 'Membuat program.',
        'deadline' => '2026-12-15',
        'prioritas' => 'Sedang',
    ])->assertRedirect(route('tugas.index'));
    $this->delete(route('tugas.destroy', $tugas))->assertRedirect(route('tugas.index'));
    $this->assertDatabaseMissing('tugas', ['id' => $tugas->id]);
});

it('prevents access to another users records and courses', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownedCourse = MataKuliah::create([
        'user_id' => $owner->id,
        'nama' => 'Mata Kuliah Pribadi',
        'kode_mk' => 'MK001',
        'dosen' => 'Dosen',
        'sks' => 2,
    ]);
    $otherCourse = MataKuliah::create([
        'user_id' => $otherUser->id,
        'nama' => 'Mata Kuliah Lain',
        'kode_mk' => 'MK002',
        'dosen' => 'Dosen',
        'sks' => 2,
    ]);
    $otherAssignment = Tugas::create([
        'user_id' => $otherUser->id,
        'id_mata_kuliah' => $otherCourse->id,
        'nama_mata_kuliah' => $otherCourse->nama,
        'judul' => 'Tugas Rahasia',
        'deskripsi' => 'Tidak boleh diakses.',
        'deadline' => '2026-12-01',
        'prioritas' => 'Tinggi',
    ]);

    $this->actingAs($owner)
        ->get(route('tugas.index'))
        ->assertOk()
        ->assertDontSee('Tugas Rahasia');

    $this->get(route('tugas.show', $otherAssignment))->assertNotFound();
    $this->put(route('tugas.update', $otherAssignment), [
        'id_mata_kuliah' => $ownedCourse->id,
        'judul' => 'Diubah',
        'deskripsi' => 'Tidak boleh diubah.',
        'deadline' => '2026-12-02',
        'prioritas' => 'Sedang',
    ])->assertNotFound();
    $this->delete(route('tugas.destroy', $otherAssignment))->assertNotFound();

    $this->post(route('tugas.store'), [
        'id_mata_kuliah' => $otherCourse->id,
        'judul' => 'Tugas Tidak Valid',
        'deskripsi' => 'Mata kuliah milik pengguna lain.',
        'deadline' => '2026-12-01',
        'prioritas' => 'Tinggi',
    ])->assertSessionHasErrors('id_mata_kuliah');
});

it('shares admin-managed records with students while keeping student records private', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create(['role' => 'user']);
    $anotherStudent = User::factory()->create(['role' => 'user']);

    $sharedCourse = MataKuliah::create([
        'user_id' => $admin->id,
        'nama' => 'Mata Kuliah Bersama',
        'kode_mk' => 'MK-SHARED',
        'dosen' => 'Dosen Admin',
        'sks' => 3,
    ]);

    $sharedSchedule = Jadwal::create([
        'user_id' => $admin->id,
        'id_mata_kuliah' => $sharedCourse->id,
        'nama_mata_kuliah' => $sharedCourse->nama,
        'hari' => 'Senin',
        'waktu_mulai' => '08:00',
        'waktu_selesai' => '10:00',
        'ruang' => 'A101',
        'dosen' => 'Dosen Admin',
        'mode' => 'Offline',
    ]);

    $sharedMaterial = Materi::create([
        'user_id' => $admin->id,
        'id_mata_kuliah' => $sharedCourse->id,
        'judul' => 'Materi Bersama',
        'nama_mata_kuliah' => $sharedCourse->nama,
        'pertemuan' => '1',
        'tipe' => 'Link',
        'detail' => 'https://example.test/shared-material',
    ]);

    $sharedAssignment = Tugas::create([
        'user_id' => $admin->id,
        'id_mata_kuliah' => $sharedCourse->id,
        'nama_mata_kuliah' => $sharedCourse->nama,
        'judul' => 'Tugas Bersama',
        'deskripsi' => 'Tugas untuk kelas.',
        'deadline' => '2026-12-01',
        'prioritas' => 'Tinggi',
    ]);

    Materi::create([
        'user_id' => $student->id,
        'id_mata_kuliah' => $sharedCourse->id,
        'nama_mata_kuliah' => $sharedCourse->nama,
        'judul' => 'Materi Diisi Sendiri',
        'pertemuan' => '2',
        'tipe' => 'Link',
        'detail' => 'https://example.test/student-material',
    ]);

    Tugas::create([
        'user_id' => $student->id,
        'id_mata_kuliah' => $sharedCourse->id,
        'nama_mata_kuliah' => $sharedCourse->nama,
        'judul' => 'Tugas Diisi Sendiri',
        'deskripsi' => 'Tugas pribadi mahasiswa.',
        'deadline' => '2026-12-10',
        'prioritas' => 'Rendah',
    ]);

    $privateCourse = MataKuliah::create([
        'user_id' => $anotherStudent->id,
        'nama' => 'Mata Kuliah Pribadi',
        'kode_mk' => 'MK-PRIVATE',
        'dosen' => 'Dosen Mahasiswa',
        'sks' => 2,
    ]);

    $privateAssignment = Tugas::create([
        'user_id' => $anotherStudent->id,
        'id_mata_kuliah' => $privateCourse->id,
        'nama_mata_kuliah' => $privateCourse->nama,
        'judul' => 'Tugas Pribadi Rahasia',
        'deskripsi' => 'Hanya untuk pemiliknya.',
        'deadline' => '2026-12-05',
        'prioritas' => 'Sedang',
    ]);

    $this->actingAs($student)
        ->get(route('matakuliah.index'))
        ->assertOk()
        ->assertSee('Mata Kuliah Bersama')
        ->assertDontSee('Mata Kuliah Pribadi')
        ->assertSee('Dibagikan Admin');
    $this->get(route('jadwal.index'))
        ->assertOk()
        ->assertSee('Mata Kuliah Bersama')
        ->assertDontSee('Tambah Jadwal');
    $this->get(route('materi.index'))
        ->assertOk()
        ->assertSee('Dari Komting')
        ->assertSee('Materi Bersama')
        ->assertSee('Pribadi')
        ->assertSee('Materi Diisi Sendiri');
    $this->get(route('tugas.index'))->assertOk()
        ->assertSee('Dari Komting')
        ->assertSee('Tugas Bersama')
        ->assertSee('Pribadi')
        ->assertSee('Tugas Diisi Sendiri')
        ->assertDontSee('Tugas Pribadi Rahasia')
        ->assertSee('Dari Komting');

    $this->get(route('matakuliah.edit', $sharedCourse))->assertNotFound();
    $this->delete(route('matakuliah.destroy', $sharedCourse))->assertNotFound();
    $this->get(route('jadwal.edit', $sharedSchedule))->assertForbidden();
    $this->delete(route('materi.destroy', $sharedMaterial))->assertNotFound();
    $this->put(route('tugas.update', $sharedAssignment), [
        'id_mata_kuliah' => $sharedCourse->id,
        'judul' => 'Perubahan Tidak Diizinkan',
        'deskripsi' => 'Tidak boleh diubah mahasiswa.',
        'deadline' => '2026-12-10',
        'prioritas' => 'Rendah',
    ])->assertNotFound();

    $this->post(route('tugas.store'), [
        'id_mata_kuliah' => $sharedCourse->id,
        'judul' => 'Tugas Pribadi Mahasiswa',
        'deskripsi' => 'Tugas yang dibuat mahasiswa menggunakan mata kuliah bersama.',
        'deadline' => '2026-12-15',
        'prioritas' => 'Rendah',
    ])->assertRedirect(route('tugas.index'));

    $this->post(route('jadwal.store'), [
        'id_mata_kuliah' => $sharedCourse->id,
        'hari' => 'Rabu',
        'waktu_mulai' => '13:00',
        'waktu_selesai' => '15:00',
        'ruang' => 'B201',
        'dosen' => 'Dosen Mahasiswa',
        'mode' => 'Offline',
    ])->assertForbidden();

    $this->post(route('materi.store'), [
        'id_mata_kuliah' => $sharedCourse->id,
        'nama_mata_kuliah' => '',
        'judul' => 'Materi Pribadi Mahasiswa',
        'pertemuan' => '2',
        'tipe' => 'File',
        'detail' => 'catatan.pdf',
    ])->assertRedirect(route('materi.index'));

    $studentAssignment = Tugas::where('user_id', $student->id)->firstOrFail();
    expect($studentAssignment->id_mata_kuliah)->toBe($sharedCourse->id);
    expect($studentAssignment->nama_mata_kuliah)->toBe($sharedCourse->nama);
    expect(Jadwal::where('user_id', $student->id)->exists())->toBeFalse();
    expect(Materi::where('user_id', $student->id)->exists())->toBeTrue();

    $this->get(route('matakuliah.edit', $sharedCourse))->assertNotFound();
    $this->actingAs($admin);

    $this->get(route('matakuliah.edit', $sharedCourse))->assertOk();
    $this->put(route('matakuliah.update', $sharedCourse), [
        'nama' => 'Mata Kuliah Diperbarui',
        'kode_mk' => 'MK-SHARED',
        'dosen' => 'Dosen Admin',
        'sks' => 3,
    ])->assertRedirect(route('matakuliah.index'));
    $this->get(route('materi.edit', $sharedMaterial))->assertOk();
    $this->put(route('materi.update', $sharedMaterial), [
        'id_mata_kuliah' => $sharedCourse->id,
        'nama_mata_kuliah' => '',
        'judul' => 'Materi Bersama Diperbarui',
        'pertemuan' => '1',
        'tipe' => 'Link',
        'detail' => 'https://example.test/updated-material',
    ])->assertRedirect(route('materi.index'));
    $this->put(route('tugas.update', $sharedAssignment), [
        'id_mata_kuliah' => $sharedCourse->id,
        'judul' => 'Tugas Bersama Diperbarui',
        'deskripsi' => 'Diperbarui oleh admin.',
        'deadline' => '2026-12-10',
        'prioritas' => 'Sedang',
    ])->assertRedirect(route('tugas.index'));

    $this->get(route('jadwal.index'))
        ->assertOk()
        ->assertSee('Mata Kuliah Diperbarui')
        ->assertDontSee('B201');
    $this->get(route('materi.index'))
        ->assertOk()
        ->assertSee('Materi Bersama Diperbarui')
        ->assertDontSee('Dari Komting')
        ->assertDontSee('Pribadi</h3>')
        ->assertDontSee('Materi Pribadi Mahasiswa');
    $this->get(route('tugas.index'))
        ->assertOk()
        ->assertSee('Tugas Bersama Diperbarui')
        ->assertDontSee('Dari Komting')
        ->assertDontSee('Pribadi</h3>')
        ->assertDontSee('Tugas Pribadi Mahasiswa')
        ->assertDontSee('Tugas Pribadi Rahasia');

    $this->put(route('jadwal.update', $sharedSchedule), [
        'id_mata_kuliah' => $sharedCourse->id,
        'hari' => 'Selasa',
        'waktu_mulai' => '09:00',
        'waktu_selesai' => '11:00',
        'ruang' => 'A102',
        'dosen' => 'Dosen Admin',
        'mode' => 'Online',
    ])->assertRedirect(route('jadwal.index'));

    $this->get(route('tugas.show', $privateAssignment))->assertNotFound();
});

it('shows a dashboard and keeps shared task progress private to each student', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create(['role' => 'user']);
    $anotherStudent = User::factory()->create(['role' => 'user']);
    $course = MataKuliah::create([
        'user_id' => $admin->id,
        'nama' => 'Mata Kuliah Dashboard',
        'kode_mk' => 'MK-DASH',
        'dosen' => 'Dosen Dashboard',
        'sks' => 3,
    ]);
    $assignment = Tugas::create([
        'user_id' => $admin->id,
        'id_mata_kuliah' => $course->id,
        'nama_mata_kuliah' => $course->nama,
        'judul' => 'Tugas Dashboard',
        'deskripsi' => 'Tugas untuk dashboard.',
        'deadline' => now()->addDay()->toDateString(),
        'prioritas' => 'Tinggi',
    ]);
    Materi::create([
        'user_id' => $admin->id,
        'id_mata_kuliah' => $course->id,
        'judul' => 'Materi Dashboard',
        'nama_mata_kuliah' => $course->nama,
        'pertemuan' => '1',
        'tipe' => 'Link',
        'detail' => 'https://example.test/dashboard',
    ]);
    Jadwal::create([
        'user_id' => $admin->id,
        'id_mata_kuliah' => $course->id,
        'nama_mata_kuliah' => $course->nama,
        'hari' => [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ][now()->dayOfWeekIso],
        'waktu_mulai' => '08:00',
        'waktu_selesai' => '10:00',
        'ruang' => 'A101',
        'dosen' => 'Dosen Dashboard',
        'mode' => 'Offline',
    ]);

    $this->actingAs($student)
        ->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('data.summary.jumlah_mata_kuliah', 1)
        ->assertJsonFragment(['judul' => 'Tugas Dashboard'])
        ->assertJsonFragment(['judul' => 'Materi Dashboard'])
        ->assertJsonFragment(['course_name' => 'Mata Kuliah Dashboard']);

    $this->patch(route('tugas.status', $assignment), ['status' => 'Selesai'])
        ->assertRedirect();
    $this->assertDatabaseHas('tugas_user_statuses', [
        'tugas_id' => $assignment->id,
        'user_id' => $student->id,
        'status' => 'Selesai',
    ]);
    expect(TugasStatus::where('tugas_id', $assignment->id)->where('user_id', $anotherStudent->id)->exists())->toBeFalse();

    $this->getJson('/api/v1/dashboard')->assertOk()->assertJsonPath('data.summary.persentase_selesai', 100);
});

it('stores and authorizes private material and assignment downloads', function () {
    Storage::fake('local');
    $owner = User::factory()->create();
    $anotherUser = User::factory()->create();
    $course = MataKuliah::create([
        'user_id' => $owner->id,
        'nama' => 'Mata Kuliah Lampiran',
        'kode_mk' => 'MK-FILE',
        'dosen' => 'Dosen File',
        'sks' => 2,
    ]);

    $this->actingAs($owner)
        ->post(route('materi.store'), [
            'id_mata_kuliah' => $course->id,
            'judul' => 'Materi PDF',
            'pertemuan' => '1',
            'tipe' => 'File',
            'file' => UploadedFile::fake()->create('materi.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect(route('materi.index'));

    $materi = Materi::firstOrFail();
    Storage::disk('local')->assertExists($materi->file_path);
    $this->get(route('materi.download', $materi))->assertOk();
    $this->actingAs($anotherUser)->get(route('materi.download', $materi))->assertNotFound();

    $this->actingAs($owner)
        ->post(route('tugas.store'), [
            'id_mata_kuliah' => $course->id,
            'judul' => 'Tugas PDF',
            'deskripsi' => 'Lampiran tugas.',
            'deadline' => now()->addWeek()->toDateString(),
            'prioritas' => 'Sedang',
            'file' => UploadedFile::fake()->create('tugas.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect(route('tugas.index'));

    $tugas = Tugas::firstOrFail();
    Storage::disk('local')->assertExists($tugas->file_path);
    $this->get(route('tugas.download', $tugas))->assertOk();
    $this->actingAs($anotherUser)->get(route('tugas.download', $tugas))->assertNotFound();
});

it('restricts weekly schedule and exam management to admins', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create(['role' => 'user']);
    $course = MataKuliah::create([
        'user_id' => $admin->id,
        'nama' => 'Mata Kuliah Ujian',
        'kode_mk' => 'MK-EXAM',
        'dosen' => 'Dosen Ujian',
        'sks' => 3,
    ]);

    $this->actingAs($student)
        ->get(route('jadwal.index'))
        ->assertOk()
        ->assertSee('Jadwal Mingguan')
        ->assertDontSee('Tambah Jadwal');
    $this->get(route('jadwal.create'))->assertForbidden();
    $this->post(route('jadwal.store'), [
        'id_mata_kuliah' => $course->id,
        'hari' => 'Senin',
        'waktu_mulai' => '08:00',
        'waktu_selesai' => '10:00',
        'ruang' => 'A101',
        'dosen' => 'Dosen Ujian',
        'mode' => 'Offline',
    ])->assertForbidden();
    $this->get(route('ujian.index'))->assertRedirect(route('jadwal.index').'#agenda-ujian');
    $this->get(route('jadwal.index'))->assertOk()->assertSee('Jadwal UTS/UAS');
    $this->get(route('ujian.create'))->assertForbidden();

    $this->actingAs($admin)
        ->post(route('jadwal.store'), [
            'id_mata_kuliah' => $course->id,
            'hari' => 'Senin',
            'waktu_mulai' => '08:00',
            'waktu_selesai' => '10:00',
            'ruang' => 'A101',
            'dosen' => 'Dosen Ujian',
            'mode' => 'Offline',
        ])
        ->assertRedirect(route('jadwal.index'));
    $weeklySchedule = Jadwal::firstOrFail();

    $this->post(route('ujian.store'), [
        'id_mata_kuliah' => $course->id,
        'jenis' => 'UTS',
        'tanggal' => now()->addDays(2)->toDateString(),
        'waktu_mulai' => '09:00',
        'waktu_selesai' => '11:00',
        'ruang' => 'A101',
        'mode' => 'Offline',
    ])->assertRedirect(route('ujian.index'));

    $exam = AgendaUjian::firstOrFail();
    expect($exam->jenis)->toBe('UTS');
    $this->actingAs($admin)->get(route('ujian.create'))->assertOk();
    $this->get(route('ujian.edit', $exam))->assertOk();
    $this->get(route('ujian.show', $exam))->assertOk()->assertSee('UTS');

    $this->actingAs($student)
        ->get(route('jadwal.edit', $weeklySchedule))
        ->assertForbidden();
    $this->put(route('jadwal.update', $weeklySchedule), [])->assertForbidden();
    $this->delete(route('jadwal.destroy', $weeklySchedule))->assertForbidden();
    $this->get(route('ujian.index'))
        ->assertRedirect(route('jadwal.index').'#agenda-ujian');
    $this->get(route('jadwal.index'))
        ->assertOk()
        ->assertSee('UTS')
        ->assertSee('Mata Kuliah Ujian');
    $this->getJson('/api/v1/dashboard')->assertOk()->assertJsonFragment(['jenis' => 'UTS']);
    $this->get(route('ujian.edit', $exam))->assertForbidden();
    $this->put(route('ujian.update', $exam), [
        'id_mata_kuliah' => $course->id,
        'jenis' => 'UAS',
        'tanggal' => '2026-12-12',
        'waktu_mulai' => '09:00',
        'waktu_selesai' => '11:00',
        'mode' => 'Offline',
    ])->assertForbidden();
    $this->delete(route('ujian.destroy', $exam))->assertForbidden();

    $this->actingAs($admin)
        ->put(route('ujian.update', $exam), [
            'id_mata_kuliah' => $course->id,
            'jenis' => 'UAS',
            'tanggal' => now()->addDays(3)->toDateString(),
            'waktu_mulai' => '09:00',
            'waktu_selesai' => '11:00',
            'mode' => 'Online',
            'link_zoom' => 'https://example.test/uas',
        ])
        ->assertRedirect(route('ujian.index'));
    $this->assertDatabaseHas('agenda_ujians', ['id' => $exam->id, 'jenis' => 'UAS']);
});

it('shows shared tasks without progress controls to admins', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create(['role' => 'user']);
    $course = MataKuliah::create([
        'user_id' => $admin->id,
        'nama' => 'Mata Kuliah Pengumuman',
        'kode_mk' => 'MK-INFO',
        'dosen' => 'Dosen Info',
        'sks' => 2,
    ]);
    $task = Tugas::create([
        'user_id' => $admin->id,
        'id_mata_kuliah' => $course->id,
        'nama_mata_kuliah' => $course->nama,
        'judul' => 'Tugas Dibagikan',
        'deskripsi' => 'Informasi tugas untuk mahasiswa.',
        'deadline' => now()->addDays(2)->toDateString(),
        'prioritas' => 'Sedang',
    ]);

    $this->actingAs($admin)
        ->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonFragment(['judul' => 'Tugas Dibagikan'])
        ->assertJsonPath('data.is_admin', true)
        ->assertJsonPath('data.summary.jumlah_tugas_belum_selesai', null);
    $this->get(route('tugas.index'))
        ->assertOk()
        ->assertDontSee('Status pribadi');
    $this->patch(route('tugas.status', $task), ['status' => 'Selesai'])->assertForbidden();

    $this->actingAs($student)
        ->get(route('tugas.index'))
        ->assertOk()
        ->assertSee('Status pribadi');
});
