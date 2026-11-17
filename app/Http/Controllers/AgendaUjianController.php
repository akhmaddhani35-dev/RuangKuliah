<?php

namespace App\Http\Controllers;

use App\Models\AgendaUjian;
use App\Models\MataKuliah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AgendaUjianController extends Controller
{
    public function index()
    {
        return redirect()->to(route('jadwal.index').'#agenda-ujian');
    }

    public function create()
    {
        $this->requireAdmin();
        $mataKuliahs = MataKuliah::visibleTo(Auth::user())->get();

        return view('ujian.create', compact('mataKuliahs'));
    }

    public function store(Request $request)
    {
        $this->requireAdmin();
        $validated = $this->validatedData($request);

        AgendaUjian::create([
            ...$validated,
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('ujian.index')->with('success', 'Agenda ujian berhasil ditambahkan.');
    }

    public function show(string $id)
    {
        $agendaUjian = AgendaUjian::visibleTo(Auth::user())->with('mataKuliah')->findOrFail($id);

        return view('ujian.show', compact('agendaUjian'));
    }

    public function edit(string $id)
    {
        $this->requireAdmin();
        $agendaUjian = AgendaUjian::manageableBy(Auth::user())->findOrFail($id);
        $mataKuliahs = MataKuliah::visibleTo(Auth::user())->get();

        return view('ujian.edit', compact('agendaUjian', 'mataKuliahs'));
    }

    public function update(Request $request, string $id)
    {
        $this->requireAdmin();
        $agendaUjian = AgendaUjian::manageableBy(Auth::user())->findOrFail($id);
        $agendaUjian->update($this->validatedData($request));

        return redirect()->route('ujian.index')->with('success', 'Agenda ujian berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $this->requireAdmin();
        AgendaUjian::manageableBy(Auth::user())->findOrFail($id)->delete();

        return redirect()->route('ujian.index')->with('success', 'Agenda ujian berhasil dihapus.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'id_mata_kuliah' => [
                'required',
                Rule::in(MataKuliah::visibleTo(Auth::user())->pluck('id')->all()),
            ],
            'jenis' => ['required', Rule::in(['UTS', 'UAS'])],
            'tanggal' => ['required', 'date'],
            'waktu_mulai' => ['required', 'date_format:H:i'],
            'waktu_selesai' => ['required', 'date_format:H:i', 'after:waktu_mulai'],
            'ruang' => ['nullable', 'string', 'max:255'],
            'mode' => ['required', Rule::in(['Offline', 'Online'])],
            'link_zoom' => ['required_if:mode,Online', 'nullable', 'url', 'max:255'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function requireAdmin(): void
    {
        abort_unless(Auth::user()->role === 'admin', 403);
    }
}
