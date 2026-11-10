
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profil Saya | Pesta Kita</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])

<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: Arial, sans-serif;
    background: #fbf9fd;
    color: #241a36;
    font-size: 14px;
}

.sidebar {
    position: fixed;
    top: 0; left: 0; bottom: 0;
    width: 206px;
    background: #201632;
    color: white;
    display: flex;
    flex-direction: column;
}

.brand { padding: 25px 17px 15px; }
.brand h2 { font-size: 21px; margin-bottom: 5px; }
.brand small { color: #d4cadd; font-style: italic; font-size: 11px; }

.menu { padding: 12px 10px; }
.menu a {
    display: block;
    padding: 12px 13px;
    margin-bottom: 4px;
    text-decoration: none;
    color: #eee8f3;
    border-radius: 10px;
    font-style: italic;
}
.menu a:hover { background: #40334f; }
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

.main { margin-left: 206px; min-height: 100vh; }
.topbar {
    height: 69px;
    background: white;
    border-bottom: 1px solid #e5ddec;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 25px;
}
.topbar h2 { font-size: 19px; }

.notification {
    background: white;
    border: 1px solid #a99ab9;
    border-radius: 25px;
    padding: 9px 16px;
    font-weight: bold;
    cursor: pointer;
}
.notification span {
    background: #f7b936;
    padding: 4px 9px;
    border-radius: 20px;
    margin-left: 7px;
}

.content { padding: 25px; }

.card {
    width: 540px;
    max-width: 100%;
    background: white;
    border: 1px solid #e7dfee;
    border-radius: 17px;
    padding: 23px 19px 20px;
    margin-bottom: 20px;
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}
.card-header h3 { font-size: 17px; }

.role {
    background: #f3eafa;
    color: #632e8b;
    border-radius: 20px;
    padding: 5px 12px;
    font-size: 11px;
}

.field { margin-bottom: 18px; }
.field label {
    display: block;
    font-weight: bold;
    font-size: 12px;
    margin-bottom: 8px;
}
.field input {
    width: 100%;
    height: 43px;
    border: 1.5px solid #a797ba;
    border-radius: 11px;
    padding: 0 12px;
    font-size: 13px;
    color: #30253e;
    outline: none;
}
.field input:focus { border-color: #632e8b; }

.field small {
    display: block;
    color: #786e82;
    font-size: 11px;
    margin-top: 7px;
}

.btn {
    padding: 12px 22px;
    border-radius: 25px;
    border: none;
    font-weight: bold;
    cursor: pointer;
}
.btn-primary {
    background: #632e8b;
    color: white;
}
.btn-primary:hover { background: #512370; }

@media(max-width:700px) {
    .sidebar { width: 145px; }
    .main { margin-left: 145px; }
    .content { padding: 15px; }
    .topbar { padding: 0 15px; }
    .card { padding: 18px 14px; }
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
        <a href="{{ route('klien.pesanan-tagihan') }}">Pesanan & tagihan</a>
        <a href="{{ route('klien.riwayat') }}">Riwayat & evaluasi</a>
        <a href="{{ route('klien.profil') }}" class="active">Profil saya</a>
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
        <h2>Profil saya</h2>
        <button class="notification" onclick="location.href='{{ url('/klien/notifikasi') }}'">
            ♧ Notifikasi <span>3</span>
        </button>
    </header>

    <div class="content">

        <!-- DATA AKUN -->
        <form class="card" id="profileForm">
            <div class="card-header">
                <h3>Data akun</h3>
                <span class="role">Klien</span>
            </div>

            <div class="field">
                <label for="name">Nama lengkap</label>
                <input type="text" id="name" value="Nadia Putri" required>
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" value="nadia@contoh.id" required>
            </div>

            <div class="field">
                <label for="phone">Nomor telepon (opsional)</label>
                <input type="tel" id="phone" value="0812-3456-7890">
            </div>

            <button type="submit" class="btn btn-primary">
                Simpan profil
            </button>
        </form>

        <!-- GANTI PASSWORD -->
        <form class="card" id="passwordForm">
            <div class="card-header">
                <h3>Ganti kata sandi</h3>
            </div>

            <div class="field">
                <label for="currentPassword">Kata sandi saat ini</label>
                <input type="password" id="currentPassword"
                       value="password" required>
            </div>

            <div class="field">
                <label for="newPassword">Kata sandi baru</label>
                <input type="password" id="newPassword"
                       minlength="8" required>
                <small>Minimal 8 karakter.</small>
            </div>

            <div class="field">
                <label for="confirmPassword">Ulangi kata sandi baru</label>
                <input type="password" id="confirmPassword" required>
            </div>

            <button type="submit" class="btn btn-primary">
                Ganti kata sandi
            </button>
        </form>

    </div>
</main>

<script>
document.getElementById('profileForm').addEventListener('submit', function(e) {
    e.preventDefault();

    alert('Tampilan profil berhasil diperbarui. Integrasi database belum diaktifkan.');
});

document.getElementById('passwordForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const current = document.getElementById('currentPassword').value;
    const newPass = document.getElementById('newPassword').value;
    const confirm = document.getElementById('confirmPassword').value;

    if (newPass.length < 8) {
        alert('Kata sandi baru minimal 8 karakter.');
        return;
    }

    if (newPass !== confirm) {
        alert('Konfirmasi kata sandi tidak cocok!');
        return;
    }

    if (newPass === current) {
        alert('Kata sandi baru harus berbeda dari kata sandi saat ini.');
        return;
    }

    alert('Validasi berhasil. Integrasi perubahan kata sandi dengan database belum diaktifkan.');
});
</script>

</body>
</html>
