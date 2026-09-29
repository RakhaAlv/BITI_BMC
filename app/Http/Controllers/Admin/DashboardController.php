<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReviewAssignment;
use App\Models\Setting;
use App\Services\ScoreCalculator;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, ScoreCalculator $calc)
    {
        $periode = ScoreCalculator::periodeSekarang();
        $peringkat = $calc->peringkat($periode);
        $setting = Setting::current();

        $rata = $peringkat->isEmpty() ? 0 : round($peringkat->avg(fn ($r) => $r['skor']['akhir']), 2);
        $belumIsi = ReviewAssignment::where('periode', $periode)->where('selesai', false)
            ->distinct('reviewer_id')->count('reviewer_id');

        return view('admin.dashboard', [
            'periode' => $periode,
            'peringkat' => $peringkat,
            'tampilSemua' => $request->boolean('semua'),
            'rata' => $rata,
            'jumlahKaryawan' => $peringkat->count(),
            'belumIsi' => $belumIsi,
            'setting' => $setting,
            'tren' => $calc->tren(6),
        ]);
    }
}
