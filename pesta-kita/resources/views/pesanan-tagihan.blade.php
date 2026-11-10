
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesanan & Tagihan | Pesta Kita</title>

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
    background: #40334f;
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
    color: #241a36;
    text-decoration: none;
}

.notification span {
    background: #f7b936;
    padding: 4px 9px;
    border-radius: 20px;
    margin-left: 7px;
}

.content {
    padding: 25px;
}

.filter-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 22px;
}

.filters {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 4px;
    background: white;
    border: 1px solid #ded2e8;
    border-radius: 30px;
}

.filter-btn {
    border: none;
    background: transparent;
    padding: 11px 20px;
    border-radius: 25px;
    cursor: pointer;
    font-size: 13px;
    color: #241a36;
}

.filter-btn.active {
    background: #632e8b;
    color: white;
}

.filter-btn:hover:not(.active) {
    background: #f4eef9;
}

.order-count {
    color: #756b80;
    font-size: 12px;
}

.orders {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.order-card {
    display: block;
    background: white;
    border: 1px solid #e6ddef;
    border-left: 4px solid #f7b936;
    border-radius: 17px;
    padding: 17px 18px;
    text-decoration: none;
    color: #241a36;
    transition: .2s;
}

.order-card:hover {
    border-color: #a98bc3;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(50, 30, 70, .06);
}

.order-card.hidden {
    display: none;
}

.order-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 15px;
}

.order-title {
    font-size: 15px;
    font-weight: bold;
    margin-bottom: 7px;
}

.order-category {
    font-size: 12px;
    color: #756b80;
}

.status-group {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 7px;
}

.status {
    display: inline-block;
    padding: 5px 11px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: bold;
    white-space: nowrap;
    font-style: italic;
}

.pending {
    background: #f8b936;
    color: #382400;
}

.waiting {
    background: #e1f3ff;
    color: #17527a;
}

.confirmation {
    background: #fff1c7;
    color: #815000;
}

.verified {
    background: #d0f8e9;
    color: #075e43;
}

.rejected {
    background: #ffe0e0;
    color: #a32121;
}

.order-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-top: 16px;
}

.event-date {
    font-size: 12px;
    color: #756b80;
}

.price {
    font-size: 14px;
    font-weight: bold;
    white-space: nowrap;
}

.empty-state {
    display: none;
    padding: 45px 20px;
    background: white;
    border: 1px solid #e6ddef;
    border-radius: 16px;
    text-align: center;
    color: #756b80;
}

@media(max-width: 800px) {
    .sidebar {
        width: 165px;
    }

    .main {
        margin-left: 165px;
    }

    .content {
        padding: 15px;
    }

    .filter-row {
        align-items: flex-start;
        flex-direction: column;
    }

    .filters {
        max-width: 100%;
        flex-wrap: wrap;
    }

    .filter-btn {
        padding: 9px 12px;
    }

    .order-top {
        flex-direction: column;
    }

    .status-group {
        justify-content: flex-start;
    }
}

@media(max-width: 500px) {
    .sidebar {
        width: 125px;
    }

    .main {
        margin-left: 125px;
    }

    .brand h2 {
        font-size: 17px;
    }

    .menu a {
        padding: 10px 7px;
        font-size: 11px;
    }

    .topbar {
        padding: 0 12px;
    }

    .topbar h2 {
        font-size: 14px;
    }

    .order-bottom {
        align-items: flex-start;
        flex-direction: column;
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
        <a href="{{ route('klien.dashboard') }}">Beranda</a>
        <a href="{{ route('klien.rancang') }}">Rancang acara</a>
        <a href="{{ route('klien.vendor-venue') }}">Vendor & venue</a>
        <a href="{{ route('klien.sewa-peralatan') }}">Sewa peralatan</a>
        <a href="{{ route('klien.pesanan-tagihan') }}" class="active">Pesanan & tagihan</a>
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
        <h2>Pesanan & tagihan</h2>

        <a href="{{ route('klien.notifikasi') }}" class="notification">
            ♧ Notifikasi <span>3</span>
        </a>
    </header>

    <section class="content">

        <div class="filter-row">

            <div class="filters">
                <button class="filter-btn active" data-filter="semua">Semua</button>
                <button class="filter-btn" data-filter="vendor">Vendor</button>
                <button class="filter-btn" data-filter="venue">Venue</button>
                <button class="filter-btn" data-filter="peralatan">Sewa peralatan</button>
            </div>

            <div class="order-count" id="orderCount">5 pesanan</div>

        </div>

        <div class="orders" id="ordersList">

            <!-- PESANAN 1 -->
            <a href="{{ route('klien.detail-pesanan') }}"
               class="order-card"
               data-category="venue">

                <div class="order-top">
                    <div>
                        <div class="order-title">Aula serbaguna</div>
                        <div class="order-category">Venue · Aula Melati</div>
                    </div>

                    <div class="status-group">
                        <span class="status pending">Perlu dibayar</span>
                        <span class="status waiting">Diterima, menunggu pembayaran</span>
                    </div>
                </div>

                <div class="order-bottom">
                    <div class="event-date">
                        Ulang Tahun ke-7 Nadia · 24 Okt 2026
                    </div>
                    <div class="price">Rp 1.800.000</div>
                </div>

            </a>

            <!-- PESANAN 2 -->
            <a href="{{ route('klien.detail-pesanan') }}"
               class="order-card"
               data-category="vendor">

                <div class="order-top">
                    <div>
                        <div class="order-title">Dekorasi balon dan backdrop</div>
                        <div class="order-category">Vendor · Balon & Co</div>
                    </div>

                    <div class="status-group">
                        <span class="status pending">Perlu dibayar</span>
                        <span class="status waiting">Diterima, menunggu pembayaran</span>
                    </div>
                </div>

                <div class="order-bottom">
                    <div class="event-date">
                        Ulang Tahun ke-7 Nadia · 24 Okt 2026
                    </div>
                    <div class="price">Rp 550.000</div>
                </div>

            </a>

            <!-- PESANAN 3 -->
            <a href="{{ route('klien.detail-pesanan') }}"
               class="order-card"
               data-category="vendor">

                <div class="order-top">
                    <div>
                        <div class="order-title">Katering prasmanan</div>
                        <div class="order-category">Vendor · Dapur Bu Rina</div>
                    </div>

                    <div class="status-group">
                        <span class="status confirmation">Menunggu konfirmasi</span>
                    </div>
                </div>

                <div class="order-bottom">
                    <div class="event-date">
                        Ulang Tahun ke-7 Nadia · 24 Okt 2026
                    </div>
                    <div class="price">-</div>
                </div>

            </a>

            <!-- PESANAN 4 -->
            <a href="{{ route('klien.detail-pesanan') }}"
               class="order-card"
               data-category="peralatan">

                <div class="order-top">
                    <div>
                        <div class="order-title">Sound system 1000 watt ×2</div>
                        <div class="order-category">Sewa peralatan · Sound & MC Ceria</div>
                    </div>

                    <div class="status-group">
                        <span class="status verified">Pembayaran terverifikasi</span>
                    </div>
                </div>

                <div class="order-bottom">
                    <div class="event-date">
                        Ulang Tahun ke-7 Nadia · 24 Okt 2026
                    </div>
                    <div class="price">Rp 700.000</div>
                </div>

            </a>

            <!-- PESANAN 5 -->
            <a href="{{ route('klien.detail-pesanan') }}"
               class="order-card"
               data-category="vendor">

                <div class="order-top">
                    <div>
                        <div class="order-title">Badut dan MC anak</div>
                        <div class="order-category">Vendor · Ceria Entertainment</div>
                    </div>

                    <div class="status-group">
                        <span class="status rejected">Ditolak</span>
                    </div>
                </div>

                <div class="order-bottom">
                    <div class="event-date">
                        Ulang Tahun ke-7 Nadia · 24 Okt 2026
                    </div>
                    <div class="price">-</div>
                </div>

            </a>

        </div>

        <div class="empty-state" id="emptyState">
            Belum ada pesanan pada kategori ini.
        </div>

    </section>

</main>

<script>
const filterButtons = document.querySelectorAll('.filter-btn');
const orderCards = document.querySelectorAll('.order-card');
const orderCount = document.getElementById('orderCount');
const emptyState = document.getElementById('emptyState');

filterButtons.forEach(button => {
    button.addEventListener('click', function() {

        const selectedFilter = this.dataset.filter;
        let visibleCount = 0;

        filterButtons.forEach(item => {
            item.classList.remove('active');
        });

        this.classList.add('active');

        orderCards.forEach(card => {

            const category = card.dataset.category;

            if (selectedFilter === 'semua' || category === selectedFilter) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }

        });

        orderCount.textContent = visibleCount + ' pesanan';

        emptyState.style.display = visibleCount === 0 ? 'block' : 'none';

    });
});
</script>

</body>
</html>
