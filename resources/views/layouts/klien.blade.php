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
            @php($p = fn (string ...$patterns) => request()->is(...$patterns))
            <x-portal-nav label="Menu utama Klien" :items="[
                ['label' => 'Beranda', 'href' => route('klien.beranda'), 'icon' => 'home', 'active' => $p('klien', 'klien/beranda')],
                ['label' => 'Acara saya', 'href' => route('klien.acara'), 'icon' => 'cal', 'active' => $p('klien/acara*', 'klien/riwayat', 'klien/tagihan*', 'klien/rundown*', 'klien/evaluasi*', 'klien/rekomendasi*')],
                ['label' => 'Katalog', 'href' => route('klien.katalog.vendor'), 'icon' => 'box', 'active' => $p('klien/katalog*', 'klien/penyedia-*')],
                ['label' => 'Notifikasi', 'href' => route('klien.notifikasi'), 'icon' => 'bell', 'active' => $p('klien/notifikasi*')],
                ['label' => 'Profil', 'href' => route('klien.profil'), 'icon' => 'users', 'active' => $p('klien/profil*')],
            ]" />
        </aside>

        <div class="overlay" aria-hidden="true"></div>

        <div class="main">
            <x-portal-topbar :notif-url="route('klien.notifikasi')" :profile-url="route('klien.profil')" :name="auth()->user()->name" :email="auth()->user()->email" />

            @yield('content')
        </div>
    </div>
    <x-whatsapp-help :context="html_entity_decode(trim($__env->yieldContent('wa_context')), ENT_QUOTES)" />
    <script src="{{ asset('assets/js/aksi.js') }}"></script>
    <script src="{{ asset('assets/js/app-ui.js') }}"></script>
    <script src="{{ asset('assets/js/password-toggle.js') }}"></script>
    @stack('scripts')
</body>
</html>

