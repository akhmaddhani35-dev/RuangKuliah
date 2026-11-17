<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AgendaUjian;
use App\Models\MataKuliah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class AgendaUjianController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $exams = AgendaUjian::visibleTo($request->user())
            ->join('mata_kuliahs as course', 'agenda_ujians.id_mata_kuliah', '=', 'course.id')
            ->select('agenda_ujians.id', 'agenda_ujians.id_mata_kuliah', 'agenda_ujians.jenis', 'agenda_ujians.tanggal', 'agenda_ujians.waktu_mulai', 'agenda_ujians.waktu_selesai', 'agenda_ujians.ruang', 'agenda_ujians.mode', 'agenda_ujians.link_zoom', 'agenda_ujians.catatan', 'course.nama as course_name')
            ->orderBy('agenda_ujians.tanggal')
            ->orderBy('agenda_ujians.waktu_mulai')
            ->get();

        return response()->json(['data' => $exams->map(fn (AgendaUjian $exam) => $this->present($exam))]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        $exam = AgendaUjian::create([
            ...$this->validated($request),
            'user_id' => $request->user()->id,
        ]);
        $exam->load('mataKuliah:id,nama');

        return response()->json(['data' => $this->present($exam, $exam->mataKuliah->nama)], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $exam = AgendaUjian::visibleTo($request->user())
            ->join('mata_kuliahs as course', 'agenda_ujians.id_mata_kuliah', '=', 'course.id')
            ->select('agenda_ujians.id', 'agenda_ujians.id_mata_kuliah', 'agenda_ujians.jenis', 'agenda_ujians.tanggal', 'agenda_ujians.waktu_mulai', 'agenda_ujians.waktu_selesai', 'agenda_ujians.ruang', 'agenda_ujians.mode', 'agenda_ujians.link_zoom', 'agenda_ujians.catatan', 'course.nama as course_name')
            ->findOrFail($id);

        return response()->json(['data' => $this->present($exam)]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $this->requireAdmin($request);
        $exam = AgendaUjian::manageableBy($request->user())->findOrFail($id);
        $exam->update($this->validated($request));
        $exam->load('mataKuliah:id,nama');

        return response()->json(['data' => $this->present($exam, $exam->mataKuliah->nama)]);
    }

    public function destroy(Request $request, string $id): Response
    {
        $this->requireAdmin($request);
        AgendaUjian::manageableBy($request->user())->findOrFail($id)->delete();

        return response()->noContent();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'id_mata_kuliah' => [
                'required',
                Rule::in(MataKuliah::visibleTo($request->user())->pluck('id')->all()),
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

    private function requireAdmin(Request $request): void
    {
        abort_unless($request->user()->role === 'admin', 403);
    }

    private function present(AgendaUjian $exam, ?string $courseName = null): array
    {
        return [
            'id' => $exam->id,
            'id_mata_kuliah' => $exam->id_mata_kuliah,
            'course_name' => $courseName ?? $exam->course_name ?? $exam->mataKuliah?->nama,
            'jenis' => $exam->jenis,
            'tanggal' => $exam->tanggal?->format('Y-m-d'),
            'waktu_mulai' => $exam->waktu_mulai,
            'waktu_selesai' => $exam->waktu_selesai,
            'ruang' => $exam->ruang,
            'mode' => $exam->mode,
            'link_zoom' => $exam->link_zoom,
            'catatan' => $exam->catatan,
        ];
    }
}
