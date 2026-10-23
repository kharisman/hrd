<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · HRD ITB PalComTech</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .sidebar { width: 240px; }
        .sidebar .nav-link { color: #cfd8e3; border-radius: .375rem; }
        .sidebar .nav-link:hover { background: rgba(255,255,255,.08); color: #fff; }
        .sidebar .nav-link.active { background: #0d6efd; color: #fff; }
        /* Bar filter di atas tabel/daftar: satu gaya untuk semua halaman */
        .bar-filter { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; padding: .75rem 1rem; background: #fff; border-bottom: 1px solid var(--bs-border-color); border-radius: var(--bs-card-inner-border-radius) var(--bs-card-inner-border-radius) 0 0; }
        .bar-filter > .form-select { width: auto; flex: 1 1 150px; max-width: 220px; }
        .bar-filter > .input-group { flex: 2 1 220px; max-width: 320px; }
        .bar-filter > .form-check { margin: 0 .25rem; }
        .bar-filter > .info { margin-left: auto; font-size: .875rem; color: var(--bs-secondary-color); white-space: nowrap; }

        /* Dashboard: kartu angka & grafik batang horizontal */
        .kartu { transition: box-shadow .15s; }
        .kartu:hover { box-shadow: 0 .25rem .75rem rgba(0,0,0,.08); }
        .kartu .ikon { width: 44px; height: 44px; border-radius: .5rem; display: grid; place-items: center; font-size: 1.25rem; background: #e7f0ff; color: #0d6efd; }
        .batang { display: grid; grid-template-columns: minmax(90px, 38%) 1fr 36px; align-items: center; gap: .5rem; padding: 3px 0; font-size: .875rem; }
        .batang:hover { background: #f8f9fa; }
        .batang-jalur { background: #f1f3f5; border-radius: 4px; height: 12px; }
        .batang-isi { background: #0d6efd; height: 100%; border-radius: 4px; min-width: 2px; }
        .batang-isi.kosong { background: #adb5bd; }
        .batang-nilai { text-align: right; font-variant-numeric: tabular-nums; color: #495057; }
        @media (min-width: 992px) { .sidebar { position: fixed; top: 0; bottom: 0; } .content { margin-left: 240px; } }

        /* Bilah progres AJAX (hrd.js) */
        #progres { position: fixed; top: 0; left: 0; height: 3px; width: 0; opacity: 0; z-index: 2000; background: #0d6efd;
                   box-shadow: 0 0 8px rgba(13,110,253,.6); transition: width .3s ease, opacity .3s; }
        /* Skeleton loading (komponen x-skeleton) */
        .sk { background: linear-gradient(90deg, #eceff3 25%, #f6f7f9 37%, #eceff3 63%); background-size: 400% 100%;
              animation: sk-kilau 1.2s ease infinite; border-radius: 4px; }
        .sk-teks { height: 12px; } .sk-teks-kecil { height: 9px; } .sk-judul { height: 22px; }
        .sk-input { height: 34px; flex: 1 1 150px; max-width: 220px; } .sk-tombol { height: 30px; width: 64px; }
        @keyframes sk-kilau { 0% { background-position: 100% 50%; } 100% { background-position: 0 50%; } }
        @media (prefers-reduced-motion: reduce) { .sk { animation: none; } }
    </style>
</head>
<body class="bg-light">
<div id="progres" role="progressbar" aria-label="Memuat"></div>
@php
    $admin = Gate::allows('admin');
    $menu = array_filter([
        ['dashboard', 'dashboard', 'speedometer2', 'Dashboard'],
        ['staff.index', ['staff.index', 'staff.show', 'staff.create', 'staff.edit'], 'people', $admin ? 'Data Pegawai' : 'Profil Saya'],
        $admin ? ['staff.atasan', 'staff.atasan*', 'diagram-3', 'Atasan & Bawahan'] : null,
        $admin ? ['dosen.index', 'dosen*', 'mortarboard', 'Data Dosen'] : null,
        $admin ? ['riwayat.index', 'riwayat*', 'journal-bookmark', 'Studi Lanjut'] : null,
        ['cuti.index', 'cuti.*', 'calendar-check', 'Cuti & Izin'],
        ['pengajuan.index', 'pengajuan.*', 'ui-checks', 'Pengajuan'],
    ]);
    // Grup yang bisa dilipat: [id, judul, ikon, route induk, item]
    // Item aktif: route-nya sendiri; untuk *.index juga semua turunannya (mis. jenis-cuti.*)
    $aktif = fn ($r) => request()->routeIs($r) || (str_ends_with($r, '.index') && request()->routeIs(\Illuminate\Support\Str::beforeLast($r, '.') . '.*'));
    $grup = $admin ? [
        ['grupLaporan', 'Laporan', 'file-earmark-bar-graph', 'laporan.index', \App\Support\Menu::laporan()],
        ['grupPengaturan', 'Pengaturan', 'gear', 'pengaturan.index', \App\Support\Menu::pengaturan()],
    ] : [];
@endphp

{{-- Offcanvas: sidebar tetap di desktop, laci di mobile --}}
<aside class="sidebar offcanvas-lg offcanvas-start bg-dark text-white d-flex flex-column" tabindex="-1" id="sidebar">
    <div class="p-3 d-flex align-items-center gap-2 border-bottom border-secondary">
        <img src="{{ asset('logo-pct1.png') }}" height="28" alt="" class="bg-white rounded p-1">
        <span class="fw-semibold">HRD PalComTech</span>
        <button class="btn-close btn-close-white ms-auto d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar"></button>
    </div>
    {{-- flex-nowrap: tanpa ini .nav melipat ke kolom kedua saat menu lebih tinggi dari layar --}}
    <nav class="nav flex-column flex-nowrap p-2 gap-1 flex-grow-1 overflow-y-auto overflow-x-hidden" id="menu" style="min-height:0">
        @foreach ($menu as [$route, $pattern, $icon, $label])
            <a href="{{ route($route) }}" class="nav-link {{ request()->routeIs($pattern) ? 'active' : '' }}">
                <i class="bi bi-{{ $icon }} me-2"></i>{{ $label }}
            </a>
        @endforeach

        @foreach ($grup as [$id, $judul, $ikon, $induk, $item])
            @php($buka = request()->routeIs($induk) || collect($item)->contains(fn ($m) => $aktif($m[0])))
            <a class="nav-link d-flex align-items-center {{ $buka ? 'text-white' : '' }}" data-bs-toggle="collapse" href="#{{ $id }}" role="button" aria-expanded="{{ $buka ? 'true' : 'false' }}">
                <i class="bi bi-{{ $ikon }} me-2"></i>{{ $judul }}<i class="bi bi-chevron-down ms-auto small"></i>
            </a>
            <div class="collapse {{ $buka ? 'show' : '' }}" id="{{ $id }}">
                <nav class="nav flex-column gap-1 ms-3 mb-1">
                    @foreach ($item as [$route, $icon, $label])
                        <a href="{{ route($route) }}" class="nav-link py-1 small {{ $aktif($route) ? 'active' : '' }}">
                            <i class="bi bi-{{ $icon }} me-2"></i>{{ $label }}
                        </a>
                    @endforeach
                </nav>
            </div>
        @endforeach
    </nav>
    <div class="p-3 border-top border-secondary small">
        <div class="fw-semibold">{{ auth()->user()->name }}</div>
        <div class="text-white-50 mb-2">{{ ['admin' => 'Admin HRD', 'dosen' => 'Dosen', 'staff' => 'Staf'][auth()->user()->peran] }}</div>
        <a href="{{ route('telegram-saya') }}" class="btn btn-sm btn-outline-light w-100 mb-2"><i class="bi bi-telegram me-1"></i>Telegram Saya</a>
        <form method="post" action="{{ route('logout') }}" data-no-ajax>@csrf<button class="btn btn-sm btn-outline-light w-100"><i class="bi bi-box-arrow-right me-1"></i>Keluar</button></form>
    </div>
</aside>

<div class="content">
    <nav class="navbar bg-white border-bottom d-lg-none mb-3">
        <div class="container-fluid">
            <button class="btn" data-bs-toggle="offcanvas" data-bs-target="#sidebar"><i class="bi bi-list fs-4"></i></button>
            <span class="fw-semibold">HRD PalComTech</span>
            <span></span>
        </div>
    </nav>
    {{-- #halaman = bagian yang ditukar oleh hrd.js saat navigasi/submit AJAX --}}
    <div id="halaman">
    <header class="bg-white border-bottom px-3 px-lg-4 py-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none"><i class="bi bi-house-door"></i> Beranda</a></li>
                    @yield('breadcrumb')
                    @hasSection('title')<li class="breadcrumb-item active" aria-current="page">@yield('title')</li>@endif
                </ol>
            </nav>
            <h1 class="h4 mb-0">@yield('title', 'Dashboard')</h1>
        </div>
        <div class="d-flex flex-wrap gap-2">@yield('actions')</div>
    </header>
    <main class="container-fluid p-3 p-lg-4">
        @if (session('ok'))<div class="alert alert-success">{{ session('ok') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        @yield('content')
    </main>
    </div>
</div>

<template id="tplSkeleton"><x-skeleton /></template>
<x-toast />
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/hrd.js') }}?v={{ filemtime(public_path('js/hrd.js')) }}"></script>
</body>
</html>
