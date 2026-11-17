<?php

namespace App\Http\Controllers;

use App\Models\AgendaUjian;
use App\Models\Jadwal;
use App\Models\MataKuliah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class JadwalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $jadwals = Jadwal::visibleTo($user)
            ->leftJoin('mata_kuliahs as course', 'jadwals.id_mata_kuliah', '=', 'course.id')
            ->select('jadwals.*', 'course.nama as course_name')
            ->orderBy('jadwals.hari')
            ->orderBy('jadwals.waktu_mulai')
            ->get();
        $agendaUjians = AgendaUjian::visibleTo($user)
            ->join('mata_kuliahs as course', 'agenda_ujians.id_mata_kuliah', '=', 'course.id')
            ->select('agenda_ujians.*', 'course.nama as course_name')
            ->orderBy('tanggal')
            ->orderBy('waktu_mulai')
            ->get();
        $isAdmin = $user->role === 'admin';

        return view('jadwal.index', compact('jadwals', 'agendaUjians', 'isAdmin'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->requireAdmin();
        $mata_kuliahs = MataKuliah::visibleTo(Auth::user())->get();

        return view('jadwal.create', compact('mata_kuliahs'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->requireAdmin();
        $validated = $request->validate([
            'id_mata_kuliah' => [
                'required',
                Rule::in(MataKuliah::visibleTo(Auth::user())->pluck('id')->all()),
            ],
            'hari' => ['required', Rule::in(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'])],
            'waktu_mulai' => ['required', 'date_format:H:i'],
            'waktu_selesai' => ['required', 'date_format:H:i', 'after:waktu_mulai'],
            'ruang' => ['required', 'string', 'max:255'],
            'dosen' => ['required', 'string', 'max:255'],
            'mode' => ['required', Rule::in(['Offline', 'Online'])],
            'link_zoom' => ['nullable', 'string', 'max:255'],
        ]);

        $mataKuliah = MataKuliah::visibleTo(Auth::user())->findOrFail($validated['id_mata_kuliah']);
        $validated['nama_mata_kuliah'] = $mataKuliah->nama;

        Jadwal::create([
            ...$validated,
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('jadwal.index')->with('success', 'Jadwal berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $jadwal = Jadwal::visibleTo(Auth::user())->with('mataKuliah')->findOrFail($id);

        return view('jadwal.show', compact('jadwal'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $this->requireAdmin();
        $mata_kuliahs = MataKuliah::visibleTo(Auth::user())->get();
        $jadwal = Jadwal::manageableBy(Auth::user())->findOrFail($id);

        return view('jadwal.edit', compact('jadwal', 'mata_kuliahs'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $this->requireAdmin();
        $validated = $request->validate([
            'id_mata_kuliah' => [
                'required',
                Rule::in(MataKuliah::visibleTo(Auth::user())->pluck('id')->all()),
            ],
            'hari' => ['required', Rule::in(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'])],
            'waktu_mulai' => ['required', 'date_format:H:i'],
            'waktu_selesai' => ['required', 'date_format:H:i', 'after:waktu_mulai'],
            'ruang' => ['required', 'string', 'max:255'],
            'dosen' => ['required', 'string', 'max:255'],
            'mode' => ['required', Rule::in(['Offline', 'Online'])],
            'link_zoom' => ['nullable', 'string', 'max:255'],
        ]);

        $jadwal = Jadwal::manageableBy(Auth::user())->findOrFail($id);
        $mataKuliah = MataKuliah::visibleTo(Auth::user())->findOrFail($validated['id_mata_kuliah']);
        $validated['nama_mata_kuliah'] = $mataKuliah->nama;
        $jadwal->update($validated);

        return redirect()->route('jadwal.index')->with('success', 'Jadwal berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->requireAdmin();
        $jadwal = Jadwal::manageableBy(Auth::user())->findOrFail($id);
        $jadwal->delete();

        return redirect()->route('jadwal.index')->with('success', 'Jadwal berhasil dihapus.');
    }

    private function requireAdmin(): void
    {
        abort_unless(Auth::user()->role === 'admin', 403);
    }
}
