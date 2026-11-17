<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AgendaUjian;
use App\Models\Jadwal;
use App\Models\MataKuliah;
use App\Models\Materi;
use App\Models\Tugas;
use App\Models\TugasStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $user = Auth::user();
        $today = today();
        $weekday = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ][now()->dayOfWeekIso];
        $visibleTasks = Tugas::visibleTo($user);
        $summaryQuery = DB::query()
            ->selectRaw("'summary' as category")
            ->selectRaw('CAST(NULL AS BIGINT) as id')
            ->selectRaw('CAST(NULL AS TEXT) as course_name')
            ->selectRaw('CAST(NULL AS TEXT) as nama_mata_kuliah')
            ->selectRaw('CAST(NULL AS TEXT) as creator_role')
            ->selectRaw('CAST(NULL AS TEXT) as title')
            ->selectRaw('CAST(NULL AS TEXT) as reference_date')
            ->selectRaw('CAST(NULL AS TEXT) as start_time')
            ->selectRaw('CAST(NULL AS TEXT) as end_time')
            ->selectRaw('CAST(NULL AS TEXT) as detail')
            ->selectRaw('CAST(NULL AS TEXT) as priority')
            ->selectRaw('CAST(NULL AS TEXT) as room')
            ->selectRaw('CAST(NULL AS TEXT) as lecturer')
            ->selectRaw('CAST(NULL AS TEXT) as mode')
            ->selectRaw('CAST(NULL AS TEXT) as link')
            ->selectRaw('CAST(NULL AS TEXT) as created_at')
            ->selectSub(MataKuliah::visibleTo($user)->selectRaw('COUNT(*)'), 'summary_courses')
            ->selectSub((clone $visibleTasks)->selectRaw('COUNT(*)'), 'summary_tasks')
            ->selectSub(
                (clone $visibleTasks)
                    ->where('deadline', '>=', $today->toDateString())
                    ->where('deadline', '<=', $today->copy()->addDays(3)->toDateString())
                    ->selectRaw('COUNT(*)'),
                'summary_tasks_due',
            );

        if ($user->role !== 'admin') {
            $summaryQuery->selectSub(
                TugasStatus::query()
                    ->where('user_id', $user->id)
                    ->where('status', 'Selesai')
                    ->whereIn('tugas_id', (clone $visibleTasks)->select('tugas.id'))
                    ->selectRaw('COUNT(*)'),
                'summary_tasks_completed',
            );
        } else {
            $summaryQuery->selectRaw('CAST(NULL AS BIGINT) as summary_tasks_completed');
        }

        $jadwalsQuery = Jadwal::visibleTo($user)
            ->leftJoin('mata_kuliahs as course', 'jadwals.id_mata_kuliah', '=', 'course.id')
            ->where('jadwals.hari', $weekday)
            ->selectRaw("'schedule' as category")
            ->addSelect(['jadwals.id', 'course.nama as course_name'])
            ->selectRaw('CAST(NULL AS TEXT) as nama_mata_kuliah')
            ->selectRaw('CAST(NULL AS TEXT) as creator_role')
            ->selectRaw('CAST(NULL AS TEXT) as title')
            ->selectRaw('CAST(NULL AS TEXT) as reference_date')
            ->selectRaw('CAST(jadwals.waktu_mulai AS TEXT) as start_time')
            ->selectRaw('CAST(jadwals.waktu_selesai AS TEXT) as end_time')
            ->selectRaw('CAST(NULL AS TEXT) as detail')
            ->selectRaw('CAST(NULL AS TEXT) as priority')
            ->selectRaw('CAST(jadwals.ruang AS TEXT) as room')
            ->selectRaw('CAST(jadwals.dosen AS TEXT) as lecturer')
            ->selectRaw('CAST(jadwals.mode AS TEXT) as mode')
            ->selectRaw('CAST(jadwals.link_zoom AS TEXT) as link')
            ->selectRaw('CAST(NULL AS TEXT) as created_at')
            ->selectRaw('CAST(NULL AS BIGINT) as summary_courses')
            ->selectRaw('CAST(NULL AS BIGINT) as summary_tasks')
            ->selectRaw('CAST(NULL AS BIGINT) as summary_tasks_due')
            ->selectRaw('CAST(NULL AS BIGINT) as summary_tasks_completed');

        $tugasQuery = Tugas::visibleTo($user)
            ->leftJoin('mata_kuliahs as course', 'tugas.id_mata_kuliah', '=', 'course.id')
            ->leftJoin('users as creator', 'tugas.user_id', '=', 'creator.id')
            ->where('tugas.deadline', '>=', $today->toDateString())
            ->where('tugas.deadline', '<=', $today->copy()->addDays(3)->toDateString())
            ->orderBy('tugas.deadline')
            ->limit(5)
            ->selectRaw("'task' as category")
            ->addSelect(['tugas.id', 'course.nama as course_name'])
            ->selectRaw('CAST(NULL AS TEXT) as nama_mata_kuliah')
            ->selectRaw('CAST(creator.role AS TEXT) as creator_role')
            ->selectRaw('CAST(tugas.judul AS TEXT) as title')
            ->selectRaw('CAST(tugas.deadline AS TEXT) as reference_date')
            ->selectRaw('CAST(NULL AS TEXT) as start_time')
            ->selectRaw('CAST(NULL AS TEXT) as end_time')
            ->selectRaw('CAST(NULL AS TEXT) as detail')
            ->selectRaw('CAST(tugas.prioritas AS TEXT) as priority')
            ->selectRaw('CAST(NULL AS TEXT) as room')
            ->selectRaw('CAST(NULL AS TEXT) as lecturer')
            ->selectRaw('CAST(NULL AS TEXT) as mode')
            ->selectRaw('CAST(NULL AS TEXT) as link')
            ->selectRaw('CAST(NULL AS TEXT) as created_at')
            ->selectRaw('CAST(NULL AS BIGINT) as summary_courses')
            ->selectRaw('CAST(NULL AS BIGINT) as summary_tasks')
            ->selectRaw('CAST(NULL AS BIGINT) as summary_tasks_due')
            ->selectRaw('CAST(NULL AS BIGINT) as summary_tasks_completed');

        $materiQuery = Materi::visibleTo($user)
            ->leftJoin('mata_kuliahs as course', 'materis.id_mata_kuliah', '=', 'course.id')
            ->leftJoin('users as creator', 'materis.user_id', '=', 'creator.id')
            ->orderByDesc('materis.created_at')
            ->limit(5)
            ->selectRaw("'material' as category")
            ->addSelect(['materis.id', DB::raw('COALESCE(course.nama, materis.nama_mata_kuliah) as course_name')])
            ->selectRaw('CAST(materis.nama_mata_kuliah AS TEXT) as nama_mata_kuliah')
            ->selectRaw('CAST(creator.role AS TEXT) as creator_role')
            ->selectRaw('CAST(materis.judul AS TEXT) as title')
            ->selectRaw('CAST(NULL AS TEXT) as reference_date')
            ->selectRaw('CAST(NULL AS TEXT) as start_time')
            ->selectRaw('CAST(NULL AS TEXT) as end_time')
            ->selectRaw('CAST(NULL AS TEXT) as detail')
            ->selectRaw('CAST(NULL AS TEXT) as priority')
            ->selectRaw('CAST(NULL AS TEXT) as room')
            ->selectRaw('CAST(NULL AS TEXT) as lecturer')
            ->selectRaw('CAST(NULL AS TEXT) as mode')
            ->selectRaw('CAST(NULL AS TEXT) as link')
            ->selectRaw('CAST(materis.created_at AS TEXT) as created_at')
            ->selectRaw('CAST(NULL AS BIGINT) as summary_courses')
            ->selectRaw('CAST(NULL AS BIGINT) as summary_tasks')
            ->selectRaw('CAST(NULL AS BIGINT) as summary_tasks_due')
            ->selectRaw('CAST(NULL AS BIGINT) as summary_tasks_completed');

        $ujianQuery = AgendaUjian::visibleTo($user)
            ->join('mata_kuliahs as course', 'agenda_ujians.id_mata_kuliah', '=', 'course.id')
            ->where('agenda_ujians.tanggal', '>=', $today->toDateString())
            ->where('agenda_ujians.tanggal', '<=', $today->copy()->addDays(30)->toDateString())
            ->orderBy('agenda_ujians.tanggal')
            ->orderBy('agenda_ujians.waktu_mulai')
            ->limit(5)
            ->selectRaw("'exam' as category")
            ->addSelect(['agenda_ujians.id', 'course.nama as course_name'])
            ->selectRaw('CAST(NULL AS TEXT) as nama_mata_kuliah')
            ->selectRaw('CAST(NULL AS TEXT) as creator_role')
            ->selectRaw('CAST(NULL AS TEXT) as title')
            ->selectRaw('CAST(agenda_ujians.tanggal AS TEXT) as reference_date')
            ->selectRaw('CAST(agenda_ujians.waktu_mulai AS TEXT) as start_time')
            ->selectRaw('CAST(agenda_ujians.waktu_selesai AS TEXT) as end_time')
            ->selectRaw('CAST(agenda_ujians.catatan AS TEXT) as detail')
            ->selectRaw('CAST(agenda_ujians.jenis AS TEXT) as priority')
            ->selectRaw('CAST(agenda_ujians.ruang AS TEXT) as room')
            ->selectRaw('CAST(NULL AS TEXT) as lecturer')
            ->selectRaw('CAST(agenda_ujians.mode AS TEXT) as mode')
            ->selectRaw('CAST(agenda_ujians.link_zoom AS TEXT) as link')
            ->selectRaw('CAST(NULL AS TEXT) as created_at')
            ->selectRaw('CAST(NULL AS BIGINT) as summary_courses')
            ->selectRaw('CAST(NULL AS BIGINT) as summary_tasks')
            ->selectRaw('CAST(NULL AS BIGINT) as summary_tasks_due')
            ->selectRaw('CAST(NULL AS BIGINT) as summary_tasks_completed');

        $dashboardItems = DB::query()
            ->fromSub(
                $summaryQuery->unionAll($jadwalsQuery->toBase())
                    ->unionAll($tugasQuery->toBase())
                    ->unionAll($materiQuery->toBase())
                    ->unionAll($ujianQuery->toBase()),
                'dashboard_items',
            )
            ->get()
            ->groupBy('category');
        $stats = $dashboardItems->get('summary')->first();
        $jadwalsHariIni = $dashboardItems->get('schedule', collect())->sortBy('start_time')->map(fn ($item) => [
            'id' => $item->id,
            'course_name' => $item->course_name,
            'hari' => $weekday,
            'waktu_mulai' => $item->start_time,
            'waktu_selesai' => $item->end_time,
            'ruang' => $item->room,
            'dosen' => $item->lecturer,
            'mode' => $item->mode,
            'link_zoom' => $item->link,
        ])->values();
        $tugasMendekat = $dashboardItems->get('task', collect())->sortBy('reference_date')->map(fn ($item) => [
            'id' => $item->id,
            'judul' => $item->title,
            'deadline' => $item->reference_date,
            'prioritas' => $item->priority,
            'course_name' => $item->course_name,
            'is_admin_shared' => $item->creator_role === 'admin',
        ])->values();
        $materiTerbaru = $dashboardItems->get('material', collect())->sortByDesc('created_at')->map(fn ($item) => [
            'id' => $item->id,
            'judul' => $item->title,
            'nama_mata_kuliah' => $item->nama_mata_kuliah,
            'course_name' => $item->course_name,
            'created_at' => $item->created_at,
            'is_admin_shared' => $item->creator_role === 'admin',
        ])->values();
        $ujianMendatang = $dashboardItems->get('exam', collect())->sortBy(fn ($item) => $item->reference_date.' '.$item->start_time)->map(fn ($item) => [
            'id' => $item->id,
            'jenis' => $item->priority,
            'tanggal' => $item->reference_date,
            'waktu_mulai' => $item->start_time,
            'waktu_selesai' => $item->end_time,
            'mode' => $item->mode,
            'course_name' => $item->course_name,
        ])->values();

        return response()->json([
            'data' => [
                'is_admin' => $user->role === 'admin',
                'summary' => [
                    'jumlah_mata_kuliah' => (int) $stats->summary_courses,
                    'jumlah_tugas' => (int) $stats->summary_tasks,
                    'jumlah_tugas_mendekat' => (int) $stats->summary_tasks_due,
                    'jumlah_tugas_belum_selesai' => $user->role === 'admin'
                        ? null
                        : max(0, (int) $stats->summary_tasks - (int) $stats->summary_tasks_completed),
                    'persentase_selesai' => $user->role === 'admin' || (int) $stats->summary_tasks === 0
                        ? null
                        : (int) round((int) $stats->summary_tasks_completed / (int) $stats->summary_tasks * 100),
                ],
                'jadwal_hari_ini' => $jadwalsHariIni,
                'tugas_mendekat' => $tugasMendekat,
                'materi_terbaru' => $materiTerbaru,
                'ujian_mendatang' => $ujianMendatang,
            ],
        ]);
    }
}
