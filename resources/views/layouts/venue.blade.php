<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', 'Panel Pemilik Venue · Pesta Kita')</title>
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
        <aside class="sidebar" aria-label="Navigasi utama Pemilik Venue" id="main-navigation">
            <button type="button" class="sidebar-close" aria-label="Tutup menu navigasi"><span aria-hidden="true">×</span></button>
            <a class="brand" href="{{ url('/venue') }}">
                <span class="logo" aria-hidden="true"></span>
                <span><b>Pesta Kita</b><small>Panel Pemilik Venue</small></span>
            </a>
            <span class="role-chip">Aktor: Pemilik Venue</span>

            <nav class="nav" aria-label="Menu utama">
                <a href="{{ url('/venue') }}" @if(request()->is('venue') || request()->is('venue/beranda')) aria-current="page" @endif>
                    <span class="ic i-home" aria-hidden="true"></span>Beranda
                </a>
            </nav>

            <p class="nav-label">Manajemen Venue</p>
            <nav class="nav" aria-label="Katalog venue">
                <a href="{{ url('/venue/data') }}" @if(request()->is('venue/data*') || request()->is('venue/form*')) aria-current="page" @endif>
                    <span class="ic i-bank" aria-hidden="true"></span>Data &amp; Denah Venue
                </a>
                <a href="{{ url('/venue/pengajuan-harga') }}" @if(request()->is('venue/pengajuan-harga*')) aria-current="page" @endif>
                    <span class="ic i-tag" aria-hidden="true"></span>Pengajuan Harga
                </a>
                <a href="{{ url('/venue/permintaan-penawaran') }}" @if(request()->is('venue/permintaan-penawaran*')) aria-current="page" @endif>
                    <span class="ic i-bill" aria-hidden="true"></span>Permintaan Penawaran
                </a>
            </nav>

            <p class="nav-label">Operasional Reservasi</p>
            <nav class="nav" aria-label="Operasional venue">
                <a href="{{ url('/venue/pesanan') }}" @if(request()->is('venue/pesanan*') || request()->is('venue/pesanan-detail*')) aria-current="page" @endif>
                    <span class="ic i-clip" aria-hidden="true"></span>Pesanan Venue
                </a>
                <a href="{{ url('/venue/kalender-ketersediaan') }}" @if(request()->is('venue/kalender-ketersediaan*')) aria-current="page" @endif>
                    <span class="ic i-cal" aria-hidden="true"></span>Kalender Ketersediaan
                </a>
                <a href="{{ url('/venue/keberatan') }}" @if(request()->is('venue/keberatan*')) aria-current="page" @endif>
                    <span class="ic i-alert" aria-hidden="true"></span>Keberatan (24 Jam)
                </a>
                <a href="{{ url('/venue/rundown') }}" @if(request()->is('venue/rundown*')) aria-current="page" @endif>
                    <span class="ic i-cal" aria-hidden="true"></span>Pantau Rundown
                </a>
                <a href="{{ url('/venue/pencairan') }}" @if(request()->is('venue/pencairan*')) aria-current="page" @endif>
                    <span class="ic i-wallet" aria-hidden="true"></span>Pencairan Dana
                </a>
            </nav>

            <p class="nav-label">Akun &amp; Kemitraan</p>
            <nav class="nav" aria-label="Akun">
                <a href="{{ url('/venue/verifikasi') }}" @if(request()->is('venue/verifikasi*')) aria-current="page" @endif>
                    <span class="ic i-ok" aria-hidden="true"></span>Status Verifikasi
                </a>
                <a href="{{ url('/venue/profil') }}" @if(request()->is('venue/profil*')) aria-current="page" @endif>
                    <span class="ic i-users" aria-hidden="true"></span>Profil Venue
                </a>
                <a href="{{ route('venue.bank-accounts') }}" @if(request()->is('venue/rekening-pencairan*')) aria-current="page" @endif>
                    <span class="ic i-bank" aria-hidden="true"></span>Rekening pencairan
                </a>
                <a href="{{ url('/venue/notifikasi') }}" @if(request()->is('venue/notifikasi*')) aria-current="page" @endif>
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
                    <input id="cari" type="search" placeholder="Cari pesanan venue atau tanggal acara…">
                </div>
                <div class="top-right">
                    <a class="icon-btn" href="{{ url('/venue/notifikasi') }}" aria-label="Notifikasi, {{ $unreadNotificationCount }} belum dibaca">
                        <span class="ic i-bell" aria-hidden="true"></span>
                        @if ($unreadNotificationCount > 0)<span class="badge b-bad">{{ $unreadNotificationCount }}</span>@endif
                    </a>
                    @php($topbarName = auth()->user()->venueProfile?->business_name ?? auth()->user()->name)
                    <a class="user" href="{{ url('/venue/profil') }}">
                        <span class="avatar" aria-hidden="true" style="background:#e0f2fe;color:#0369a1">{{ mb_strtoupper(mb_substr($topbarName, 0, 1)) }}</span>
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
