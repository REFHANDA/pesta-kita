
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Evaluasi Acara | Pesta Kita</title>
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

.back {
    display: inline-block;
    color: #67308d;
    font-weight: bold;
    margin-bottom: 22px;
    text-decoration: underline;
}

.form-card {
    width: 540px;
    max-width: 100%;
    background: white;
    border: 1px solid #e7dfee;
    border-radius: 17px;
    padding: 23px 19px 20px;
}

.event-info {
    color: #756b80;
    margin-bottom: 24px;
}

.event-info strong {
    color: #271c38;
}

.form-card h3 {
    font-size: 17px;
    margin-bottom: 12px;
}

.ratings {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 10px;
    margin-bottom: 10px;
}

.rating {
    height: 53px;
    border: 1.5px solid #a797ba;
    border-radius: 14px;
    background: white;
    color: #291e3a;
    font-size: 20px;
    font-weight: bold;
    cursor: pointer;
    transition: .2s;
}

.rating:hover {
    border-color: #632e8b;
}

.rating.selected {
    background: #f7b936;
    border: 2px solid #632e8b;
}

.rating-labels {
    display: flex;
    justify-content: space-between;
    color: #756b80;
    font-size: 11px;
    margin-bottom: 24px;
}

.field {
    margin-bottom: 20px;
}

.field label {
    display: block;
    font-weight: bold;
    font-size: 12px;
    margin-bottom: 8px;
}

.field textarea {
    display: block;
    width: 100%;
    height: 77px;
    border: 1.5px solid #a797ba;
    border-radius: 11px;
    padding: 12px;
    font-size: 13px;
    color: #30253e;
    resize: vertical;
    outline: none;
    font-family: inherit;
}

.field textarea:focus {
    border-color: #632e8b;
}

.field small {
    display: block;
    color: #786e82;
    font-size: 11px;
    margin-top: 7px;
}

.actions {
    display: flex;
    gap: 10px;
    margin-top: 19px;
}

.btn {
    padding: 13px 25px;
    border-radius: 28px;
    border: none;
    font-weight: bold;
    cursor: pointer;
}

.btn-primary {
    background: #632e8b;
    color: white;
}

.btn-secondary {
    background: white;
    color: #30243e;
    border: 1.5px solid #a797ba;
}

.btn:hover {
    opacity: .9;
}

@media(max-width:700px) {
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

    .ratings {
        gap: 5px;
    }

    .rating {
        height: 45px;
    }

    .actions {
        flex-wrap: wrap;
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
        <a href="{{ route('klien.pesanan-tagihan') }}">Pesanan & tagihan</a>
        <a href="{{ route('klien.riwayat') }}" class="active">Riwayat & evaluasi</a>
        <a href="#">Profil saya</a>
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
        <h2>Evaluasi · Syukuran Ulang Tahun Mama</h2>
        <button class="notification">♧ Notifikasi <span>3</span></button>
    </header>

    <div class="content">

        <a href="{{ route('klien.riwayat') }}" class="back">
            ← Kembali ke acara
        </a>

        <form class="form-card" id="evaluationForm">

            <p class="event-info">
                <strong>Syukuran Ulang Tahun Mama</strong>
                · Sabtu, 3 Oktober 2026
            </p>

            <h3>Penilaian keseluruhan</h3>

            <div class="ratings">
                <button type="button" class="rating" data-value="1">1</button>
                <button type="button" class="rating" data-value="2">2</button>
                <button type="button" class="rating" data-value="3">3</button>
                <button type="button" class="rating selected" data-value="4">4</button>
                <button type="button" class="rating" data-value="5">5</button>
            </div>

            <input type="hidden" name="rating" id="ratingValue" value="4">

            <div class="rating-labels">
                <span>1 = kurang baik</span>
                <span>5 = sangat baik</span>
            </div>

            <div class="field">
                <label for="comment">Komentar (opsional)</label>
                <textarea id="comment" name="comment">Dekorasi dan katering sangat memuaskan.</textarea>
                <small>Apa yang berjalan baik?</small>
            </div>

            <div class="field">
                <label for="suggestion">Saran perbaikan (opsional)</label>
                <textarea id="suggestion" name="suggestion">Sound system sebaiknya dicek lebih awal.</textarea>
                <small>Apa yang bisa lebih baik di acara berikutnya?</small>
            </div>

        </form>

        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="saveEvaluation()">
                Simpan evaluasi
            </button>

            <a href="{{ route('klien.riwayat') }}" class="btn btn-secondary" style="text-decoration:none">
                Kembali
            </a>
        </div>

    </div>

</main>

<script>
const ratingButtons = document.querySelectorAll('.rating');
const ratingValue = document.getElementById('ratingValue');

ratingButtons.forEach(button => {
    button.addEventListener('click', function() {
        ratingButtons.forEach(item => item.classList.remove('selected'));

        this.classList.add('selected');
        ratingValue.value = this.dataset.value;
    });
});

function saveEvaluation() {
    const rating = ratingValue.value;
    const comment = document.getElementById('comment').value;
    const suggestion = document.getElementById('suggestion').value;

    if (!confirm('Simpan evaluasi dengan nilai ' + rating + '?')) {
        return;
    }

    alert('Evaluasi siap disimpan. Integrasi database belum diaktifkan.');
}
</script>

</body>
</html>
