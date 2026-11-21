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
            @php($p = fn (string ...$patterns) => request()->is(...$patterns))
            <x-portal-nav label="Menu utama Vendor" :items="[
                ['label' => 'Beranda', 'href' => route('vendor.beranda'), 'icon' => 'home', 'active' => $p('vendor', 'vendor/beranda')],
                ['label' => 'Katalog saya', 'href' => route('vendor.layanan'), 'icon' => 'box', 'active' => $p('vendor/layanan*', 'vendor/peralatan*', 'vendor/item-form*', 'vendor/pengajuan-harga*')],
                ['label' => 'Pesanan', 'href' => route('vendor.pesanan'), 'icon' => 'clip', 'active' => $p('vendor/pesanan*')],
                ['label' => 'Rundown', 'href' => route('vendor.rundown'), 'icon' => 'cal', 'active' => $p('vendor/rundown*')],
                ['label' => 'Pencairan', 'href' => route('vendor.pencairan'), 'icon' => 'wallet', 'active' => $p('vendor/pencairan*')],
                ['label' => 'Profil & verifikasi', 'href' => route('vendor.profil'), 'icon' => 'users', 'active' => $p('vendor/profil*', 'vendor/verifikasi*')],
            ]" />
        </aside>

        <div class="overlay" aria-hidden="true"></div>

        <div class="main">
            @php($topbarName = auth()->user()->vendorProfile?->business_name ?? auth()->user()->name)
            <x-portal-topbar :notif-url="route('vendor.notifikasi')" :profile-url="route('vendor.profil')" :name="$topbarName" :email="auth()->user()->email" />

            @yield('content')
        </div>
    </div>
    <script src="{{ asset('assets/js/aksi.js') }}"></script>
    <script src="{{ asset('assets/js/app-ui.js') }}"></script>
    <script src="{{ asset('assets/js/password-toggle.js') }}"></script>
    @stack('scripts')
</body>
</html>
