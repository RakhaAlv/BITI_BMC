<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Feedback;
use App\Models\ReviewAssignment;
use App\Models\Setting;
use App\Services\ScoreCalculator;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, ScoreCalculator $calc)
    {
        $user = $request->user();
        $periode = ScoreCalculator::periodeSekarang();

        $peringkat = $calc->peringkat($periode);
        $posisi = $peringkat->search(fn ($r) => $r['user']->id === $user->id);

        return view('karyawan.dashboard', [
            'user' => $user,
            'skor' => $calc->hitung($user, $periode),
            'top5' => $peringkat->take(5),
            'posisi' => $posisi === false ? null : $posisi + 1,
            'totalKaryawan' => $peringkat->count(),
            'absenHariIni' => Attendance::where('user_id', $user->id)->whereDate('tanggal', today())->first(),
            'absenAktif' => Setting::current()->absen_mandiri_aktif,
            'tugasSurvei' => ReviewAssignment::where('reviewer_id', $user->id)
                ->where('periode', $periode)->where('selesai', false)->count(),
            'feedbackBaru' => Feedback::where('user_id', $user->id)->latest()->take(3)->get(),
            'riwayat' => $user->monthlyScores()->orderBy('periode')->get()->take(-6),
        ]);
    }
}
