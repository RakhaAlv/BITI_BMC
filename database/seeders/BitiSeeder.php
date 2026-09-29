<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Feedback;
use App\Models\MonthlyScore;
use App\Models\Review;
use App\Models\ReviewAssignment;
use App\Models\Setting;
use App\Models\Target;
use App\Models\User;
use App\Services\ScoreCalculator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BitiSeeder extends Seeder
{
    public function run(): void
    {
        Setting::create([
            'nama_usaha' => 'Toko Kopi Rasa',
            'tier' => 'growth',
            'kuota_karyawan' => 20,
            'bobot_kehadiran' => 40,
            'bobot_target' => 30,
            'bobot_peer' => 30,
            'maintenance_aktif' => true,
            'absen_mandiri_aktif' => true,
        ]);

        $admin = User::create([
            'name' => 'admin',
            'email' => 'admin@gmail.com',
            'password' => 'password',
            'role' => 'admin',
            'jabatan' => 'Owner',
            'divisi' => 'Manajemen',
            'tanggal_bergabung' => '2021-01-01',
        ]);

        $data = [
            ['Naila Luluah', 'naila@gmail.com', 'Kasir', 'Operasional', '2023-02-01'],
            ['Rakha Aljiva', 'rakha@gmail.com', 'Barista', 'Operasional', '2022-11-15'],
            ['Siti Nur Aini', 'siti@gmail.com', 'Supervisor', 'Manajemen', '2021-06-10'],
            ['Bagas Wirawan', 'bagas@gmail.com', 'Barista', 'Operasional', '2023-08-20'],
            ['Melati Putri', 'melati@gmail.com', 'Marketing', 'Pemasaran', '2022-01-05'],
            ['Fajar Hidayat', 'fajar@gmail.com', 'Kasir', 'Operasional', '2024-01-10'],
        ];

        $karyawan = collect($data)->map(fn ($d) => User::create([
            'name' => $d[0], 'email' => $d[1], 'password' => 'password', 'role' => 'karyawan',
            'jabatan' => $d[2], 'divisi' => $d[3], 'tanggal_bergabung' => $d[4],
        ]));

        // [peluang hadir, rata menit telat, realisasi target %, rating peer]
        $profil = [
            [0.96, 4, 97, 4.6], [0.88, 12, 80, 3.2], [0.99, 0, 105, 4.7],
            [0.91, 6, 112, 2.4], [0.85, 14, 70, 3.8], [0.94, 3, 99, 4.0],
        ];

        $calc = new ScoreCalculator(Setting::current());
        $bulan = collect(range(5, 0))->map(fn ($i) => Carbon::now()->startOfMonth()->subMonths($i));

        foreach ($bulan as $idx => $tgl) {
            $periode = $tgl->format('Y-m');
            $adalahBulanIni = $tgl->isSameMonth(now());

            foreach ($karyawan as $i => $k) {
                [$pHadir, $telat, $real, $peer] = $profil[$i];
                $variasi = ($idx - 2) * 1.5;

                $hari = $adalahBulanIni ? now()->day : $tgl->daysInMonth;
                for ($d = 1; $d <= $hari; $d++) {
                    $tanggal = $tgl->copy()->day($d);
                    if ($tanggal->isSunday() || $tanggal->isFuture() || $tanggal->isToday()) {
                        continue;
                    }
                    if (mt_rand(0, 1000) / 1000 > $pHadir) {
                        Attendance::create([
                            'user_id' => $k->id, 'tanggal' => $tanggal->format('Y-m-d'), 'status' => 'alpa',
                            'menit_telat' => 0, 'sumber' => 'manual',
                        ]);
                        continue;
                    }
                    $menit = mt_rand(0, 100) < 25 ? mt_rand(3, $telat + 8) : 0;
                    $masuk = Carbon::createFromTime(8, 0)->addMinutes($menit);
                    Attendance::create([
                        'user_id' => $k->id, 'tanggal' => $tanggal->format('Y-m-d'),
                        'jam_masuk' => $masuk->format('H:i:s'),
                        'jam_keluar' => '17:00:00',
                        'status' => $menit > 0 ? 'telat' : 'hadir',
                        'menit_telat' => $menit,
                        'sumber' => $i % 2 === 0 ? 'mandiri' : 'impor',
                    ]);
                }

                Target::create([
                    'user_id' => $k->id, 'periode' => $periode, 'jenis' => 'Penjualan',
                    'target' => 100, 'realisasi' => max(40, min(125, $real + $variasi)),
                ]);

                $penilai = $karyawan->where('id', '!=', $k->id)->shuffle()->take(2);
                foreach ($penilai as $p) {
                    $n = fn () => (int) max(1, min(5, round($peer + mt_rand(-8, 8) / 10)));
                    Review::create([
                        'periode' => $periode, 'reviewer_id' => $p->id, 'reviewee_id' => $k->id,
                        'komunikasi' => $n(), 'inisiatif' => $n(), 'kepatuhan_sop' => $n(), 'kerjasama' => $n(),
                        'catatan' => $this->catatan($peer),
                    ]);
                }
            }

            $calc->arsipkan($periode);
        }

        // Penugasan penilaian bulan ini (belum diisi, agar karyawan bisa mencoba)
        $periodeIni = now()->format('Y-m');
        ReviewAssignment::where('periode', $periodeIni)->delete();
        Review::where('periode', $periodeIni)->delete();

        foreach ($karyawan as $k) {
            $target = $karyawan->where('id', '!=', $k->id);
            foreach ($target as $t) {
                ReviewAssignment::create([
                    'periode' => $periodeIni, 'reviewer_id' => $k->id,
                    'reviewee_id' => $t->id, 'selesai' => false,
                ]);
            }
        }

        Feedback::create([
            'user_id' => $karyawan[2]->id, 'admin_id' => $admin->id,
            'periode' => now()->subMonth()->format('Y-m'),
            'jenis' => 'apresiasi', 'isi' => 'Konsistensi dan kepemimpinanmu di tim sangat membantu. Pertahankan.',
        ]);
        Feedback::create([
            'user_id' => $karyawan[3]->id, 'admin_id' => $admin->id,
            'periode' => now()->subMonth()->format('Y-m'),
            'jenis' => 'arahan', 'isi' => 'Capaian targetmu sangat baik. Mari tingkatkan koordinasi dengan rekan shift lain.',
        ]);
        Feedback::create([
            'user_id' => $karyawan[1]->id, 'admin_id' => $admin->id,
            'periode' => now()->format('Y-m'),
            'jenis' => 'apresiasi', 'isi' => 'Kinerja kamu stabil selama enam bulan terakhir. Pertahankan konsistensi dan kualitas pelayanan.',
        ]);
        Feedback::create([
            'user_id' => $karyawan[1]->id, 'admin_id' => $admin->id,
            'periode' => now()->subMonths(2)->format('Y-m'),
            'jenis' => 'arahan', 'isi' => 'Coba tingkatkan capaian target secara bertahap pada periode berikutnya.',
        ]);


        $calc->arsipkan($periodeIni);
    }

    private function catatan(float $rating): string
    {
        $bagus = ['Komunikatif dan selalu membantu rekan.', 'Inisiatif tinggi dan patuh SOP.', 'Kerja sama tim sangat baik.'];
        $sedang = ['Cukup baik, masih bisa ditingkatkan koordinasinya.', 'Kinerja stabil namun kurang proaktif.'];
        $kurang = ['Sering bekerja sendiri dan jarang berkoordinasi.', 'Perlu memperbaiki kedisiplinan dan komunikasi.'];

        $pool = $rating >= 4 ? $bagus : ($rating >= 3 ? $sedang : $kurang);
        return $pool[array_rand($pool)];
    }
}
