
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Beranda | Pesta Kita</title>

@vite(['resources/css/app.css', 'resources/js/app.js'])

<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: Arial, sans-serif;
    background: #fbf9fd;
    color: #241a36;
    font-size: 14px;
}

.sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    width: 206px;
    background: #201632;
    color: white;
    display: flex;
    flex-direction: column;
    z-index: 10;
}

.brand {
    padding: 25px 17px 15px;
}

.brand h2 {
    font-size: 21px;
    margin-bottom: 5px;
}

.brand small {
    font-style: italic;
    color: #d4cadd;
    font-size: 11px;
}

.menu {
    padding: 12px 10px;
}

.menu a {
    display: block;
    padding: 12px 13px;
    margin-bottom: 4px;
    text-decoration: none;
    color: #eee8f3;
    border-radius: 10px;
    font-style: italic;
}

.menu a:hover {
    background: #40354f;
}

.menu a.active {
    background: #f8b936;
    color: #241a32;
    font-weight: bold;
}

.profile {
    margin-top: auto;
    padding: 15px;
    border-top: 1px solid #40354f;
}

.profile-info {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

.avatar {
    width: 33px;
    height: 33px;
    border-radius: 50%;
    background: #493e59;
    display: grid;
    place-items: center;
}

.profile small {
    display: block;
    color: #d1c8dc;
    margin-top: 3px;
    font-style: italic;
}

.logout {
    display: block;
    padding: 9px;
    text-align: center;
    border: 1px solid #a99bb9;
    border-radius: 25px;
    color: white;
    text-decoration: none;
    font-style: italic;
}

.main {
    margin-left: 206px;
    min-height: 100vh;
}

.topbar {
    height: 69px;
    background: white;
    border-bottom: 1px solid #e5ddec;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 25px;
}

.topbar h2 {
    font-size: 19px;
}

.notification {
    background: white;
    border: 1px solid #a99ab9;
    border-radius: 25px;
    padding: 9px 16px;
    font-weight: bold;
    text-decoration: none;
    color: #241a36;
}

.notification span {
    background: #f7b936;
    padding: 4px 9px;
    border-radius: 20px;
    margin-left: 7px;
}

.content {
    padding: 22px 25px;
}

.hero {
    background: #632e8b;
    color: white;
    padding: 27px 25px;
    border-radius: 21px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 25px;
}

.hero-label {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}

.hero-label span {
    background: #f8b936;
    color: #241a32;
    padding: 5px 11px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: bold;
}

.hero-label .days {
    background: #8054a0;
    color: white;
}

.hero h1 {
    font-size: 27px;
    margin-bottom: 10px;
}

.hero p {
    color: #e7dced;
    font-size: 13px;
    margin-bottom: 12px;
}

.hero-status {
    background: #dff3ff;
    color: #17527a;
    display: inline-block;
    padding: 5px 11px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: bold;
}

.btn-yellow {
    display: inline-block;
    background: #f8b936;
    color: #241a32;
    padding: 13px 22px;
    border-radius: 25px;
    text-decoration: none;
    font-size: 13px;
    font-weight: bold;
    white-space: nowrap;
}

.btn-yellow:hover {
    background: #ffcb58;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: 1.45fr 1fr;
    gap: 22px;
}

.section {
    margin-bottom: 23px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.section h3 {
    font-size: 16px;
}

.see-all {
    color: #632e8b;
    font-size: 12px;
    font-weight: bold;
}

.search-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}

.search-card {
    display: block;
    background: white;
    border: 1px solid #e6ddef;
    border-radius: 17px;
    padding: 15px;
    text-decoration: none;
    color: #241a36;
    transition: .2s;
}

.search-card:hover,
.event-card:hover,
.bill-card:hover {
    border-color: #a98bc3;
    transform: translateY(-2px);
}

.search-card h4 {
    color: #632e8b;
    font-size: 14px;
    margin-bottom: 7px;
}

.search-card p {
    color: #756b80;
    font-size: 12px;
    line-height: 1.5;
}

.event-card,
.bill-card {
    display: block;
    background: white;
    border: 1px solid #e6ddef;
    border-radius: 16px;
    padding: 15px;
    margin-bottom: 10px;
    text-decoration: none;
    color: #241a36;
    transition: .2s;
}

.event-title {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 7px;
    flex-wrap: wrap;
}

.event-title strong {
    font-size: 13px;
}

.event-card p,
.bill-card p {
    color: #756b80;
    font-size: 12px;
}

.badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: bold;
}

.badge-draft {
    background: #e7e7e7;
    color: #555;
}

.badge-process {
    background: #dff3ff;
    color: #17527a;
}

.bill-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
}

.bill-card strong {
    font-size: 13px;
    display: block;
    margin-bottom: 6px;
}

.bill-card p {
    line-height: 1.5;
}

.evaluation-card {
    display: block;
    background: white;
    border: 1px solid #e6ddef;
    border-radius: 16px;
    padding: 17px;
    text-decoration: none;
    color: #241a36;
    font-weight: bold;
    font-size: 13px;
}

.btn-outline {
    display: block;
    text-align: center;
    padding: 12px;
    border: 1px solid #a99bb9;
    color: #241a36;
    background: white;
    border-radius: 25px;
    text-decoration: none;
    font-weight: bold;
    font-size: 13px;
    margin-top: 22px;
}

.btn-outline:hover {
    background: #f4eef9;
}

@media(max-width: 1000px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}

@media(max-width: 700px) {
    .sidebar {
        width: 145px;
    }

    .main {
        margin-left: 145px;
    }

    .content {
        padding: 15px;
    }

    .topbar {
        padding: 0 15px;
    }

    .hero {
        align-items: flex-start;
        flex-direction: column;
    }

    .hero h1 {
        font-size: 22px;
    }

    .search-cards {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<aside class="sidebar">

    <div class="brand">
        <h2>Pesta Kita</h2>
        <small>Klien</small>
    </div>

    <nav class="menu">
        <a href="{{ route('klien.dashboard') }}" class="active">Beranda</a>
        <a href="{{ route('klien.rancang') }}">Rancang acara</a>
        <a href="{{ route('klien.vendor-venue') }}">Vendor & venue</a>
        <a href="{{ route('klien.sewa-peralatan') }}">Sewa peralatan</a>
        <a href="{{ route('klien.pesanan-tagihan') }}">Pesanan & tagihan</a>
        <a href="{{ route('klien.riwayat') }}">Riwayat & evaluasi</a>
        <a href="{{ route('klien.profil') }}">Profil saya</a>
    </nav>

    <div class="profile">
        <div class="profile-info">
            <div class="avatar">N</div>
            <div>
                <b>Nadia Putri</b>
                <small>nadia@contoh.id</small>
            </div>
        </div>

        <x-logout />
    </div>

</aside>

<main class="main">

    <header class="topbar">
        <h2>Halo, Nadia Putri</h2>

        <a href="{{ route('klien.notifikasi') }}" class="notification">
            ♧ Notifikasi <span>3</span>
        </a>
    </header>

    <div class="content">

        <!-- ACARA TERDEKAT -->
        <section class="hero">

            <div>
                <div class="hero-label">
                    <span>Acara terdekat</span>
                    <span class="days">H-9</span>
                </div>

                <h1>Ulang Tahun ke-7 Nadia</h1>

                <p>
                    Sabtu, 24 Oktober 2026 · 40 tamu · Putri Salju
                </p>

                <span class="hero-status">Dalam pemesanan</span>
            </div>

            <a href="{{ route('klien.detail-acara') }}" class="btn-yellow">
                Buka acara
            </a>

        </section>

        <div class="dashboard-grid">

            <div>

                <!-- PENCARIAN -->
                <section class="section">

                    <div class="section-header">
                        <h3>Cari untuk acara Anda</h3>
                    </div>

                    <div class="search-cards">

                        <a href="{{ route('klien.vendor-venue', ['jenis' => 'vendor']) }}"
                           class="search-card">
                            <h4>Vendor</h4>
                            <p>Katering, dekorasi, dan jasa lain</p>
                        </a>

                        <a href="{{ route('klien.vendor-venue', ['jenis' => 'venue']) }}"
                           class="search-card">
                            <h4>Venue</h4>
                            <p>Tempat acara dan layout ruangan</p>
                        </a>

                        <a href="{{ route('klien.sewa-peralatan') }}"
                           class="search-card">
                            <h4>Peralatan</h4>
                            <p>Sewa per unit, stok dicek otomatis</p>
                        </a>

                    </div>

                </section>

                <!-- ACARA MENDATANG -->
                <section class="section">

                    <div class="section-header">
                        <h3>Acara mendatang lainnya</h3>

                        <a href="{{ route('klien.rancang') }}" class="see-all">
                            Lihat semua
                        </a>
                    </div>

                    <a href="{{ route('klien.detail-acara') }}" class="event-card">

                        <div class="event-title">
                            <strong>Ulang Tahun Kakek 70 Tahun</strong>
                            <span class="badge badge-draft">Draft</span>
                        </div>

                        <p>Sabtu, 14 November 2026 · 80 tamu · Tradisional</p>

                    </a>

                    <a href="{{ route('klien.detail-acara') }}" class="event-card">

                        <div class="event-title">
                            <strong>Pesta Sweet Seventeen Sinta</strong>
                            <span class="badge badge-process">Dalam pemesanan</span>
                        </div>

                        <p>Minggu, 6 Desember 2026 · 60 tamu · Taman bunga</p>

                    </a>

                </section>

            </div>

            <div>

                <!-- TAGIHAN -->
                <section class="section">

                    <div class="section-header">
                        <h3>Tagihan menunggu dibayar</h3>
                    </div>

                    <p style="font-size:12px;color:#756b80;margin-bottom:12px">
                        2 tagihan · total Rp 2.350.000
                    </p>

                    <a href="{{ route('klien.detail-pesanan') }}" class="bill-card">
                        <div>
                            <strong>Aula Melati</strong>
                            <p>Rp 1.800.000 · Ulang Tahun ke-7 Nadia</p>
                        </div>
                    </a>

                    <a href="{{ route('klien.detail-pesanan') }}" class="bill-card">
                        <div>
                            <strong>Dekorasi balon dan backdrop</strong>
                            <p>Rp 550.000 · Ulang Tahun ke-7 Nadia</p>
                        </div>
                    </a>

                </section>

                <!-- EVALUASI -->
                <section class="section">

                    <div class="section-header">
                        <h3>Menunggu evaluasi</h3>
                    </div>

                    <a href="{{ route('klien.evaluasi') }}" class="evaluation-card">
                        Syukuran Ulang Tahun Mama
                    </a>

                </section>

                <a href="{{ route('klien.buat-konsep') }}" class="btn-outline">
                    Buat konsep acara baru
                </a>

            </div>

        </div>

    </div>

</main>

</body>
</html>
