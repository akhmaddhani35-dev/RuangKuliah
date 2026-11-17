<?php

namespace App\Http\Controllers;

use App\Models\AgendaUjian;
use App\Models\Jadwal;
use App\Models\MataKuliah;
use App\Models\Materi;
use App\Models\Tugas;
use App\Models\TugasStatus;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();
        $now = now();
        $today = $now->copy()->startOfDay();
        $deadlineEnd = $today->copy()->addDays(3);
        $weekday = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ][$now->dayOfWeekIso];

        $isAdmin = $user->role === 'admin';
        $tugasQuery = Tugas::visibleTo($user);
        $statsQuery = DB::query()
            ->selectSub(MataKuliah::visibleTo($user)->selectRaw('COUNT(*)'), 'jumlah_mata_kuliah')
            ->selectSub((clone $tugasQuery)->selectRaw('COUNT(*)'), 'total_tugas')
            ->selectSub(
                (clone $tugasQuery)
                    ->whereDate('deadline', '>=', $today)
                    ->whereDate('deadline', '<=', $deadlineEnd)
                    ->selectRaw('COUNT(*)'),
                'jumlah_tugas_mendekat',
            );

        if (! $isAdmin) {
            $tugasSelesaiQuery = TugasStatus::query()
                ->where('user_id', $user->id)
                ->where('status', 'Selesai')
                ->whereIn('tugas_id', (clone $tugasQuery)->select('tugas.id'));
            $statsQuery->selectSub($tugasSelesaiQuery->selectRaw('COUNT(*)'), 'tugas_selesai');
        }

        $stats = $statsQuery->first();
        $tugasMendekatQuery = Tugas::visibleTo($user)
            ->leftJoin('mata_kuliahs as course', 'tugas.id_mata_kuliah', '=', 'course.id')
            ->select('tugas.*', 'course.nama as course_name')
            ->whereDate('tugas.deadline', '>=', $today)
            ->whereDate('tugas.deadline', '<=', $deadlineEnd)
            ->orderBy('tugas.deadline')
            ->limit(5);

        if (! $isAdmin) {
            $tugasMendekatQuery
                ->leftJoin('tugas_user_statuses as user_status', function (JoinClause $join) use ($user) {
                    $join->on('user_status.tugas_id', '=', 'tugas.id')
                        ->where('user_status.user_id', '=', $user->id);
                })
                ->addSelect('user_status.status as my_status');
        }

        $totalTugas = (int) $stats->total_tugas;
        $tugasSelesai = $isAdmin ? 0 : (int) $stats->tugas_selesai;

        return view('dashboard', [
            'isAdmin' => $isAdmin,
            'jumlahMataKuliah' => (int) $stats->jumlah_mata_kuliah,
            'jadwalsHariIni' => Jadwal::visibleTo($user)
                ->leftJoin('mata_kuliahs as course', 'jadwals.id_mata_kuliah', '=', 'course.id')
                ->select('jadwals.*', 'course.nama as course_name')
                ->where('jadwals.hari', $weekday)
                ->orderBy('jadwals.waktu_mulai')
                ->get(),
            'tugasMendekat' => $tugasMendekatQuery->get(),
            'materiTerbaru' => Materi::visibleTo($user)
                ->leftJoin('mata_kuliahs as course', 'materis.id_mata_kuliah', '=', 'course.id')
                ->select('materis.*', 'course.nama as course_name')
                ->orderByDesc('materis.created_at')
                ->limit(5)
                ->get(),
            'ujianMendatang' => AgendaUjian::visibleTo($user)
                ->join('mata_kuliahs as course', 'agenda_ujians.id_mata_kuliah', '=', 'course.id')
                ->select('agenda_ujians.*', 'course.nama as course_name')
                ->whereDate('agenda_ujians.tanggal', '>=', $today)
                ->whereDate('agenda_ujians.tanggal', '<=', $today->copy()->addDays(30))
                ->orderBy('agenda_ujians.tanggal')
                ->orderBy('agenda_ujians.waktu_mulai')
                ->limit(5)
                ->get(),
            'jumlahTugasMendekat' => (int) $stats->jumlah_tugas_mendekat,
            'jumlahTugasDibagikan' => $isAdmin ? $totalTugas : null,
            'jumlahTugasBelumSelesai' => $isAdmin ? null : max(0, $totalTugas - $tugasSelesai),
            'persentaseSelesai' => $isAdmin || $totalTugas === 0 ? null : (int) round($tugasSelesai / $totalTugas * 100),
        ]);
    }
}
