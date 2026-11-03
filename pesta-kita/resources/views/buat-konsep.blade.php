
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Buat Konsep Acara | Pesta Kita</title>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --purple: #5B2887;
    --dark: #211733;
    --yellow: #F8B936;
    --bg: #FAF8FD;
    --border: #E8E0EF;
    --muted: #777087;
}

body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: var(--bg);
    color: var(--dark);
}

.layout {
    display: flex;
    min-height: 100vh;
}

/* SIDEBAR */
.sidebar {
    width: 220px;
    background: var(--dark);
    color: white;
    position: fixed;
    inset: 0 auto 0 0;
    display: flex;
    flex-direction: column;
    padding: 25px 12px 15px;
    z-index: 10;
}

.brand {
    padding: 0 15px 25px;
}

.brand h2 {
    font-size: 21px;
    font-weight: 800;
}

.brand span {
    font-size: 11px;
    color: #c9bfd3;
    font-style: italic;
}

.menu {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.menu a {
    text-decoration: none;
    color: #ddd4e5;
    padding: 12px 14px;
    border-radius: 10px;
    font-size: 13px;
    font-style: italic;
    transition: .2s;
}

.menu a:hover {
    background: #49345e;
}

.menu a.active {
    background: var(--yellow);
    color: var(--dark);
}

.profile {
    margin-top: auto;
    border-top: 1px solid #443650;
    padding: 17px 5px 0;
}

.profile-info {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

.avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #493957;
    display: grid;
    place-items: center;
    font-weight: 700;
}

.profile-info strong {
    font-size: 12px;
}

.profile-info small {
    display: block;
    font-size: 10px;
    color: #c9bfd3;
}

.logout {
    display: block;
    text-align: center;
    color: white;
    border: 1px solid #9b8ba9;
    padding: 9px;
    border-radius: 25px;
    font-size: 12px;
    text-decoration: none;
}

/* MAIN */
.main {
    margin-left: 220px;
    width: calc(100% - 220px);
    min-height: 100vh;
}

.topbar {
    height: 75px;
    background: white;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 30px;
}

.topbar h3 {
    font-size: 19px;
    font-weight: 800;
}

.notification {
    border: 1px solid #9278a7;
    border-radius: 30px;
    padding: 9px 17px;
    font-size: 12px;
    font-weight: 700;
    background: white;
}

.notification span {
    background: var(--yellow);
    padding: 4px 9px;
    border-radius: 20px;
    margin-left: 8px;
}

/* CONTENT */
.content {
    padding: 20px 30px 40px;
}

.form-wrapper {
    max-width: 700px;
}

.form-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 17px;
    padding: 20px;
    margin-bottom: 16px;
}

.form-card h4 {
    font-size: 15px;
    font-weight: 800;
    margin-bottom: 18px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    margin-bottom: 7px;
}

.form-group input,
.form-group textarea {
    display: block;
    width: 100%;
    border: 1px solid #A99AB9;
    border-radius: 9px;
    padding: 11px 12px;
    font-family: inherit;
    font-size: 12px;
    color: var(--dark);
    background: white;
    outline: none;
    transition: .2s;
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: var(--purple);
    box-shadow: 0 0 0 2px rgba(91,40,135,.10);
}

.form-group textarea {
    min-height: 100px;
    resize: vertical;
    line-height: 1.6;
}

.form-group small {
    display: block;
    margin-top: 6px;
    font-size: 10px;
    color: var(--muted);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn {
    padding: 11px 22px;
    border-radius: 25px;
    font-family: inherit;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    transition: .2s;
}

.btn-primary {
    border: 1px solid var(--purple);
    background: var(--purple);
    color: white;
}

.btn-primary:hover {
    background: #472068;
}

.btn-secondary {
    border: 1px solid #9278a7;
    background: white;
    color: var(--dark);
}

.btn-secondary:hover {
    background: #f1ebf7;
}

/* RESPONSIVE */
@media(max-width: 650px) {
    .sidebar {
        width: 75px;
        padding: 20px 7px;
    }

    .brand {
        padding: 0 0 25px;
        text-align: center;
    }

    .brand h2 {
        font-size: 11px;
    }

    .brand span,
    .menu a span,
    .profile-info div,
    .logout {
        display: none;
    }

    .menu a {
        text-align: center;
        font-size: 0;
        padding: 13px 5px;
    }

    .menu a::first-letter {
        font-size: 17px;
    }

    .profile-info {
        justify-content: center;
    }

    .main {
        margin-left: 75px;
        width: calc(100% - 75px);
    }

    .topbar {
        height: 65px;
        padding: 0 12px;
    }

    .topbar h3 {
        font-size: 13px;
    }

    .notification {
        font-size: 10px;
        padding: 7px;
    }

    .content {
        padding: 15px 12px;
    }

    .form-card {
        padding: 16px;
    }

    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
}
</style>
</head>

<body>

@php
$user = auth()->user();
$nama = $user->name ?? 'Nama Klien';
$email = $user->email ?? 'Email belum tersedia';
$inisial = strtoupper(substr($nama, 0, 1));
@endphp

<div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="brand">
            <h2>Pesta Kita</h2>
            <span>Klien</span>
        </div>

        <nav class="menu">
            <a href="{{ route('klien.dashboard') }}">⌂ <span>Beranda</span></a>

            <a href="{{ route('klien.rancang') }}" class="active">
                ✎ <span>Rancang acara</span>
            </a>

            <a href="#">♙ <span>Vendor & venue</span></a>
            <a href="#">▣ <span>Sewa peralatan</span></a>
            <a href="#">▤ <span>Pesanan & tagihan</span></a>
            <a href="{{ route('klien.riwayat') }}">◷ <span>Riwayat & evaluasi</span></a>
            <a href="#">⚙ <span>Profil saya</span></a>
        </nav>

        <div class="profile">
            <div class="profile-info">
                <div class="avatar">{{ $inisial }}</div>
                <div>
                    <strong>{{ $nama }}</strong>
                    <small>{{ $email }}</small>
                </div>
            </div>

            <a href="#" class="logout">Keluar</a>
        </div>

    </aside>

    <!-- MAIN CONTENT -->
    <main class="main">

        <header class="topbar">
            <h3>Buat konsep acara</h3>

            <button class="notification">
                ♧ Notifikasi <span>3</span>
            </button>
        </header>

        <div class="content">

            <div class="form-wrapper">

                <!-- DASAR ACARA -->
                <section class="form-card">

                    <h4>Dasar acara</h4>

                    <form id="conceptForm">

                        <div class="form-group">
                            <label for="nama">Nama acara</label>

                            <input
                                type="text"
                                id="nama"
                                name="nama"
                                placeholder="Contoh: Ulang Tahun ke-7"
                                required
                            >

                            <small>Contoh: Ulang Tahun ke-7 Nadia</small>
                        </div>

                        <div class="form-row">

                            <div class="form-group">
                                <label for="tanggal">Tanggal acara</label>

                                <input
                                    type="date"
                                    id="tanggal"
                                    name="tanggal"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="tamu">Jumlah tamu</label>

                                <input
                                    type="number"
                                    id="tamu"
                                    name="jumlah_tamu"
                                    min="1"
                                    placeholder="Contoh: 40"
                                    required
                                >
                            </div>

                        </div>

                        <div class="form-group">
                            <label for="tema">Tema (opsional)</label>

                            <input
                                type="text"
                                id="tema"
                                name="tema"
                                placeholder="Contoh: Putri Salju"
                            >
                        </div>

                    </form>

                </section>

                <!-- KONSEP DAN ANGGARAN -->
                <section class="form-card">

                    <h4>Konsep & anggaran</h4>

                    <div class="form-group">
                        <label for="deskripsi">Deskripsi konsep (opsional)</label>

                        <textarea
                            id="deskripsi"
                            name="deskripsi"
                            form="conceptForm"
                            placeholder="Ceritakan konsep acara yang ingin Anda buat..."
                        ></textarea>

                        <small>
                            Suasana, rangkaian acara, atau hal yang ingin dicapai.
                        </small>
                    </div>

                    <div class="form-group" style="margin-bottom:0">
                        <label for="anggaran">Anggaran (Rp, opsional)</label>

                        <input
                            type="number"
                            id="anggaran"
                            name="anggaran"
                            form="conceptForm"
                            min="0"
                            placeholder="Contoh: 5000000"
                        >

                        <small>
                            Dibandingkan dengan biaya terkonfirmasi di halaman acara.
                        </small>
                    </div>

                </section>

                <!-- BUTTON -->
                <div class="actions">

                    <button type="submit" form="conceptForm" class="btn btn-primary">
                        Simpan konsep
                    </button>

                    <a href="{{ route('klien.rancang') }}" class="btn btn-secondary">
                        Batal
                    </a>

                </div>

            </div>

        </div>

    </main>

</div>

<script>
document.getElementById('conceptForm').addEventListener('submit', function(e) {
    e.preventDefault();
    alert('Tampilan form sudah siap. Penyimpanan konsep memerlukan integrasi backend.');
});
</script>

</body>
</html>
