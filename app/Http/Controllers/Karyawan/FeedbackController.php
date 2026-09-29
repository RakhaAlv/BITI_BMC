<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Review;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        return view('karyawan.feedback', [
            'feedbacks' => Feedback::where('user_id', $request->user()->id)->latest()->get(),
            'catatanRekan' => Review::with('reviewer')->where('reviewee_id', $request->user()->id)
                ->whereNotNull('catatan')->where('catatan', '!=', '')->latest()->get(),
        ]);
    }
}
