<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\DeletesStoredFiles;
use App\Http\Controllers\Controller;
use App\Models\MataKuliah;
use App\Models\Materi;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MateriController extends Controller
{
    use DeletesStoredFiles;

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'course_id' => ['nullable', Rule::in(MataKuliah::visibleTo($request->user())->pluck('id')->all())],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'type' => ['nullable', Rule::in(['File', 'Link'])],
        ]);
        $materials = Materi::visibleTo($request->user())
            ->leftJoin('mata_kuliahs as course', 'materis.id_mata_kuliah', '=', 'course.id')
            ->leftJoin('users as creator', 'materis.user_id', '=', 'creator.id')
            ->select('materis.*', 'course.nama as course_name', 'creator.role as creator_role');

        if (! empty($filters['search'])) {
            $search = '%'.mb_strtolower(trim($filters['search'])).'%';
            $materials->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(materis.judul) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(materis.detail) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(materis.nama_mata_kuliah) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(course.nama) LIKE ?', [$search]);
            });
        }
        if (! empty($filters['course_id'])) {
            $materials->where('materis.id_mata_kuliah', $filters['course_id']);
        }
        if (! empty($filters['date_from'])) {
            $materials->where('materis.created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $materials->where('materis.created_at', '<', Carbon::parse($filters['date_to'])->addDay()->toDateString());
        }
        if (! empty($filters['type'])) {
            $materials->where('materis.tipe', $filters['type']);
        }

        $materials = $materials->orderByDesc('materis.created_at')->simplePaginate(12);
        $materials->through(fn (Materi $material) => $this->present($material, $request->user()));

        return response()->json($materials);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validated($request);
        $courseName = $this->courseName($request, $validated['id_mata_kuliah'] ?? null);
        unset($validated['file']);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('materi', 'local');
            abort_unless(is_string($path), 500, 'Lampiran materi gagal disimpan.');
            $validated['tipe'] = 'File';
            $validated['detail'] = $file->getClientOriginalName();
            $validated['file_path'] = $path;
            $validated['file_name'] = $file->getClientOriginalName();
        }

        $material = Materi::create([
            ...$validated,
            'nama_mata_kuliah' => $courseName,
            'user_id' => $request->user()->id,
        ]);
        $material->setAttribute('course_name', $courseName);
        $material->setAttribute('creator_role', $request->user()->role);

        return response()->json(['data' => $this->present($material, $request->user())], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $material = Materi::visibleTo($request->user())
            ->leftJoin('mata_kuliahs as course', 'materis.id_mata_kuliah', '=', 'course.id')
            ->leftJoin('users as creator', 'materis.user_id', '=', 'creator.id')
            ->select('materis.*', 'course.nama as course_name', 'creator.role as creator_role')
            ->findOrFail($id);

        return response()->json(['data' => $this->present($material, $request->user())]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $material = Materi::manageableBy($request->user())
            ->leftJoin('users as creator', 'materis.user_id', '=', 'creator.id')
            ->select('materis.*', 'creator.role as creator_role')
            ->findOrFail($id);
        $validated = $this->validated($request, true);
        $courseName = $this->courseName($request, $validated['id_mata_kuliah'] ?? null);
        unset($validated['file'], $validated['remove_file']);
        $previousFilePath = $material->file_path;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('materi', 'local');
            abort_unless(is_string($path), 500, 'Lampiran materi gagal disimpan.');
            $validated['tipe'] = 'File';
            $validated['detail'] = $file->getClientOriginalName();
            $validated['file_path'] = $path;
            $validated['file_name'] = $file->getClientOriginalName();
        } elseif ($request->boolean('remove_file')) {
            $validated['file_path'] = null;
            $validated['file_name'] = null;
        }

        $material->update([
            ...$validated,
            'nama_mata_kuliah' => $courseName,
        ]);
        if ($previousFilePath && $previousFilePath !== $material->file_path) {
            $this->deleteStoredFile($previousFilePath, 'materi');
        }
        $material->setAttribute('course_name', $courseName);

        return response()->json(['data' => $this->present($material, $request->user())]);
    }

    public function destroy(Request $request, string $id): Response
    {
        $material = Materi::manageableBy($request->user())->findOrFail($id);
        $this->deleteStoredFile($material->file_path, 'materi');
        $material->delete();

        return response()->noContent();
    }

    public function download(Request $request, string $id)
    {
        $material = Materi::visibleTo($request->user())->findOrFail($id);
        abort_unless($material->file_path && Storage::disk('local')->exists($material->file_path), 404);

        return Storage::disk('local')->download($material->file_path, $material->file_name);
    }

    private function validated(Request $request, bool $updating = false): array
    {
        return $request->validate([
            'id_mata_kuliah' => [
                'nullable',
                Rule::in(MataKuliah::visibleTo($request->user())->pluck('id')->all()),
            ],
            'judul' => ['required', 'string', 'max:200'],
            'nama_mata_kuliah' => ['required_without:id_mata_kuliah', 'nullable', 'string', 'max:255'],
            'pertemuan' => ['required', 'string', 'max:255'],
            'tipe' => ['required', Rule::in(['File', 'Link'])],
            'detail' => ['required_without:file', 'nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx', 'max:20480'],
            ...($updating ? ['remove_file' => ['nullable', 'boolean']] : []),
        ]);
    }

    private function courseName(Request $request, mixed $courseId): string
    {
        if ($courseId) {
            return MataKuliah::visibleTo($request->user())->findOrFail($courseId)->nama;
        }

        return $request->string('nama_mata_kuliah')->toString();
    }

    private function present(Materi $material, User $user): array
    {
        return [
            'id' => $material->id,
            'id_mata_kuliah' => $material->id_mata_kuliah,
            'judul' => $material->judul,
            'nama_mata_kuliah' => $material->nama_mata_kuliah,
            'course_name' => $material->course_name ?? $material->nama_mata_kuliah,
            'pertemuan' => $material->pertemuan,
            'tipe' => $material->tipe,
            'detail' => $material->detail,
            'file_name' => $material->file_name,
            'download_url' => $material->file_path ? url("/api/v1/materis/{$material->id}/download") : null,
            'is_admin_shared' => $material->creator_role === 'admin',
            'can_manage' => $user->role === 'admin' || (int) $material->user_id === (int) $user->id,
            'created_at' => $material->created_at,
            'updated_at' => $material->updated_at,
        ];
    }
}
