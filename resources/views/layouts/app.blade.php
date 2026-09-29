<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="app-shell"><div class="min-h-screen md:flex"><aside class="app-sidebar w-full md:w-56"><div class="app-brand"><span class="app-brand-mark">●</span> BITI</div><nav class="app-nav">@if(auth()->user()->role==='admin')<a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">⌂ <span>Dashboard</span></a><a class="{{ request()->routeIs('admin.employees.*') ? 'active' : '' }}" href="{{ route('admin.employees.index') }}">♙ <span>Karyawan</span></a><a class="{{ request()->routeIs('admin.targets.*') ? 'active' : '' }}" href="{{ route('admin.targets.index') }}">▤ <span>Target</span></a><a class="{{ request()->routeIs('admin.import.*') ? 'active' : '' }}" href="{{ route('admin.import.create') }}">↥ <span>Impor Absensi</span></a><a class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">▥ <span>Laporan</span></a><a class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.edit') }}">⚙ <span>Pengaturan</span></a>@else<a class="{{ request()->routeIs('karyawan.dashboard') ? 'active' : '' }}" href="{{ route('karyawan.dashboard') }}">⌂ <span>Dashboard</span></a><a class="{{ request()->routeIs('karyawan.reviews.*') ? 'active' : '' }}" href="{{ route('karyawan.reviews.index') }}">◎ <span>Survei Rekan</span></a><a class="{{ request()->routeIs('karyawan.feedback.*') ? 'active' : '' }}" href="{{ route('karyawan.feedback.index') }}">▣ <span>Feedback</span></a>@endif</nav><form method="POST" action="{{ route('logout') }}" class="app-logout">@csrf<button><span>Logout</span></button></form></aside><main class="app-main flex-1"><header class="app-topbar"><div class="app-search"> <span></span></div><div class="app-top-actions"><span class="app-user">{{ auth()->user()->name }}</span></div></header><section class="app-content max-w-7xl">@if(session('success'))<div class="mb-5 rounded-lg bg-emerald-100 p-3 text-emerald-800">{{ session('success') }}</div>@endif @if(session('error'))<div class="mb-5 rounded-lg bg-red-100 p-3 text-red-800">{{ session('error') }}</div>@endif

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </section></main></div></body>
</html>
