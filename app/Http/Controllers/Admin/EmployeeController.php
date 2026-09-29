<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Feedback;
use App\Models\MonthlyScore;
use App\Models\Review;
use App\Models\Setting;
use App\Models\Target;
use App\Models\User;
use App\Services\ScoreCalculator;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(ScoreCalculator $calc)
    {
        $periode = ScoreCalculator::periodeSekarang();
        $setting = Setting::current();
        $jumlah = User::where('role', 'karyawan')->count();

        $rows = User::where('role', 'karyawan')->orderBy('name')->get()->map(function (User $u) use ($calc, $periode) {
            $t = Target::where('user_id', $u->id)->where('periode', $periode)->first();
            $hadir = Attendance::where('user_id', $u->id)->where('tanggal', 'like', $periode . '%')
                ->whereIn('status', ['hadir', 'telat'])->count();

            return [
                'user' => $u,
                'hadir' => $hadir,
                'target' => $t ? round(($t->realisasi / max(1, $t->target)) * 100) : null,
                'skor' => $calc->hitung($u, $periode),
            ];
        });

        $riwayat = MonthlyScore::whereIn('user_id', $rows->pluck('user.id'))
            ->orderByDesc('periode')->get()->groupBy('user_id');
        $rows->each(fn (array $row) => $row['user']->setRelation(
            'monthlyScores', $riwayat->get($row['user']->id, collect())->take(6)->sortBy('periode')->values()
        ));

        return view('admin.employees.index', [
            'rows' => $rows,
            'setting' => $setting,
            'jumlah' => $jumlah,
            'penuh' => $jumlah >= $setting->kuota_karyawan,
        ]);
    }

    public function store(Request $request)
    {
        $setting = Setting::current();

        if (User::where('role', 'karyawan')->count() >= $setting->kuota_karyawan) {
            return back()->with('error', 'Kapasitas akun penuh. Hubungi tim BITI untuk menambah kuota karyawan.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'jabatan' => ['required', 'string', 'max:100'],
            'divisi' => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        User::create($data + ['role' => 'karyawan', 'tanggal_bergabung' => now()]);

        return back()->with('success', 'Karyawan berhasil ditambahkan.');
    }

    public function show(User $user, ScoreCalculator $calc)
    {
        abort_unless($user->role === 'karyawan', 404);

        $periode = ScoreCalculator::periodeSekarang();

        return view('admin.employees.show', [
            'user' => $user,
            'periode' => $periode,
            'skor' => $calc->hitung($user, $periode),
            'insight' => $calc->insight($user, $periode),
            'reviews' => Review::where('reviewee_id', $user->id)->where('periode', $periode)->get(),
            'feedbacks' => Feedback::where('user_id', $user->id)->latest()->get(),
            'riwayat' => $user->monthlyScores()->orderBy('periode')->get(),
        ]);
    }

    public function storeFeedback(Request $request, User $user)
    {
        abort_unless($user->role === 'karyawan', 404);

        $data = $request->validate([
            'jenis' => ['required', 'in:apresiasi,arahan'],
            'isi' => ['required', 'string', 'max:1000'],
        ]);

        Feedback::create($data + [
            'user_id' => $user->id,
            'admin_id' => $request->user()->id,
            'periode' => ScoreCalculator::periodeSekarang(),
        ]);

        return back()->with('success', 'Catatan feedback berhasil dikirim ke karyawan.');
    }
}
