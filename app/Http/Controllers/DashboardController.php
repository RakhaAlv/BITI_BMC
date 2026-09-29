<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    /** Arahkan pengguna ke dashboard sesuai role */
    public function index(): RedirectResponse
    {
        return auth()->user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('karyawan.dashboard');
    }
}
