<?php

namespace App\Http\Controllers;

use App\Models\MataKuliah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MataKuliahController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $mataKuliahs = MataKuliah::visibleTo($user)->orderBy('nama')->paginate(12)->withQueryString();
        $isAdmin = $user->role === 'admin';

        return view('matakuliah.index', compact('mataKuliahs', 'isAdmin'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        return view('matakuliah.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kode_mk' => ['required', 'string', 'max:255'],
            'dosen' => ['required', 'string', 'max:255'],
            'sks' => ['required', 'integer', 'min:1'],
        ]);

        MataKuliah::create([
            ...$validated,
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('matakuliah.index')
            ->with('success', 'Mata kuliah berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $mataKuliah = MataKuliah::visibleTo(Auth::user())->findOrFail($id);

        return view('matakuliah.show', compact('mataKuliah'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $mataKuliah = MataKuliah::manageableBy(Auth::user())->findOrFail($id);

        return view('matakuliah.edit', compact('mataKuliah'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kode_mk' => ['required', 'string', 'max:255'],
            'dosen' => ['required', 'string', 'max:255'],
            'sks' => ['required', 'integer', 'min:1'],
        ]);

        $mataKuliah = MataKuliah::manageableBy(Auth::user())->findOrFail($id);
        $mataKuliah->update($validated);

        return redirect()->route('matakuliah.index')
            ->with('success', 'Mata kuliah berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $mataKuliah = MataKuliah::manageableBy(Auth::user())->findOrFail($id);

        if ($mataKuliah->jadwals()->exists() || $mataKuliah->materis()->exists() || $mataKuliah->tugas()->exists()) {
            return redirect()->route('matakuliah.index')
                ->with('error', 'Mata kuliah tidak dapat dihapus selama masih digunakan oleh jadwal, materi, atau tugas.');
        }

        $mataKuliah->delete();

        return redirect()->route('matakuliah.index')
            ->with('success', 'Mata kuliah berhasil dihapus.');
    }
}
