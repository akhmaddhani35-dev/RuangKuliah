<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\MataKuliah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class JadwalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $schedules = Jadwal::visibleTo($request->user())
            ->leftJoin('mata_kuliahs as course', 'jadwals.id_mata_kuliah', '=', 'course.id')
            ->select('jadwals.id', 'jadwals.id_mata_kuliah', 'jadwals.nama_mata_kuliah', 'jadwals.hari', 'jadwals.waktu_mulai', 'jadwals.waktu_selesai', 'jadwals.ruang', 'jadwals.dosen', 'jadwals.mode', 'jadwals.link_zoom', 'course.nama as course_name')
            ->orderBy('jadwals.hari')
            ->orderBy('jadwals.waktu_mulai')
            ->get();

        return response()->json(['data' => $schedules->map(fn (Jadwal $schedule) => $this->present($schedule))]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        $validated = $this->validated($request);
        $course = MataKuliah::visibleTo($request->user())->findOrFail($validated['id_mata_kuliah']);

        $schedule = Jadwal::create([
            ...$validated,
            'nama_mata_kuliah' => $course->nama,
            'user_id' => $request->user()->id,
        ]);
        $schedule->setAttribute('course_name', $course->nama);

        return response()->json(['data' => $this->present($schedule)], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $schedule = Jadwal::visibleTo($request->user())
            ->leftJoin('mata_kuliahs as course', 'jadwals.id_mata_kuliah', '=', 'course.id')
            ->select('jadwals.id', 'jadwals.id_mata_kuliah', 'jadwals.nama_mata_kuliah', 'jadwals.hari', 'jadwals.waktu_mulai', 'jadwals.waktu_selesai', 'jadwals.ruang', 'jadwals.dosen', 'jadwals.mode', 'jadwals.link_zoom', 'course.nama as course_name')
            ->findOrFail($id);

        return response()->json(['data' => $this->present($schedule)]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $this->requireAdmin($request);
        $schedule = Jadwal::manageableBy($request->user())->findOrFail($id);
        $validated = $this->validated($request);
        $course = MataKuliah::visibleTo($request->user())->findOrFail($validated['id_mata_kuliah']);
        $schedule->update([
            ...$validated,
            'nama_mata_kuliah' => $course->nama,
        ]);
        $schedule->refresh()->setAttribute('course_name', $course->nama);

        return response()->json(['data' => $this->present($schedule)]);
    }

    public function destroy(Request $request, string $id): Response
    {
        $this->requireAdmin($request);
        Jadwal::manageableBy($request->user())->findOrFail($id)->delete();

        return response()->noContent();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'id_mata_kuliah' => [
                'required',
                Rule::in(MataKuliah::visibleTo($request->user())->pluck('id')->all()),
            ],
            'hari' => ['required', Rule::in(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'])],
            'waktu_mulai' => ['required', 'date_format:H:i'],
            'waktu_selesai' => ['required', 'date_format:H:i', 'after:waktu_mulai'],
            'ruang' => ['required', 'string', 'max:255'],
            'dosen' => ['required', 'string', 'max:255'],
            'mode' => ['required', Rule::in(['Offline', 'Online'])],
            'link_zoom' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function requireAdmin(Request $request): void
    {
        abort_unless($request->user()->role === 'admin', 403);
    }

    private function present(Jadwal $schedule): array
    {
        return [
            'id' => $schedule->id,
            'id_mata_kuliah' => $schedule->id_mata_kuliah,
            'nama_mata_kuliah' => $schedule->nama_mata_kuliah,
            'course_name' => $schedule->course_name ?? $schedule->nama_mata_kuliah,
            'hari' => $schedule->hari,
            'waktu_mulai' => $schedule->waktu_mulai,
            'waktu_selesai' => $schedule->waktu_selesai,
            'ruang' => $schedule->ruang,
            'dosen' => $schedule->dosen,
            'mode' => $schedule->mode,
            'link_zoom' => $schedule->link_zoom,
        ];
    }
}
