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
            @php($p = fn (string ...$patterns) => request()->is(...$patterns))
            <x-portal-nav label="Menu utama Pemilik Venue" :items="[
                ['label' => 'Beranda', 'href' => route('venue.beranda'), 'icon' => 'home', 'active' => $p('venue', 'venue/beranda')],
                ['label' => 'Venue saya', 'href' => route('venue.data'), 'icon' => 'image', 'active' => $p('venue/data*', 'venue/form*', 'venue/pengajuan-harga*')],
                ['label' => 'Pesanan', 'href' => route('venue.pesanan'), 'icon' => 'clip', 'active' => $p('venue/pesanan*')],
                ['label' => 'Rundown', 'href' => route('venue.rundown'), 'icon' => 'cal', 'active' => $p('venue/rundown*')],
                ['label' => 'Pencairan', 'href' => route('venue.pencairan'), 'icon' => 'wallet', 'active' => $p('venue/pencairan*')],
                ['label' => 'Profil & verifikasi', 'href' => route('venue.profil'), 'icon' => 'users', 'active' => $p('venue/profil*', 'venue/verifikasi*')],
            ]" />
        </aside>

        <div class="overlay" aria-hidden="true"></div>

        <div class="main">
            @php($topbarName = auth()->user()->venueProfile?->business_name ?? auth()->user()->name)
            <x-portal-topbar :notif-url="route('venue.notifikasi')" :profile-url="route('venue.profil')" :name="$topbarName" :email="auth()->user()->email" />

            @yield('content')
        </div>
    </div>
    <script src="{{ asset('assets/js/aksi.js') }}"></script>
    <script src="{{ asset('assets/js/app-ui.js') }}"></script>
    <script src="{{ asset('assets/js/password-toggle.js') }}"></script>
    @stack('scripts')
</body>
</html>
