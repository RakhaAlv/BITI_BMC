<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'periode', 'skor_kehadiran', 'skor_target', 'skor_peer', 'skor_akhir'])]
class MonthlyScore extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
