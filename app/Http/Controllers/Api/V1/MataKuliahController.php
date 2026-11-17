<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AgendaUjian;
use App\Models\MataKuliah;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MataKuliahController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $courses = MataKuliah::visibleTo($request->user())
            ->orderBy('nama')
            ->simplePaginate(max(1, min($request->integer('per_page', 12), 100)));
        $courses->through(fn (MataKuliah $course) => $this->present($course, $request->user()));

        return response()->json($courses);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->role === 'admin', 403);

        $course = MataKuliah::create([
            ...$this->validated($request),
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['data' => $this->present($course, $request->user())], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $course = MataKuliah::visibleTo($request->user())->findOrFail($id);

        return response()->json(['data' => $this->present($course, $request->user())]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $course = MataKuliah::manageableBy($request->user())->findOrFail($id);
        $course->update($this->validated($request));

        return response()->json(['data' => $this->present($course->refresh(), $request->user())]);
    }

    public function destroy(Request $request, string $id): Response
    {
        $course = MataKuliah::manageableBy($request->user())->findOrFail($id);
        $isUsed = $course->jadwals()->exists()
            || $course->materis()->exists()
            || $course->tugas()->exists()
            || AgendaUjian::query()->where('id_mata_kuliah', $course->id)->exists();

        if ($isUsed) {
            abort(409, 'Mata kuliah tidak dapat dihapus selama masih digunakan oleh jadwal, materi, tugas, atau ujian.');
        }

        $course->delete();

        return response()->noContent();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kode_mk' => ['required', 'string', 'max:255'],
            'dosen' => ['required', 'string', 'max:255'],
            'sks' => ['required', 'integer', 'min:1'],
        ]);
    }

    private function present(MataKuliah $course, User $user): array
    {
        return [
            'id' => $course->id,
            'nama' => $course->nama,
            'kode_mk' => $course->kode_mk,
            'dosen' => $course->dosen,
            'sks' => $course->sks,
            'can_manage' => $user->role === 'admin' || (int) $course->user_id === (int) $user->id,
            'created_at' => $course->created_at,
            'updated_at' => $course->updated_at,
        ];
    }
}
