<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'nama_usaha', 'tier', 'kuota_karyawan',
    'bobot_kehadiran', 'bobot_target', 'bobot_peer',
    'maintenance_aktif', 'absen_mandiri_aktif',
])]
class Setting extends Model
{
    protected function casts(): array
    {
        return [
            'maintenance_aktif' => 'boolean',
            'absen_mandiri_aktif' => 'boolean',
        ];
    }

    /** Ambil pengaturan tunggal, buat otomatis jika belum ada */
    public static function current(): self
    {
        return static::first() ?? static::create([]);
    }

    public function tierLabel(): string
    {
        return ['starter' => 'Starter', 'growth' => 'Growth', 'scale' => 'Scale'][$this->tier] ?? ucfirst($this->tier);
    }
}
