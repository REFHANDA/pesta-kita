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
            
            <nav aria-label="Menu utama" class="nav">
                <a @if(request()->is('admin') || request()->is('admin/beranda')) aria-current="page" @endif href="{{ url('/admin') }}">
                    <span aria-hidden="true" class="ic i-home"></span>Beranda
                </a>
            </nav>

            <p class="nav-label">Operasional</p>
            <nav aria-label="Operasional" class="nav">
                <a @if(request()->is('admin/pesanan*') || request()->is('admin/admin-pesanan-venue*')) aria-current="page" @endif href="{{ url('/admin/pesanan') }}">
                    <span aria-hidden="true" class="ic i-clip"></span>Review pesanan
                </a>
                <a @if(request()->is('admin/pembayaran*') || request()->is('admin/tagihan-detail*')) aria-current="page" @endif href="{{ url('/admin/pembayaran') }}">
                    <span aria-hidden="true" class="ic i-bill"></span>Tagihan &amp; pembayaran
                </a>
                <a @if(request()->is('admin/keberatan*')) aria-current="page" @endif href="{{ url('/admin/keberatan') }}">
                    <span aria-hidden="true" class="ic i-alert"></span>Keberatan
                </a>
                <a @if(request()->is('admin/pembatalan*')) aria-current="page" @endif href="{{ url('/admin/pembatalan') }}">
                    <span aria-hidden="true" class="ic i-undo"></span>Pembatalan &amp; refund
                </a>
                <a @if(request()->is('admin/pencairan*')) aria-current="page" @endif href="{{ url('/admin/pencairan') }}">
                    <span aria-hidden="true" class="ic i-wallet"></span>Pencairan penyedia
                </a>
            </nav>

            <p class="nav-label">Penyedia &amp; katalog</p>
            <nav aria-label="Penyedia &amp; katalog" class="nav">
                <a @if(request()->is('admin/katalog*')) aria-current="page" @endif href="{{ url('/admin/katalog') }}">
                    <span aria-hidden="true" class="ic i-box"></span>Katalog penyedia
                </a>
                <a @if(request()->is('admin/pengajuan-harga*')) aria-current="page" @endif href="{{ url('/admin/pengajuan-harga') }}">
                    <span aria-hidden="true" class="ic i-tag"></span>Pengajuan harga
                </a>
                <a @if(request()->is('admin/pengguna*') || request()->is('admin/admin-penyedia*') || request()->is('admin/mitra-detail*') || request()->is('admin/admin-venue-form*')) aria-current="page" @endif href="{{ url('/admin/pengguna') }}">
                    <span aria-hidden="true" class="ic i-users"></span>Penyedia &amp; pengguna
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
                </a>
                <form method="POST" action="{{ url('/logout') }}">@csrf
                    <button type="submit" style="display:flex;align-items:center;gap:12px;min-height:44px;padding:0 12px;font-size:14.5px;border-radius:10px;color:#3a3150;font-weight:600;width:100%;background:none;border:0;cursor:pointer;font-family:inherit;text-align:left">
                        <span aria-hidden="true" class="ic i-out"></span>Keluar
                    </button>
                </form>
            </nav>
        </aside>

        <div class="overlay" aria-hidden="true"></div>

        <div class="main">
            <header class="topbar">
                <button type="button" class="burger" id="nav-toggle" aria-label="Buka menu navigasi" aria-controls="main-navigation" aria-expanded="false">
                    <span aria-hidden="true" class="ic i-menu"></span>
                </button>
                <div class="search" role="search">
                    <span aria-hidden="true" class="ic i-search"></span>
                    <label class="sr-only" for="cari">Cari</label>
                    <input id="cari" placeholder="Cari pesanan, acara, atau penyedia…" type="search"/>
                </div>
                <div class="top-right">
                    <a aria-label="Notifikasi" class="icon-btn" href="{{ url('/admin/notifikasi') }}">
                        <span aria-hidden="true" class="ic i-bell"></span>
                    </a>
                    <a aria-label="Profil Admin" class="user" href="{{ url('/admin/profil') }}">
                        <span aria-hidden="true" class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
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

