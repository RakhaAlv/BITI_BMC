<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceImportController extends Controller
{
    public function create()
    {
        return view('admin.import');
    }

    /**
     * Impor rekap kehadiran dari CSV dengan kolom:
     * email, tanggal (YYYY-MM-DD), jam_masuk (HH:MM), jam_keluar (HH:MM)
     */
    public function store(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = array_map(fn ($h) => strtolower(trim($h)), fgetcsv($handle) ?: []);

        $wajib = ['email', 'tanggal', 'jam_masuk'];
        if (array_diff($wajib, $header)) {
            fclose($handle);
            return back()->with('error', 'Header CSV harus memuat kolom: email, tanggal, jam_masuk (opsional: jam_keluar).');
        }

        $berhasil = 0;
        $dilewati = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $r = array_combine($header, array_pad($row, count($header), null));
            $user = User::where('email', trim($r['email'] ?? ''))->where('role', 'karyawan')->first();

            if (! $user || empty($r['tanggal']) || empty($r['jam_masuk'])) {
                $dilewati++;
                continue;
            }

            try {
                $masuk = Carbon::createFromFormat('H:i', substr(trim($r['jam_masuk']), 0, 5));
                $batas = Carbon::createFromTime(8, 0);
                $menit = max(0, $batas->diffInMinutes($masuk, false));
                $tanggal = Carbon::parse($r['tanggal'])->format('Y-m-d');
            } catch (\Throwable $e) {
                $dilewati++;
                continue;
            }

            Attendance::updateOrCreate(
                ['user_id' => $user->id, 'tanggal' => $tanggal],
                [
                    'jam_masuk' => $masuk->format('H:i:s'),
                    'jam_keluar' => ! empty($r['jam_keluar']) ? substr(trim($r['jam_keluar']), 0, 5) . ':00' : null,
                    'status' => $menit > 0 ? 'telat' : 'hadir',
                    'menit_telat' => (int) $menit,
                    'sumber' => 'impor',
                ]
            );
            $berhasil++;
        }
        fclose($handle);

        return back()->with('success', "Impor selesai: {$berhasil} baris berhasil, {$dilewati} baris dilewati.");
    }
}
