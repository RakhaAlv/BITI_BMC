<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonthlyScore;
use App\Models\User;
use App\Services\ScoreCalculator;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request, ScoreCalculator $calc)
    {
        $calc->arsipkan(ScoreCalculator::periodeSekarang());

        $daftarPeriode = MonthlyScore::select('periode')->distinct()->orderByDesc('periode')->pluck('periode');
        $periode = $request->query('periode', $daftarPeriode->first());

        $rows = MonthlyScore::with('user')->where('periode', $periode)
            ->orderByDesc('skor_akhir')->get();

        return view('admin.reports', [
            'daftarPeriode' => $daftarPeriode,
            'periode' => $periode,
            'rows' => $rows,
            'rata' => $rows->isEmpty() ? 0 : round($rows->avg('skor_akhir'), 2),
            'tren' => $calc->tren(6),
        ]);
    }

    public function exportTim(Request $request)
    {
        $periode = $request->query('periode', ScoreCalculator::periodeSekarang());
        $rows = MonthlyScore::with('user')->where('periode', $periode)->orderByDesc('skor_akhir')->get();

        return $this->csv("laporan-tim-{$periode}.csv", function ($out) use ($rows) {
            fputcsv($out, ['Peringkat', 'Nama', 'Jabatan', 'Kehadiran', 'Target', 'Peer Review', 'Skor Akhir']);
            foreach ($rows as $i => $r) {
                fputcsv($out, [
                    $i + 1, $r->user->name, $r->user->jabatan,
                    $r->skor_kehadiran, $r->skor_target, $r->skor_peer ?? '-', $r->skor_akhir,
                ]);
            }
        });
    }

    public function exportKaryawan(User $user)
    {
        abort_unless($user->role === 'karyawan', 404);
        $rows = $user->monthlyScores()->orderBy('periode')->get();

        return $this->csv("rapor-{$user->id}.csv", function ($out) use ($rows, $user) {
            fputcsv($out, ['Karyawan', $user->name]);
            fputcsv($out, ['Periode', 'Kehadiran', 'Target', 'Peer Review', 'Skor Akhir']);
            foreach ($rows as $r) {
                fputcsv($out, [$r->periode, $r->skor_kehadiran, $r->skor_target, $r->skor_peer ?? '-', $r->skor_akhir]);
            }
        });
    }

    private function csv(string $nama, callable $tulis)
    {
        return response()->streamDownload(function () use ($tulis) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 agar Excel terbaca benar
            $tulis($out);
            fclose($out);
        }, $nama, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
