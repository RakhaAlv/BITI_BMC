<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    private const KUOTA = ['starter' => 10, 'growth' => 20, 'scale' => 999];

    public function edit()
    {
        return view('admin.settings', [
            'setting' => Setting::current(),
            'jumlah' => User::where('role', 'karyawan')->count(),
        ]);
    }

    public function updateBobot(Request $request)
    {
        $data = $request->validate([
            'bobot_kehadiran' => ['required', 'integer', 'min:0', 'max:100'],
            'bobot_target' => ['required', 'integer', 'min:0', 'max:100'],
            'bobot_peer' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $total = array_sum($data);
        if ($total === 0) {
            return back()->with('error', 'Total bobot tidak boleh 0.');
        }

        $norm = array_map(fn ($v) => (int) round($v / $total * 100), $data);
        Setting::current()->update($norm);

        return back()->with('success', 'Bobot penilaian berhasil disimpan.');
    }

    public function updateTier(Request $request)
    {
        $data = $request->validate(['tier' => ['required', 'in:starter,growth,scale']]);
        $kuota = self::KUOTA[$data['tier']];

        if (User::where('role', 'karyawan')->count() > $kuota) {
            return back()->with('error', 'Tidak dapat mengubah paket: jumlah karyawan melebihi kuota paket tersebut.');
        }

        Setting::current()->update(['tier' => $data['tier'], 'kuota_karyawan' => $kuota]);

        return back()->with('success', 'Paket berhasil diperbarui.');
    }

    public function toggleMaintenance()
    {
        $s = Setting::current();
        $s->update(['maintenance_aktif' => ! $s->maintenance_aktif]);

        return back()->with('success', 'Status maintenance diperbarui.');
    }

    public function toggleAbsenMandiri()
    {
        $s = Setting::current();
        $s->update(['absen_mandiri_aktif' => ! $s->absen_mandiri_aktif]);

        return back()->with('success', 'Pengaturan absen mandiri diperbarui.');
    }
}
