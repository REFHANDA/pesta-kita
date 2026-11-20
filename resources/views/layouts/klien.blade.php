<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Klien · Pesta Kita')</title>
    <link rel="icon" href="{{ asset('assets/favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('assets/css/design-tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/penyedia.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/klien.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/keselarasan.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/password-toggle.css') }}">
    @stack('styles')
</head>
<body>
    <a class="skip" href="#konten">Lewati ke konten</a>
    <div class="app">
        <aside class="sidebar" aria-label="Navigasi utama Klien" id="main-navigation">
            <button type="button" class="sidebar-close" aria-label="Tutup menu navigasi"><span aria-hidden="true">×</span></button>
            <a class="brand" href="{{ url('/klien') }}">
                <span class="logo" aria-hidden="true"></span>
                <span><b>Pesta Kita</b><small>Klien</small></span>
            </a>
            <span class="role-chip">Aktor: Klien</span>
            
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
                <a href="{{ url('/klien/katalog') }}" @if(request()->is('klien/katalog') || request()->is('klien/katalog-vendor*') || request()->is('klien/katalog-venue*') || request()->is('klien/katalog-detail*') || request()->is('klien/penyedia-*') || request()->is('klien/rekomendasi*')) aria-current="page" @endif>
                    <span class="ic i-users" aria-hidden="true"></span>Vendor &amp; venue
                </a>
                <a href="{{ url('/klien/katalog-peralatan') }}" @if(request()->is('klien/katalog-peralatan*')) aria-current="page" @endif>
                    <span class="ic i-box" aria-hidden="true"></span>Sewa peralatan
                </a>
                <a href="{{ url('/klien/kalender-ketersediaan') }}" @if(request()->is('klien/kalender-ketersediaan*')) aria-current="page" @endif>
                    <span class="ic i-cal" aria-hidden="true"></span>Kalender ketersediaan
                </a>
            </nav>

            <p class="nav-label">Pesanan</p>
            <nav class="nav" aria-label="Pesanan">
                <a href="{{ url('/klien/pesanan') }}" @if(request()->is('klien/pesanan*') || request()->is('klien/tagihan*') || request()->is('klien/rundown*')) aria-current="page" @endif>
                    <span class="ic i-clip" aria-hidden="true"></span>Pesanan &amp; tagihan
                </a>
            </nav>

            <p class="nav-label">Akun</p>
            <nav class="nav" aria-label="Akun">
                <a href="{{ url('/klien/profil') }}" @if(request()->is('klien/profil*')) aria-current="page" @endif>
                    <span class="ic i-users" aria-hidden="true"></span>Profil saya
                </a>
                <a href="{{ url('/klien/notifikasi') }}" @if(request()->is('klien/notifikasi*')) aria-current="page" @endif>
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
                    <input id="cari" type="search" placeholder="Cari vendor, venue, atau peralatan…">
                </div>
                <div class="top-right">
                    <a class="icon-btn" href="{{ url('/klien/notifikasi') }}" aria-label="Notifikasi">
                        <span class="ic i-bell" aria-hidden="true"></span>
                    </a>
                    <a class="user" href="{{ url('/klien/profil') }}">
                        <span class="avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="t"><b>{{ auth()->user()->name }}</b><small>{{ auth()->user()->email }}</small></span>
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

