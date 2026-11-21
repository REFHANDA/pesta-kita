<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width,initial-scale=1" name="viewport"/>
    <title>@yield('title', 'Admin · Admin Pesta Kita')</title>
    <link href="{{ asset('assets/favicon.svg') }}" rel="icon" type="image/svg+xml"/>
    <link href="{{ asset('assets/css/design-tokens.css') }}" rel="stylesheet"/>
    <link href="{{ asset('assets/css/admin.css') }}" rel="stylesheet"/>
    <link href="{{ asset('assets/css/admin-pages.css') }}" rel="stylesheet"/>
    <link href="{{ asset('assets/css/keselarasan.css') }}" rel="stylesheet"/>
    <link href="{{ asset('assets/css/password-toggle.css') }}" rel="stylesheet"/>
    @stack('styles')
</head>
<body>
    <a class="skip" href="#konten">Lewati ke konten</a>
    <div class="app">
        <aside aria-label="Navigasi utama Admin Pesta Kita" class="sidebar" id="main-navigation">
            <button type="button" class="sidebar-close" aria-label="Tutup menu navigasi"><span aria-hidden="true">×</span></button>
            <a class="brand" href="{{ url('/admin') }}">
                <span aria-hidden="true" class="logo"></span>
                <span><b>Pesta Kita</b><small>Panel Admin</small></span>
            </a>
            <span class="role-chip">Aktor: Admin</span>
            @php($p = fn (string ...$patterns) => request()->is(...$patterns))
            <x-portal-nav label="Menu utama Admin" :items="[
                ['label' => 'Beranda', 'href' => route('admin.beranda'), 'icon' => 'home', 'active' => $p('admin', 'admin/beranda')],
                ['label' => 'Pesanan', 'href' => route('admin.pesanan'), 'icon' => 'clip', 'active' => $p('admin/pesanan*')],
                ['label' => 'Tagihan & pembayaran', 'href' => route('admin.pembayaran.index'), 'icon' => 'bill', 'active' => $p('admin/pembayaran*', 'admin/tagihan*')],
                ['label' => 'Pembatalan & refund', 'href' => route('admin.pembatalan'), 'icon' => 'undo', 'active' => $p('admin/pembatalan*')],
                ['label' => 'Pencairan', 'href' => route('admin.pencairan'), 'icon' => 'wallet', 'active' => $p('admin/pencairan*')],
                ['label' => 'Penyedia & katalog', 'href' => route('admin.penyedia.index'), 'icon' => 'users', 'active' => $p('admin/admin-penyedia*', 'admin/penyedia*', 'admin/admin-venue-form*', 'admin/katalog*', 'admin/pengajuan-harga*', 'admin/pengguna*')],
                ['label' => 'Acara', 'href' => route('admin.acara'), 'icon' => 'cal', 'active' => $p('admin/acara*')],
                ['label' => 'Pengaturan', 'href' => route('admin.pengaturan'), 'icon' => 'cog', 'active' => $p('admin/pengaturan*', 'admin/rekening*')],
                ['label' => 'Log aktivitas', 'href' => route('admin.aktivitas'), 'icon' => 'log', 'active' => $p('admin/aktivitas*')],
            ]" />
        </aside>

        <div class="overlay" aria-hidden="true"></div>

        <div class="main">
            <x-portal-topbar :notif-url="route('admin.notifikasi')" :profile-url="route('admin.profil')" :name="auth()->user()->name" :email="auth()->user()->email" />

            @yield('content')
        </div>
    </div>
    <script src="{{ asset('assets/js/aksi.js') }}"></script>
    <script src="{{ asset('assets/js/app-ui.js') }}"></script>
    <script src="{{ asset('assets/js/password-toggle.js') }}"></script>
    @stack('scripts')
</body>
</html>

