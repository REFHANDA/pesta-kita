<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', 'Panel Vendor · Pesta Kita')</title>
    <link rel="icon" href="{{ asset('assets/favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/penyedia.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/penyedia-lanjutan.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/keselarasan.css') }}">
    @stack('styles')
</head>
<body>
    @include('partials.demo-bar', ['portalTitle' => 'Panel Vendor (Katering, Dekorasi, Peralatan)'])
    <a class="skip" href="#konten">Lewati ke konten</a>
    <input type="checkbox" id="nav-toggle" class="sr-only" aria-label="Buka menu">
    <div class="app">
        <aside class="sidebar" aria-label="Navigasi utama Vendor">
            <a class="brand" href="{{ url('/vendor') }}">
                <span class="logo" aria-hidden="true"></span>
                <span><b>Pesta Kita</b><small>Panel Vendor</small></span>
            </a>
            <div style="padding:0 12px;margin:4px 0 10px">
                <span class="role-chip" style="margin:0;background:#fef3c7;color:#78350f">Aktor: Vendor</span>
            </div>

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
                    <span class="n"><span class="sr-only">1 perlu tindakan</span><span aria-hidden="true">1</span></span>
                </a>
            </nav>

            <p class="nav-label">Operasional Acara</p>
            <nav class="nav" aria-label="Operasional">
                <a href="{{ url('/vendor/pesanan') }}" @if(request()->is('vendor/pesanan*')) aria-current="page" @endif>
                    <span class="ic i-clip" aria-hidden="true"></span>Pesanan Masuk
                    <span class="n"><span class="sr-only">2 perlu tindakan</span><span aria-hidden="true">2</span></span>
                </a>
                <a href="{{ url('/vendor/keberatan') }}" @if(request()->is('vendor/keberatan*')) aria-current="page" @endif>
                    <span class="ic i-alert" aria-hidden="true"></span>Keberatan (24 Jam)
                </a>
                <a href="{{ url('/vendor/rundown') }}" @if(request()->is('vendor/rundown*')) aria-current="page" @endif>
                    <span class="ic i-cal" aria-hidden="true"></span>Rundown Acara
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
                    <input id="cari" type="search" placeholder="Cari pesanan vendor atau acara…">
                </div>
                <div class="top-right">
                    <a class="icon-btn" href="{{ url('/vendor/notifikasi') }}" aria-label="Notifikasi, 2 belum dibaca">
                        <span class="ic i-bell" aria-hidden="true"></span>
                        <span class="dot"></span>
                    </a>
                    <a class="user" href="{{ url('/vendor/profil') }}">
                        <span class="avatar" aria-hidden="true">D</span>
                        <span class="t"><b>Dapur Nusantara</b><small>vendor@pestakita.test</small></span>
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
