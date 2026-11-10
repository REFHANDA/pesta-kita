<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', 'Penyedia · Pesta Kita')</title>
    <link rel="icon" href="{{ asset('assets/favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/penyedia.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/penyedia-lanjutan.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/keselarasan.css') }}">
    @stack('styles')
</head>
<body>
    @include('partials.demo-bar', ['portalTitle' => 'Portal Penyedia (Vendor & Venue)'])
    <a class="skip" href="#konten">Lewati ke konten</a>
    <input type="checkbox" id="nav-toggle" class="sr-only" aria-label="Buka menu">
    <div class="app">
        <aside class="sidebar" aria-label="Navigasi utama Penyedia">
            <a class="brand" href="{{ url('/penyedia') }}">
                <span class="logo" aria-hidden="true"></span>
                <span><b>Pesta Kita</b><small>Panel Penyedia</small></span>
            </a>

            <nav class="nav" aria-label="Menu utama">
                <a href="{{ url('/penyedia') }}" @if(request()->is('penyedia') || request()->is('penyedia/beranda')) aria-current="page" @endif>
                    <span class="ic i-home" aria-hidden="true"></span>Beranda
                </a>
            </nav>

            <p class="nav-label">Katalog saya</p>
            <nav class="nav" aria-label="Katalog saya">
                <a href="{{ url('/penyedia/layanan') }}" @if(request()->is('penyedia/layanan*') || request()->is('penyedia/item-form*')) aria-current="page" @endif>
                    <span class="ic i-box" aria-hidden="true"></span>Layanan vendor
                </a>
                <a href="{{ url('/penyedia/venue') }}" @if(request()->is('penyedia/venue*')) aria-current="page" @endif>
                    <span class="ic i-bank" aria-hidden="true"></span>Data venue
                </a>
                <a href="{{ url('/penyedia/peralatan') }}" @if(request()->is('penyedia/peralatan*')) aria-current="page" @endif>
                    <span class="ic i-box" aria-hidden="true"></span>Peralatan sewa
                </a>
                <a href="{{ url('/penyedia/pengajuan-harga') }}" @if(request()->is('penyedia/pengajuan-harga*')) aria-current="page" @endif>
                    <span class="ic i-tag" aria-hidden="true"></span>Pengajuan harga
                    <span class="n"><span class="sr-only">1 perlu tindakan</span><span aria-hidden="true">1</span></span>
                </a>
            </nav>

            <p class="nav-label">Operasional</p>
            <nav class="nav" aria-label="Operasional">
                <a href="{{ url('/penyedia/pesanan') }}" @if(request()->is('penyedia/pesanan*')) aria-current="page" @endif>
                    <span class="ic i-clip" aria-hidden="true"></span>Pesanan
                    <span class="n"><span class="sr-only">2 perlu tindakan</span><span aria-hidden="true">2</span></span>
                </a>
                <a href="{{ url('/penyedia/keberatan') }}" @if(request()->is('penyedia/keberatan*')) aria-current="page" @endif>
                    <span class="ic i-alert" aria-hidden="true"></span>Keberatan
                </a>
                <a href="{{ url('/penyedia/rundown') }}" @if(request()->is('penyedia/rundown*')) aria-current="page" @endif>
                    <span class="ic i-cal" aria-hidden="true"></span>Rundown acara
                </a>
                <a href="{{ url('/penyedia/pencairan') }}" @if(request()->is('penyedia/pencairan*')) aria-current="page" @endif>
                    <span class="ic i-wallet" aria-hidden="true"></span>Pencairan
                </a>
            </nav>

            <p class="nav-label">Akun & Kemitraan</p>
            <nav class="nav" aria-label="Akun">
                <a href="{{ url('/penyedia/verifikasi-penyedia') }}" @if(request()->is('penyedia/verifikasi-penyedia*')) aria-current="page" @endif>
                    <span class="ic i-ok" aria-hidden="true"></span>Status verifikasi
                </a>
                <a href="{{ url('/penyedia/profil') }}" @if(request()->is('penyedia/profil*')) aria-current="page" @endif>
                    <span class="ic i-users" aria-hidden="true"></span>Profil usaha
                </a>
                <a href="{{ url('/penyedia/notifikasi') }}" @if(request()->is('penyedia/notifikasi*')) aria-current="page" @endif>
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
                    <input id="cari" type="search" placeholder="Cari pesanan atau acara…">
                </div>
                <div class="top-right">
                    <a class="icon-btn" href="{{ url('/penyedia/notifikasi') }}" aria-label="Notifikasi, 2 belum dibaca">
                        <span class="ic i-bell" aria-hidden="true"></span>
                        <span class="dot"></span>
                    </a>
                    <a class="user" href="{{ url('/penyedia/profil') }}">
                        <span class="avatar" aria-hidden="true">P</span>
                        <span class="t"><b>Mitra Penyedia</b><small>penyedia@pestakita.test</small></span>
                    </a>
                </div>
            </header>

            @yield('content')
        </div>
    </div>
    @stack('scripts')
</body>
</html>
