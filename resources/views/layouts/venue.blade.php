<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', 'Panel Pemilik Venue · Pesta Kita')</title>
    <link rel="icon" href="{{ asset('assets/favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/penyedia.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/penyedia-lanjutan.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/keselarasan.css') }}">
    @stack('styles')
</head>
<body>
    @include('partials.demo-bar', ['portalTitle' => 'Panel Pemilik Venue (Gedung, Aula, Tempat Acara)'])
    <a class="skip" href="#konten">Lewati ke konten</a>
    <input type="checkbox" id="nav-toggle" class="sr-only" aria-label="Buka menu">
    <div class="app">
        <aside class="sidebar" aria-label="Navigasi utama Pemilik Venue">
            <a class="brand" href="{{ url('/venue') }}">
                <span class="logo" aria-hidden="true"></span>
                <span><b>Pesta Kita</b><small>Panel Pemilik Venue</small></span>
            </a>
            <div style="padding:0 12px;margin:4px 0 10px">
                <span class="role-chip" style="margin:0;background:#e0f2fe;color:#0c4a6e">Aktor: Pemilik Venue</span>
            </div>

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
                    <span class="n"><span class="sr-only">1 perlu tindakan</span><span aria-hidden="true">1</span></span>
                </a>
            </nav>

            <p class="nav-label">Operasional Reservasi</p>
            <nav class="nav" aria-label="Operasional venue">
                <a href="{{ url('/venue/pesanan') }}" @if(request()->is('venue/pesanan*') || request()->is('venue/pesanan-detail*')) aria-current="page" @endif>
                    <span class="ic i-clip" aria-hidden="true"></span>Pesanan Venue
                    <span class="n"><span class="sr-only">2 perlu tindakan</span><span aria-hidden="true">2</span></span>
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
                <a href="{{ url('/venue/notifikasi') }}" @if(request()->is('venue/notifikasi*')) aria-current="page" @endif>
                    <span class="ic i-bell" aria-hidden="true"></span>Notifikasi
                    <span class="n"><span class="sr-only">2 belum dibaca</span><span aria-hidden="true">2</span></span>
                </a>
                <a href="{{ url('/login') }}">
                    <span class="ic i-out" aria-hidden="true"></span>Keluar
                </a>
            </nav>
        </aside>

        <label class="overlay" for="nav-toggle"></label>

        <div class="main">
            <header class="topbar">
                <label class="burger" for="nav-toggle">
                    <span class="ic i-menu" aria-hidden="true"></span>
                    <span class="sr-only">Menu</span>
                </label>
                <div class="search" role="search">
                    <span class="ic i-search" aria-hidden="true"></span>
                    <label class="sr-only" for="cari">Cari</label>
                    <input id="cari" type="search" placeholder="Cari pesanan venue atau tanggal acara…">
                </div>
                <div class="top-right">
                    <a class="icon-btn" href="{{ url('/venue/notifikasi') }}" aria-label="Notifikasi, 2 belum dibaca">
                        <span class="ic i-bell" aria-hidden="true"></span>
                        <span class="dot"></span>
                    </a>
                    <a class="user" href="{{ url('/venue/profil') }}">
                        <span class="avatar" aria-hidden="true" style="background:#e0f2fe;color:#0369a1">G</span>
                        <span class="t"><b>Graha Melati</b><small>venue@pestakita.test</small></span>
                    </a>
                </div>
            </header>

            @yield('content')
        </div>
    </div>
    <script src="{{ asset('assets/js/app-ui.js') }}"></script>
    @stack('scripts')
</body>
</html>
