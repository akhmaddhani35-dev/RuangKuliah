<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\DeletesStoredFiles;
use App\Models\MataKuliah;
use App\Models\Materi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MateriController extends Controller
{
    use DeletesStoredFiles;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $materis = Materi::visibleTo($user)
            ->leftJoin('mata_kuliahs as course', 'materis.id_mata_kuliah', '=', 'course.id')
            ->select('materis.*', 'course.nama as course_name')
            ->orderByDesc('materis.created_at')
            ->paginate(12)
            ->withQueryString();
        $isAdmin = $user->role === 'admin';

        return view('materi.index', compact('materis', 'isAdmin'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $mata_kuliahs = MataKuliah::visibleTo(Auth::user())->get();

        return view('materi.create', compact('mata_kuliahs'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_mata_kuliah' => [
                'nullable',
                Rule::in(MataKuliah::visibleTo(Auth::user())->pluck('id')->all()),
            ],
            'judul' => ['required', 'string', 'max:200'],
            'nama_mata_kuliah' => ['required_without:id_mata_kuliah', 'nullable', 'string', 'max:255'],
            'pertemuan' => ['required', 'string', 'max:255'],
            'tipe' => ['required', Rule::in(['File', 'Link'])],
            'detail' => ['required_without:file', 'nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx', 'max:20480'],
        ]);

        if (! empty($validated['id_mata_kuliah'])) {
            $mataKuliah = MataKuliah::visibleTo(Auth::user())->findOrFail($validated['id_mata_kuliah']);
            $validated['nama_mata_kuliah'] = $mataKuliah->nama;
        }
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

        Materi::create([
            ...$validated,
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('materi.index')->with('success', 'Materi berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $materi = Materi::visibleTo(Auth::user())->with('mataKuliah')->findOrFail($id);

        return view('materi.show', compact('materi'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $mata_kuliahs = MataKuliah::visibleTo(Auth::user())->get();
        $materi = Materi::manageableBy(Auth::user())->findOrFail($id);

        return view('materi.edit', compact('materi', 'mata_kuliahs'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'id_mata_kuliah' => [
                'nullable',
                Rule::in(MataKuliah::visibleTo(Auth::user())->pluck('id')->all()),
            ],
            'judul' => ['required', 'string', 'max:200'],
            'nama_mata_kuliah' => ['required_without:id_mata_kuliah', 'nullable', 'string', 'max:255'],
            'pertemuan' => ['required', 'string', 'max:255'],
            'tipe' => ['required', Rule::in(['File', 'Link'])],
            'detail' => ['required_without:file', 'nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx', 'max:20480'],
            'remove_file' => ['nullable', 'boolean'],
        ]);

        $materi = Materi::manageableBy(Auth::user())->findOrFail($id);
        if (! empty($validated['id_mata_kuliah'])) {
            $mataKuliah = MataKuliah::visibleTo(Auth::user())->findOrFail($validated['id_mata_kuliah']);
            $validated['nama_mata_kuliah'] = $mataKuliah->nama;
        }
        unset($validated['file'], $validated['remove_file']);
        $previousFilePath = $materi->file_path;
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

        $materi->update($validated);
        if ($previousFilePath && $previousFilePath !== $materi->file_path) {
            $this->deleteStoredFile($previousFilePath, 'materi');
        }

        return redirect()->route('materi.index')->with('success', 'Materi berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $materi = Materi::manageableBy(Auth::user())->findOrFail($id);
        $this->deleteStoredFile($materi->file_path, 'materi');
        $materi->delete();

        return redirect()->route('materi.index')->with('success', 'Materi berhasil dihapus.');
    }

    public function download(string $id)
    {
        $materi = Materi::visibleTo(Auth::user())->findOrFail($id);
        abort_unless($materi->file_path && Storage::disk('local')->exists($materi->file_path), 404);

        return Storage::disk('local')->download($materi->file_path, $materi->file_name);
    }
}
