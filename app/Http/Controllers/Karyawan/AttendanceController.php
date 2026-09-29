<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    private const JAM_MASUK_STANDAR = '08:00';

    public function checkIn(Request $request)
    {
        if (! Setting::current()->absen_mandiri_aktif) {
            return back()->with('error', 'Absen mandiri tidak diaktifkan oleh admin.');
        }

        $user = $request->user();
        $sudah = Attendance::where('user_id', $user->id)->whereDate('tanggal', today())->first();

        if ($sudah && $sudah->jam_masuk) {
            return back()->with('error', 'Anda sudah check-in hari ini.');
        }

        $sekarang = now();
        $batas = Carbon::today()->setTimeFromTimeString(self::JAM_MASUK_STANDAR);
        $menit = $sekarang->greaterThan($batas) ? (int) $batas->diffInMinutes($sekarang) : 0;

        Attendance::updateOrCreate(
            ['user_id' => $user->id, 'tanggal' => today()],
            [
                'jam_masuk' => $sekarang->format('H:i:s'),
                'status' => $menit > 0 ? 'telat' : 'hadir',
                'menit_telat' => $menit,
                'sumber' => 'mandiri',
            ]
        );

        return back()->with('success', 'Check-in berhasil pada ' . $sekarang->format('H:i') . '.');
    }

    public function checkOut(Request $request)
    {
        $absen = Attendance::where('user_id', $request->user()->id)->whereDate('tanggal', today())->first();

        if (! $absen || ! $absen->jam_masuk) {
            return back()->with('error', 'Anda belum check-in hari ini.');
        }
        if ($absen->jam_keluar) {
            return back()->with('error', 'Anda sudah check-out hari ini.');
        }

        $absen->update(['jam_keluar' => now()->format('H:i:s')]);

        return back()->with('success', 'Check-out berhasil pada ' . now()->format('H:i') . '.');
    }
}
