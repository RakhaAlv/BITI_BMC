<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\MonthlyScore;
use App\Models\Review;
use App\Models\Setting;
use App\Models\Target;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Mesin kalkulasi skor kinerja BITI.
 * Skor akhir = gabungan berbobot dari kehadiran, capaian target, dan peer review 360° (skala 0–5).
 */
class ScoreCalculator
{
    private const HARI_KERJA = 26;

    public function __construct(private ?Setting $setting = null)
    {
        $this->setting ??= Setting::current();
    }

    public static function periodeSekarang(): string
    {
        return now()->format('Y-m');
    }

    /** Skor kehadiran 0–5, dikurangi penalti keterlambatan (maks -2) */
    public function skorKehadiran(User $user, string $periode): float
    {
        $rows = Attendance::where('user_id', $user->id)
            ->where('tanggal', 'like', $periode . '%')
            ->get();

        if ($rows->isEmpty()) {
            return 0.0;
        }

        $hadir = $rows->whereIn('status', ['hadir', 'telat'])->count();
        $persen = min(1, $hadir / self::HARI_KERJA);
        $menitTelat = $rows->sum('menit_telat');

        $skor = $persen * 5;
        $penalti = min($menitTelat / 30, 2);

        return round(max(0, min(5, $skor - $penalti)), 2);
    }

    /** Skor capaian target 0–5 */
    public function skorTarget(User $user, string $periode): float
    {
        $t = Target::where('user_id', $user->id)->where('periode', $periode)->first();

        if (! $t || (float) $t->target <= 0) {
            return 0.0;
        }

        return round(max(0, min(5, ((float) $t->realisasi / (float) $t->target) * 5)), 2);
    }

    /** Rata-rata peer review 0–5, null bila belum ada penilaian masuk */
    public function skorPeer(User $user, string $periode): ?float
    {
        $reviews = Review::where('reviewee_id', $user->id)->where('periode', $periode)->get();

        if ($reviews->isEmpty()) {
            return null;
        }

        return round($reviews->avg(fn (Review $r) => $r->rataRata()), 2);
    }

    /** Hitung seluruh komponen + skor akhir berbobot */
    public function hitung(User $user, ?string $periode = null): array
    {
        $periode ??= self::periodeSekarang();
        $s = $this->setting;

        $kehadiran = $this->skorKehadiran($user, $periode);
        $target = $this->skorTarget($user, $periode);
        $peer = $this->skorPeer($user, $periode);

        if ($peer === null) {
            $total = max(1, $s->bobot_kehadiran + $s->bobot_target);
            $akhir = ($kehadiran * $s->bobot_kehadiran + $target * $s->bobot_target) / $total;
        } else {
            $total = max(1, $s->bobot_kehadiran + $s->bobot_target + $s->bobot_peer);
            $akhir = ($kehadiran * $s->bobot_kehadiran + $target * $s->bobot_target + $peer * $s->bobot_peer) / $total;
        }

        return [
            'kehadiran' => $kehadiran,
            'target' => $target,
            'peer' => $peer,
            'akhir' => round($akhir, 2),
        ];
    }

    /** Peringkat seluruh karyawan, skor tertinggi di atas */
    public function peringkat(?string $periode = null): Collection
    {
        $periode ??= self::periodeSekarang();

        return User::where('role', 'karyawan')->get()
            ->map(fn (User $u) => ['user' => $u, 'skor' => $this->hitung($u, $periode)])
            ->sortByDesc(fn ($row) => $row['skor']['akhir'])
            ->values();
    }

    /** Insight otomatis: deteksi ketimpangan antar komponen */
    public function insight(User $user, ?string $periode = null): array
    {
        $s = $this->hitung($user, $periode);
        $out = [];

        if ($s['peer'] !== null && $s['target'] >= 4 && $s['peer'] < 2.8) {
            $out[] = ['tipe' => 'perhatian', 'teks' => "{$user->name} mencapai target kerja dengan baik, namun mendapat nilai rendah dari rekan kerja pada aspek penilaian 360°."];
        }
        if ($s['kehadiran'] > 0 && $s['kehadiran'] < 2.5) {
            $out[] = ['tipe' => 'perhatian', 'teks' => "Tingkat kehadiran dan keterlambatan {$user->name} perlu mendapat perhatian bulan ini."];
        }
        if ($s['peer'] !== null && $s['peer'] >= 4.3 && $s['target'] < 3) {
            $out[] = ['tipe' => 'perhatian', 'teks' => "{$user->name} dinilai sangat baik oleh rekan kerja, namun capaian target masih di bawah ekspektasi."];
        }
        if (empty($out) && $s['akhir'] >= 4) {
            $out[] = ['tipe' => 'baik', 'teks' => "Kinerja {$user->name} konsisten baik di seluruh aspek penilaian."];
        }
        if (empty($out)) {
            $out[] = ['tipe' => 'netral', 'teks' => 'Belum ada ketimpangan signifikan yang terdeteksi pada periode ini.'];
        }

        return $out;
    }

    /** Simpan/perbarui arsip skor bulanan seluruh karyawan */
    public function arsipkan(?string $periode = null): void
    {
        $periode ??= self::periodeSekarang();

        foreach (User::where('role', 'karyawan')->get() as $u) {
            $s = $this->hitung($u, $periode);
            MonthlyScore::updateOrCreate(
                ['user_id' => $u->id, 'periode' => $periode],
                [
                    'skor_kehadiran' => $s['kehadiran'],
                    'skor_target' => $s['target'],
                    'skor_peer' => $s['peer'],
                    'skor_akhir' => $s['akhir'],
                ]
            );
        }
    }

    /** Rata-rata skor perusahaan per bulan untuk grafik tren */
    public function tren(int $bulan = 6): Collection
    {
        return MonthlyScore::selectRaw('periode, AVG(skor_akhir) as rata')
            ->groupBy('periode')
            ->orderBy('periode')
            ->get()
            ->take(-$bulan);
    }
}
