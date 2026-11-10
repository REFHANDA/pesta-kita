<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Klien · Pesta Kita')</title>
    <link rel="icon" href="{{ asset('assets/favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/penyedia.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/klien.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/keselarasan.css') }}">
    @stack('styles')
</head>
<body>
    @include('partials.demo-bar', ['portalTitle' => 'Portal Klien'])
    <a class="skip" href="#konten">Lewati ke konten</a>
    <input type="checkbox" id="nav-toggle" class="sr-only" aria-label="Buka menu">
    <div class="app">
        <aside class="sidebar" aria-label="Navigasi utama Klien">
            <a class="brand" href="{{ url('/klien') }}">
                <span class="logo" aria-hidden="true"></span>
                <span><b>Pesta Kita</b><small>Klien</small></span>
            </a>
            
            <nav class="nav" aria-label="Menu utama">
                <a href="{{ url('/klien') }}" @if(request()->is('klien') || request()->is('klien/beranda')) aria-current="page" @endif>
                    <span class="ic i-home" aria-hidden="true"></span>Beranda
                </a>
            </nav>

            <p class="nav-label">Acara</p>
            <nav class="nav" aria-label="Acara">
                <a href="{{ url('/klien/acara') }}" @if(request()->is('klien/acara*')) aria-current="page" @endif>
                    <span class="ic i-cal" aria-hidden="true"></span>Rancang acara
                </a>
                <a href="{{ url('/klien/riwayat') }}" @if(request()->is('klien/riwayat*') || request()->is('klien/evaluasi*')) aria-current="page" @endif>
                    <span class="ic i-ok" aria-hidden="true"></span>Riwayat &amp; evaluasi
                </a>
            </nav>

            <p class="nav-label">Jelajahi</p>
            <nav class="nav" aria-label="Jelajahi">
                <a href="{{ url('/klien/katalog') }}" @if(request()->is('klien/katalog') || request()->is('klien/katalog-vendor*') || request()->is('klien/katalog-detail*') || request()->is('klien/rekomendasi*')) aria-current="page" @endif>
                    <span class="ic i-users" aria-hidden="true"></span>Vendor &amp; venue
                </a>
                <a href="{{ url('/klien/katalog-peralatan') }}" @if(request()->is('klien/katalog-peralatan*')) aria-current="page" @endif>
                    <span class="ic i-box" aria-hidden="true"></span>Sewa peralatan
                </a>
            </nav>

            <p class="nav-label">Pesanan</p>
            <nav class="nav" aria-label="Pesanan">
                <a href="{{ url('/klien/pesanan') }}" @if(request()->is('klien/pesanan*') || request()->is('klien/tagihan*') || request()->is('klien/rundown*')) aria-current="page" @endif>
                    <span class="ic i-clip" aria-hidden="true"></span>Pesanan &amp; tagihan
                    <span class="n"><span class="sr-only">1 perlu tindakan</span><span aria-hidden="true">1</span></span>
                </a>
            </nav>

            <p class="nav-label">Akun</p>
            <nav class="nav" aria-label="Akun">
                <a href="{{ url('/klien/profil') }}" @if(request()->is('klien/profil*')) aria-current="page" @endif>
                    <span class="ic i-users" aria-hidden="true"></span>Profil saya
                </a>
                <a href="{{ url('/klien/notifikasi') }}" @if(request()->is('klien/notifikasi*')) aria-current="page" @endif>
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
                    <input id="cari" type="search" placeholder="Cari vendor, venue, atau peralatan…">
                </div>
                <div class="top-right">
                    <a class="icon-btn" href="{{ url('/klien/notifikasi') }}" aria-label="Notifikasi, 2 belum dibaca">
                        <span class="ic i-bell" aria-hidden="true"></span>
                        <span class="dot"></span>
                    </a>
                    <a class="user" href="{{ url('/klien/profil') }}">
                        <span class="avatar" aria-hidden="true">R</span>
                        <span class="t"><b>Rina Putri</b><small>klien@pestakita.test</small></span>
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

