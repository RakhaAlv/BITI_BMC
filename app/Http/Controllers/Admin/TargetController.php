<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Target;
use App\Models\User;
use App\Services\ScoreCalculator;
use Illuminate\Http\Request;

class TargetController extends Controller
{
    public function index()
    {
        $periode = ScoreCalculator::periodeSekarang();

        return view('admin.targets', [
            'periode' => $periode,
            'karyawan' => User::where('role', 'karyawan')->orderBy('name')->get(),
            'targets' => Target::where('periode', $periode)->get()->keyBy('user_id'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'target' => ['required', 'array'],
            'target.*' => ['nullable', 'numeric', 'min:0'],
            'realisasi' => ['required', 'array'],
            'realisasi.*' => ['nullable', 'numeric', 'min:0'],
            'jenis' => ['nullable', 'string', 'max:100'],
        ]);

        $periode = ScoreCalculator::periodeSekarang();

        foreach ($data['target'] as $userId => $nilaiTarget) {
            if ($nilaiTarget === null || $nilaiTarget === '') {
                continue;
            }
            Target::updateOrCreate(
                ['user_id' => $userId, 'periode' => $periode],
                [
                    'jenis' => $data['jenis'] ?? 'Penjualan',
                    'target' => $nilaiTarget,
                    'realisasi' => $data['realisasi'][$userId] ?? 0,
                ]
            );
        }

        return back()->with('success', 'Capaian target berhasil disimpan.');
    }
}
