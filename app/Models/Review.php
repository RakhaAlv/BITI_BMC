<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['periode', 'reviewer_id', 'reviewee_id', 'komunikasi', 'inisiatif', 'kepatuhan_sop', 'kerjasama', 'catatan'])]
class Review extends Model
{
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }

    /** Rata-rata keempat indikator (skala 1–5) */
    public function rataRata(): float
    {
        return ($this->komunikasi + $this->inisiatif + $this->kepatuhan_sop + $this->kerjasama) / 4;
    }
}
