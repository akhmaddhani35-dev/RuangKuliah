<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\DeletesStoredFiles;
use App\Models\MataKuliah;
use App\Models\Tugas;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TugasController extends Controller
{
    use DeletesStoredFiles;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';
        $query = Tugas::visibleTo($user)
            ->leftJoin('mata_kuliahs as course', 'tugas.id_mata_kuliah', '=', 'course.id')
            ->select('tugas.*', 'course.nama as course_name');

        if (! $isAdmin) {
            $query
                ->leftJoin('tugas_user_statuses as user_status', function (JoinClause $join) use ($user) {
                    $join->on('user_status.tugas_id', '=', 'tugas.id')
                        ->where('user_status.user_id', '=', $user->id);
                })
                ->addSelect('user_status.status as my_status');
        }

        $tugas = $query->orderBy('tugas.deadline')->paginate(12)->withQueryString();

        return view('tugas.index', compact('tugas', 'isAdmin'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $mata_kuliahs = MataKuliah::visibleTo(Auth::user())->get();

        return view('tugas.create', compact('mata_kuliahs'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_mata_kuliah' => [
                'required',
                Rule::in(MataKuliah::visibleTo(Auth::user())->pluck('id')->all()),
            ],
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string'],
            'deadline' => ['required', 'date'],
            'prioritas' => ['required', Rule::in(['Tinggi', 'Sedang', 'Rendah'])],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx', 'max:20480'],
        ]);

        $mataKuliah = MataKuliah::visibleTo(Auth::user())->findOrFail($validated['id_mata_kuliah']);
        $validated['nama_mata_kuliah'] = $mataKuliah->nama;
        unset($validated['file']);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('tugas', 'local');
            abort_unless(is_string($path), 500, 'Lampiran tugas gagal disimpan.');
            $validated['file_path'] = $path;
            $validated['file_name'] = $file->getClientOriginalName();
        }

        Tugas::create([
            ...$validated,
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('tugas.index')->with('success', 'Tugas berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tugas = Tugas::visibleTo(Auth::user())->with('mataKuliah')->findOrFail($id);

        return view('tugas.show', compact('tugas'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $mata_kuliahs = MataKuliah::visibleTo(Auth::user())->get();
        $tugas = Tugas::manageableBy(Auth::user())->findOrFail($id);

        return view('tugas.edit', compact('tugas', 'mata_kuliahs'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'id_mata_kuliah' => [
                'required',
                Rule::in(MataKuliah::visibleTo(Auth::user())->pluck('id')->all()),
            ],
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string'],
            'deadline' => ['required', 'date'],
            'prioritas' => ['required', Rule::in(['Tinggi', 'Sedang', 'Rendah'])],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx', 'max:20480'],
            'remove_file' => ['nullable', 'boolean'],
        ]);

        $tugas = Tugas::manageableBy(Auth::user())->findOrFail($id);
        $mataKuliah = MataKuliah::visibleTo(Auth::user())->findOrFail($validated['id_mata_kuliah']);
        $validated['nama_mata_kuliah'] = $mataKuliah->nama;
        unset($validated['file'], $validated['remove_file']);

        $previousFilePath = $tugas->file_path;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('tugas', 'local');
            abort_unless(is_string($path), 500, 'Lampiran tugas gagal disimpan.');
            $validated['file_path'] = $path;
            $validated['file_name'] = $file->getClientOriginalName();
        } elseif ($request->boolean('remove_file')) {
            $validated['file_path'] = null;
            $validated['file_name'] = null;
        }

        $tugas->update($validated);
        if ($previousFilePath && $previousFilePath !== $tugas->file_path) {
            $this->deleteStoredFile($previousFilePath, 'tugas');
        }

        return redirect()->route('tugas.index')->with('success', 'Tugas berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $tugas = Tugas::manageableBy(Auth::user())->findOrFail($id);
        $this->deleteStoredFile($tugas->file_path, 'tugas');
        $tugas->delete();

        return redirect()->route('tugas.index')->with('success', 'Tugas berhasil dihapus.');
    }

    public function updateStatus(Request $request, string $id)
    {
        abort_if(Auth::user()->role === 'admin', 403);
        $validated = $request->validate([
            'status' => ['required', Rule::in(['Belum dikerjakan', 'Sudah dikerjakan', 'Selesai'])],
        ]);
        $tugas = Tugas::visibleTo(Auth::user())->findOrFail($id);
        $tugas->statuses()->updateOrCreate(
            ['user_id' => Auth::id()],
            ['status' => $validated['status']],
        );

        return redirect()->back()->with('success', 'Status tugas berhasil diperbarui.');
    }

    public function download(string $id)
    {
        $tugas = Tugas::visibleTo(Auth::user())->findOrFail($id);
        abort_unless($tugas->file_path && Storage::disk('local')->exists($tugas->file_path), 404);

        return Storage::disk('local')->download($tugas->file_path, $tugas->file_name);
    }
}
