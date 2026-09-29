<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Karyawan;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

// Pintu masuk: arahkan ke dashboard sesuai role
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ──────────── ADMIN / PEMILIK USAHA ────────────
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/karyawan', [Admin\EmployeeController::class, 'index'])->name('employees.index');
    Route::post('/karyawan', [Admin\EmployeeController::class, 'store'])->name('employees.store');
    Route::get('/karyawan/{user}', [Admin\EmployeeController::class, 'show'])->name('employees.show');
    Route::post('/karyawan/{user}/feedback', [Admin\EmployeeController::class, 'storeFeedback'])->name('employees.feedback');

    Route::get('/target', [Admin\TargetController::class, 'index'])->name('targets.index');
    Route::post('/target', [Admin\TargetController::class, 'update'])->name('targets.update');

    Route::get('/impor-absensi', [Admin\AttendanceImportController::class, 'create'])->name('import.create');
    Route::post('/impor-absensi', [Admin\AttendanceImportController::class, 'store'])->name('import.store');

    Route::get('/laporan', [Admin\ReportController::class, 'index'])->name('reports.index');
    Route::get('/laporan/ekspor', [Admin\ReportController::class, 'exportTim'])->name('reports.export');
    Route::get('/laporan/karyawan/{user}', [Admin\ReportController::class, 'exportKaryawan'])->name('reports.export.employee');

    Route::get('/pengaturan', [Admin\SettingController::class, 'edit'])->name('settings.edit');
    Route::post('/pengaturan/bobot', [Admin\SettingController::class, 'updateBobot'])->name('settings.bobot');
    Route::post('/pengaturan/paket', [Admin\SettingController::class, 'updateTier'])->name('settings.tier');
    Route::post('/pengaturan/maintenance', [Admin\SettingController::class, 'toggleMaintenance'])->name('settings.maintenance');
    Route::post('/pengaturan/absen-mandiri', [Admin\SettingController::class, 'toggleAbsenMandiri'])->name('settings.absen');
});

// ──────────── KARYAWAN ────────────
Route::middleware(['auth', 'role:karyawan'])->prefix('karyawan')->name('karyawan.')->group(function () {
    Route::get('/', [Karyawan\DashboardController::class, 'index'])->name('dashboard');

    Route::post('/absen/masuk', [Karyawan\AttendanceController::class, 'checkIn'])->name('absen.masuk');
    Route::post('/absen/keluar', [Karyawan\AttendanceController::class, 'checkOut'])->name('absen.keluar');

    Route::get('/survei', [Karyawan\ReviewController::class, 'index'])->name('reviews.index');
    Route::post('/survei/{assignment}', [Karyawan\ReviewController::class, 'store'])->name('reviews.store');

    Route::get('/feedback', [Karyawan\FeedbackController::class, 'index'])->name('feedback.index');
    Route::get('/survei/{assignment}', [Karyawan\ReviewController::class, 'show'])->name('reviews.show');
});

require __DIR__.'/auth.php';
