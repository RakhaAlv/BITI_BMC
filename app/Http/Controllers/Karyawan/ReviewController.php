<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\ReviewAssignment;
use App\Models\MonthlyScore;
use App\Services\ScoreCalculator;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $tugas = ReviewAssignment::with('reviewee')
            ->where('reviewer_id', $request->user()->id)
            ->where('periode', ScoreCalculator::periodeSekarang())
            ->where('selesai', false)
            ->get();

        $riwayat = MonthlyScore::whereIn('user_id', $tugas->pluck('reviewee_id'))
            ->orderByDesc('periode')->get()->groupBy('user_id');

        $tugas->each(fn (ReviewAssignment $tugas) => $tugas->reviewee->setRelation(
            'monthlyScores', $riwayat->get($tugas->reviewee_id, collect())->take(6)->sortBy('periode')->values()
        ));

        return view('karyawan.reviews', ['tugas' => $tugas]);
    }

    public function show(Request $request, ReviewAssignment $assignment)
    {
        abort_unless($assignment->reviewer_id === $request->user()->id && ! $assignment->selesai, 403);

        return view('karyawan.review-form', ['tugas' => $assignment->load('reviewee')]);
    }

    public function store(Request $request, ReviewAssignment $assignment, ScoreCalculator $calc)
    {
        abort_unless($assignment->reviewer_id === $request->user()->id && ! $assignment->selesai, 403);

        $data = $request->validate([
            'komunikasi' => ['required', 'integer', 'between:1,5'],
            'inisiatif' => ['required', 'integer', 'between:1,5'],
            'kepatuhan_sop' => ['required', 'integer', 'between:1,5'],
            'kerjasama' => ['required', 'integer', 'between:1,5'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        Review::updateOrCreate(
            ['periode' => $assignment->periode, 'reviewer_id' => $assignment->reviewer_id, 'reviewee_id' => $assignment->reviewee_id],
            $data
        );
        $assignment->update(['selesai' => true]);

        $calc->arsipkan($assignment->periode);

        return redirect()->route('karyawan.reviews.index')->with('success', 'Penilaian berhasil dikirim. Terima kasih!');
    }
}
