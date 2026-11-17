<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\DeletesStoredFiles;
use App\Http\Controllers\Controller;
use App\Models\MataKuliah;
use App\Models\Tugas;
use App\Models\TugasStatus;
use App\Models\User;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TugasController extends Controller
{
    use DeletesStoredFiles;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'course_id' => ['nullable', Rule::in(MataKuliah::visibleTo($user)->pluck('id')->all())],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'source' => $user->role === 'admin' ? ['prohibited'] : ['nullable', Rule::in(['shared', 'personal'])],
            'status' => $user->role === 'admin'
                ? ['prohibited']
                : ['nullable', Rule::in(['Belum dikerjakan', 'Sudah dikerjakan', 'Selesai'])],
        ]);
        $query = Tugas::visibleTo($user)
            ->leftJoin('mata_kuliahs as course', 'tugas.id_mata_kuliah', '=', 'course.id')
            ->leftJoin('users as creator', 'tugas.user_id', '=', 'creator.id')
            ->select('tugas.*', 'course.nama as course_name', 'creator.role as creator_role');

        if (! empty($filters['search'])) {
            $search = '%'.mb_strtolower(trim($filters['search'])).'%';
            $query->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(tugas.judul) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(tugas.deskripsi) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(tugas.nama_mata_kuliah) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(course.nama) LIKE ?', [$search]);
            });
        }
        if (! empty($filters['course_id'])) {
            $query->where('tugas.id_mata_kuliah', $filters['course_id']);
        }
        if (! empty($filters['date_from'])) {
            $query->where('tugas.deadline', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->where('tugas.deadline', '<=', $filters['date_to']);
        }
        if (($filters['source'] ?? null) === 'shared') {
            $query->where('creator.role', 'admin');
        } elseif (($filters['source'] ?? null) === 'personal') {
            $query->where('tugas.user_id', $user->id);
        }

        if ($user->role !== 'admin') {
            $query
                ->leftJoin('tugas_user_statuses as user_status', function (JoinClause $join) use ($user) {
                    $join->on('user_status.tugas_id', '=', 'tugas.id')
                        ->where('user_status.user_id', '=', $user->id);
                })
                ->addSelect('user_status.status as my_status');

            if (! empty($filters['status'])) {
                $query->where(function ($statusQuery) use ($filters) {
                    if ($filters['status'] === 'Belum dikerjakan') {
                        $statusQuery->whereNull('user_status.status')
                            ->orWhere('user_status.status', 'Belum dikerjakan');
                    } else {
                        $statusQuery->where('user_status.status', $filters['status']);
                    }
                });
            }
        }

        $assignments = $query->orderBy('tugas.deadline')->simplePaginate(12);
        $assignments->through(fn (Tugas $assignment) => $this->present($assignment, $user));

        return response()->json($assignments);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validated($request);
        $course = MataKuliah::visibleTo($request->user())->findOrFail($validated['id_mata_kuliah']);
        unset($validated['file']);
        $this->storeFile($request, $validated);

        $assignment = Tugas::create([
            ...$validated,
            'nama_mata_kuliah' => $course->nama,
            'user_id' => $request->user()->id,
        ]);
        $assignment->setAttribute('course_name', $course->nama);
        $assignment->setAttribute('creator_role', $request->user()->role);

        return response()->json(['data' => $this->present($assignment, $request->user())], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $assignment = Tugas::visibleTo($request->user())
            ->leftJoin('mata_kuliahs as course', 'tugas.id_mata_kuliah', '=', 'course.id')
            ->leftJoin('users as creator', 'tugas.user_id', '=', 'creator.id')
            ->select('tugas.*', 'course.nama as course_name', 'creator.role as creator_role')
            ->findOrFail($id);

        if ($request->user()->role !== 'admin') {
            $assignment->setAttribute('my_status', TugasStatus::query()
                ->where('tugas_id', $assignment->id)
                ->where('user_id', $request->user()->id)
                ->value('status') ?? 'Belum dikerjakan');
        }

        return response()->json(['data' => $this->present($assignment, $request->user())]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $assignment = Tugas::manageableBy($request->user())
            ->leftJoin('users as creator', 'tugas.user_id', '=', 'creator.id')
            ->select('tugas.*', 'creator.role as creator_role')
            ->findOrFail($id);
        $validated = $this->validated($request, true);
        $course = MataKuliah::visibleTo($request->user())->findOrFail($validated['id_mata_kuliah']);
        unset($validated['file'], $validated['remove_file']);
        $previousFilePath = $assignment->file_path;
        $this->storeFile($request, $validated);

        if (! $request->hasFile('file') && $request->boolean('remove_file')) {
            $validated['file_path'] = null;
            $validated['file_name'] = null;
        }

        $assignment->update([
            ...$validated,
            'nama_mata_kuliah' => $course->nama,
        ]);
        if ($previousFilePath && $previousFilePath !== $assignment->file_path) {
            $this->deleteStoredFile($previousFilePath, 'tugas');
        }
        $assignment->setAttribute('course_name', $course->nama);

        return response()->json(['data' => $this->present($assignment, $request->user())]);
    }

    public function destroy(Request $request, string $id): Response
    {
        $assignment = Tugas::manageableBy($request->user())->findOrFail($id);
        $this->deleteStoredFile($assignment->file_path, 'tugas');
        $assignment->delete();

        return response()->noContent();
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        abort_if($request->user()->role === 'admin', 403);
        $validated = $request->validate([
            'status' => ['required', Rule::in(['Belum dikerjakan', 'Sudah dikerjakan', 'Selesai'])],
        ]);
        $assignment = Tugas::visibleTo($request->user())->findOrFail($id);
        TugasStatus::updateOrCreate(
            ['tugas_id' => $assignment->id, 'user_id' => $request->user()->id],
            ['status' => $validated['status']],
        );

        return response()->json(['data' => ['tugas_id' => $assignment->id, 'status' => $validated['status']]]);
    }

    public function download(Request $request, string $id)
    {
        $assignment = Tugas::visibleTo($request->user())->findOrFail($id);
        abort_unless($assignment->file_path && Storage::disk('local')->exists($assignment->file_path), 404);

        return Storage::disk('local')->download($assignment->file_path, $assignment->file_name);
    }

    private function validated(Request $request, bool $updating = false): array
    {
        return $request->validate([
            'id_mata_kuliah' => [
                'required',
                Rule::in(MataKuliah::visibleTo($request->user())->pluck('id')->all()),
            ],
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string'],
            'deadline' => ['required', 'date'],
            'prioritas' => ['required', Rule::in(['Tinggi', 'Sedang', 'Rendah'])],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx', 'max:20480'],
            ...($updating ? ['remove_file' => ['nullable', 'boolean']] : []),
        ]);
    }

    private function storeFile(Request $request, array &$validated): void
    {
        if (! $request->hasFile('file')) {
            return;
        }

        $file = $request->file('file');
        $path = $file->store('tugas', 'local');
        abort_unless(is_string($path), 500, 'Lampiran tugas gagal disimpan.');
        $validated['file_path'] = $path;
        $validated['file_name'] = $file->getClientOriginalName();
    }

    private function present(Tugas $assignment, User $user): array
    {
        return [
            'id' => $assignment->id,
            'id_mata_kuliah' => $assignment->id_mata_kuliah,
            'judul' => $assignment->judul,
            'nama_mata_kuliah' => $assignment->nama_mata_kuliah,
            'course_name' => $assignment->course_name ?? $assignment->nama_mata_kuliah,
            'deskripsi' => $assignment->deskripsi,
            'deadline' => $assignment->deadline,
            'prioritas' => $assignment->prioritas,
            'file_name' => $assignment->file_name,
            'download_url' => $assignment->file_path ? url("/api/v1/tugas/{$assignment->id}/download") : null,
            'is_admin_shared' => $assignment->creator_role === 'admin',
            'my_status' => $user->role === 'admin' ? null : ($assignment->my_status ?? 'Belum dikerjakan'),
            'can_manage' => $user->role === 'admin' || (int) $assignment->user_id === (int) $user->id,
            'created_at' => $assignment->created_at,
            'updated_at' => $assignment->updated_at,
        ];
    }
}
