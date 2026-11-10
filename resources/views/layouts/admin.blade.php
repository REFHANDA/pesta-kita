<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width,initial-scale=1" name="viewport"/>
    <title>@yield('title', 'Admin · Admin Pesta Kita')</title>
    <link href="{{ asset('assets/favicon.svg') }}" rel="icon" type="image/svg+xml"/>
    <link href="{{ asset('assets/css/admin.css') }}" rel="stylesheet"/>
    <link href="{{ asset('assets/css/admin-pages.css') }}" rel="stylesheet"/>
    <link href="{{ asset('assets/css/keselarasan.css') }}" rel="stylesheet"/>
    @stack('styles')
</head>
<body>
    @include('partials.demo-bar', ['portalTitle' => 'Panel Admin'])
    <a class="skip" href="#konten">Lewati ke konten</a>
    <input aria-hidden="true" class="sr-only" id="nav-toggle" tabindex="-1" type="checkbox"/>
    <div class="app">
        <aside aria-label="Navigasi utama Admin Pesta Kita" class="sidebar">
            <a class="brand" href="{{ url('/admin') }}">
                <span aria-hidden="true" class="logo"></span>
                <span><b>Pesta Kita</b><small>Panel Admin</small></span>
            </a>
            
            <nav aria-label="Menu utama" class="nav">
                <a @if(request()->is('admin') || request()->is('admin/beranda')) aria-current="page" @endif href="{{ url('/admin') }}">
                    <span aria-hidden="true" class="ic i-home"></span>Beranda
                </a>
            </nav>

            <p class="nav-label">Operasional</p>
            <nav aria-label="Operasional" class="nav">
                <a @if(request()->is('admin/pesanan*') || request()->is('admin/admin-pesanan-venue*')) aria-current="page" @endif href="{{ url('/admin/pesanan') }}">
                    <span aria-hidden="true" class="ic i-clip"></span>Review pesanan
                    <span class="n"><span class="sr-only">3 perlu tindakan</span><span aria-hidden="true">3</span></span>
                </a>
                <a @if(request()->is('admin/pembayaran*') || request()->is('admin/tagihan-detail*')) aria-current="page" @endif href="{{ url('/admin/pembayaran') }}">
                    <span aria-hidden="true" class="ic i-bill"></span>Tagihan &amp; pembayaran
                    <span class="n"><span class="sr-only">4 perlu tindakan</span><span aria-hidden="true">4</span></span>
                </a>
                <a @if(request()->is('admin/keberatan*')) aria-current="page" @endif href="{{ url('/admin/keberatan') }}">
                    <span aria-hidden="true" class="ic i-alert"></span>Keberatan
                    <span class="n"><span class="sr-only">2 perlu tindakan</span><span aria-hidden="true">2</span></span>
                </a>
                <a @if(request()->is('admin/pembatalan*')) aria-current="page" @endif href="{{ url('/admin/pembatalan') }}">
                    <span aria-hidden="true" class="ic i-undo"></span>Pembatalan &amp; refund
                    <span class="n"><span class="sr-only">2 perlu tindakan</span><span aria-hidden="true">2</span></span>
                </a>
                <a @if(request()->is('admin/pencairan*')) aria-current="page" @endif href="{{ url('/admin/pencairan') }}">
                    <span aria-hidden="true" class="ic i-wallet"></span>Pencairan penyedia
                    <span class="n"><span class="sr-only">5 perlu tindakan</span><span aria-hidden="true">5</span></span>
                </a>
            </nav>

            <p class="nav-label">Penyedia &amp; katalog</p>
            <nav aria-label="Penyedia &amp; katalog" class="nav">
                <a @if(request()->is('admin/katalog*')) aria-current="page" @endif href="{{ url('/admin/katalog') }}">
                    <span aria-hidden="true" class="ic i-box"></span>Katalog penyedia
                    <span class="n"><span class="sr-only">6 perlu tindakan</span><span aria-hidden="true">6</span></span>
                </a>
                <a @if(request()->is('admin/pengajuan-harga*')) aria-current="page" @endif href="{{ url('/admin/pengajuan-harga') }}">
                    <span aria-hidden="true" class="ic i-tag"></span>Pengajuan harga
                    <span class="n"><span class="sr-only">2 perlu tindakan</span><span aria-hidden="true">2</span></span>
                </a>
                <a @if(request()->is('admin/pengguna*') || request()->is('admin/admin-penyedia*') || request()->is('admin/mitra-detail*') || request()->is('admin/admin-venue-form*')) aria-current="page" @endif href="{{ url('/admin/pengguna') }}">
                    <span aria-hidden="true" class="ic i-users"></span>Penyedia &amp; pengguna
                    <span class="n"><span class="sr-only">3 perlu tindakan</span><span aria-hidden="true">3</span></span>
                </a>
                <a @if(request()->is('admin/rekening*')) aria-current="page" @endif href="{{ url('/admin/rekening') }}">
                    <span aria-hidden="true" class="ic i-bank"></span>Rekening perusahaan
                </a>
            </nav>

            <p class="nav-label">Pemantauan</p>
            <nav aria-label="Pemantauan" class="nav">
                <a @if(request()->is('admin/acara*')) aria-current="page" @endif href="{{ url('/admin/acara') }}">
                    <span aria-hidden="true" class="ic i-cal"></span>Pantau acara
                </a>
                <a @if(request()->is('admin/rundown*')) aria-current="page" @endif href="{{ url('/admin/rundown') }}">
                    <span aria-hidden="true" class="ic i-clip"></span>Monitoring rundown
                </a>
                <a @if(request()->is('admin/evaluasi*')) aria-current="page" @endif href="{{ url('/admin/evaluasi') }}">
                    <span aria-hidden="true" class="ic i-users"></span>Evaluasi &amp; rating
                </a>
                <a @if(request()->is('admin/aktivitas*')) aria-current="page" @endif href="{{ url('/admin/aktivitas') }}">
                    <span aria-hidden="true" class="ic i-log"></span>Log aktivitas
                </a>
                <a @if(request()->is('admin/pengaturan*')) aria-current="page" @endif href="{{ url('/admin/pengaturan') }}">
                    <span aria-hidden="true" class="ic i-cog"></span>Pengaturan
                </a>
            </nav>

            <p class="nav-label">Akun</p>
            <nav aria-label="Akun" class="nav">
                <a @if(request()->is('admin/profil*')) aria-current="page" @endif href="{{ url('/admin/profil') }}">
                    <span aria-hidden="true" class="ic i-users"></span>Profil
                </a>
                <a @if(request()->is('admin/notifikasi*')) aria-current="page" @endif href="{{ url('/admin/notifikasi') }}">
                    <span aria-hidden="true" class="ic i-bell"></span>Notifikasi
                    <span class="n"><span class="sr-only">3 belum dibaca</span><span aria-hidden="true">3</span></span>
                </a>
                <a href="{{ url('/login') }}">
                    <span aria-hidden="true" class="ic i-out"></span>Keluar
                </a>
            </nav>
        </aside>

        <label class="overlay" for="nav-toggle"></label>

        <div class="main">
            <header class="topbar">
                <label class="burger" for="nav-toggle">
                    <span aria-hidden="true" class="ic i-menu"></span>
                    <span class="sr-only">Menu</span>
                </label>
                <div class="search" role="search">
                    <span aria-hidden="true" class="ic i-search"></span>
                    <label class="sr-only" for="cari">Cari</label>
                    <input id="cari" placeholder="Cari pesanan, acara, atau penyedia…" type="search"/>
                </div>
                <div class="top-right">
                    <a aria-label="Notifikasi, 3 belum dibaca" class="icon-btn" href="{{ url('/admin/notifikasi') }}">
                        <span aria-hidden="true" class="ic i-bell"></span>
                        <span class="dot"></span>
                    </a>
                    <a aria-label="Profil Admin" class="user" href="{{ url('/admin/profil') }}">
                        <span aria-hidden="true" class="avatar">A</span>
                        <span class="t"><b>Admin</b><small>admin@pestakita.test</small></span>
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

