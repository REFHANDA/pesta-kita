<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', 'Panel Vendor · Pesta Kita')</title>
    <link rel="icon" href="{{ asset('assets/favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('assets/css/design-tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/penyedia.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/penyedia-lanjutan.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/keselarasan.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/password-toggle.css') }}">
    @stack('styles')
</head>
<body>
    <a class="skip" href="#konten">Lewati ke konten</a>
    <div class="app">
        <aside class="sidebar" aria-label="Navigasi utama Vendor" id="main-navigation">
            <button type="button" class="sidebar-close" aria-label="Tutup menu navigasi"><span aria-hidden="true">×</span></button>
            <a class="brand" href="{{ url('/vendor') }}">
                <span class="logo" aria-hidden="true"></span>
                <span><b>Pesta Kita</b><small>Panel Vendor</small></span>
            </a>
            <span class="role-chip">Aktor: Vendor</span>

            <nav class="nav" aria-label="Menu utama">
                <a href="{{ url('/vendor') }}" @if(request()->is('vendor') || request()->is('vendor/beranda')) aria-current="page" @endif>
                    <span class="ic i-home" aria-hidden="true"></span>Beranda
                </a>
            </nav>

            <p class="nav-label">Katalog &amp; Layanan</p>
            <nav class="nav" aria-label="Katalog saya">
                <a href="{{ url('/vendor/layanan') }}" @if(request()->is('vendor/layanan*') || request()->is('vendor/item-form*')) aria-current="page" @endif>
                    <span class="ic i-box" aria-hidden="true"></span>Layanan Saya
                </a>
                <a href="{{ url('/vendor/peralatan') }}" @if(request()->is('vendor/peralatan*') || request()->is('vendor/peralatan-form*')) aria-current="page" @endif>
                    <span class="ic i-box" aria-hidden="true"></span>Peralatan Sewa
                </a>
                <a href="{{ url('/vendor/pengajuan-harga') }}" @if(request()->is('vendor/pengajuan-harga*')) aria-current="page" @endif>
                    <span class="ic i-tag" aria-hidden="true"></span>Pengajuan Harga
                </a>
                <a href="{{ url('/vendor/permintaan-penawaran') }}" @if(request()->is('vendor/permintaan-penawaran*')) aria-current="page" @endif>
                    <span class="ic i-bill" aria-hidden="true"></span>Permintaan Penawaran
                </a>
            </nav>

            <p class="nav-label">Operasional Acara</p>
            <nav class="nav" aria-label="Operasional">
                <a href="{{ url('/vendor/pesanan') }}" @if(request()->is('vendor/pesanan*')) aria-current="page" @endif>
                    <span class="ic i-clip" aria-hidden="true"></span>Pesanan Masuk
                </a>
                <a href="{{ url('/vendor/keberatan') }}" @if(request()->is('vendor/keberatan*')) aria-current="page" @endif>
                    <span class="ic i-alert" aria-hidden="true"></span>Keberatan (24 Jam)
                </a>
                <a href="{{ url('/vendor/rundown') }}" @if(request()->is('vendor/rundown*')) aria-current="page" @endif>
                    <span class="ic i-cal" aria-hidden="true"></span>Rundown Acara
                </a>
                <a href="{{ url('/vendor/kalender-ketersediaan') }}" @if(request()->is('vendor/kalender-ketersediaan*')) aria-current="page" @endif>
                    <span class="ic i-cal" aria-hidden="true"></span>Kalender Ketersediaan
                </a>
                <a href="{{ url('/vendor/pencairan') }}" @if(request()->is('vendor/pencairan*')) aria-current="page" @endif>
                    <span class="ic i-wallet" aria-hidden="true"></span>Pencairan Dana
                </a>
            </nav>

            <p class="nav-label">Akun &amp; Kemitraan</p>
            <nav class="nav" aria-label="Akun">
                <a href="{{ url('/vendor/verifikasi') }}" @if(request()->is('vendor/verifikasi*')) aria-current="page" @endif>
                    <span class="ic i-ok" aria-hidden="true"></span>Status Verifikasi
                </a>
                <a href="{{ url('/vendor/profil') }}" @if(request()->is('vendor/profil*')) aria-current="page" @endif>
                    <span class="ic i-users" aria-hidden="true"></span>Profil Usaha
                </a>
                <a href="{{ url('/vendor/notifikasi') }}" @if(request()->is('vendor/notifikasi*')) aria-current="page" @endif>
                    <span class="ic i-bell" aria-hidden="true"></span>Notifikasi
                </a>
                <form method="POST" action="{{ url('/logout') }}">@csrf
                    <button type="submit" style="display:flex;align-items:center;gap:12px;min-height:44px;padding:0 12px;font-size:14.5px;border-radius:10px;color:#3a3150;font-weight:600;width:100%;background:none;border:0;cursor:pointer;font-family:inherit;text-align:left">
                        <span class="ic i-out" aria-hidden="true"></span>Keluar
                    </button>
                </form>
            </nav>
        </aside>

        <div class="overlay" aria-hidden="true"></div>

        <div class="main">
            <header class="topbar">
                <button type="button" class="burger" id="nav-toggle" aria-label="Buka menu navigasi" aria-controls="main-navigation" aria-expanded="false">
                    <span class="ic i-menu" aria-hidden="true"></span>
                </button>
                <div class="search" role="search">
                    <span class="ic i-search" aria-hidden="true"></span>
                    <label class="sr-only" for="cari">Cari</label>
                    <input id="cari" type="search" placeholder="Cari pesanan vendor atau acara…">
                </div>
                <div class="top-right">
                    <a class="icon-btn" href="{{ url('/vendor/notifikasi') }}" aria-label="Notifikasi">
                        <span class="ic i-bell" aria-hidden="true"></span>
                    </a>
                    @php($topbarName = auth()->user()->vendorProfile?->business_name ?? auth()->user()->name)
                    <a class="user" href="{{ url('/vendor/profil') }}">
                        <span class="avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($topbarName, 0, 1)) }}</span>
                        <span class="t"><b>{{ $topbarName }}</b><small>{{ auth()->user()->email }}</small></span>
                    </a>
                </div>
            </header>

            @yield('content')
        </div>
    </div>
    <script src="{{ asset('assets/js/aksi.js') }}"></script>
    <script src="{{ asset('assets/js/app-ui.js') }}"></script>
    <script src="{{ asset('assets/js/password-toggle.js') }}"></script>
    @stack('scripts')
</body>
</html>
