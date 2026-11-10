
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detail Pesanan | Pesta Kita</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])

<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;background:#fbf9fd;color:#251a37;font-size:14px}
button,input,textarea,select{font:inherit}
.sidebar{position:fixed;left:0;top:0;bottom:0;width:176px;background:#201632;color:white;display:flex;flex-direction:column;z-index:5}
.brand{padding:25px 20px 15px}
.brand h2{font-size:21px}
.brand small{font-style:italic;color:#d1c7dc}
.menu{padding:12px 10px}
.menu a{display:block;text-decoration:none;color:#eee5f4;padding:13px 12px;border-radius:10px;margin-bottom:3px;font-style:italic}
.menu a.active{background:#f7b936;color:#241a32;font-weight:bold}
.profile{margin-top:auto;border-top:1px solid #40344d;padding:17px 14px}
.profile-row{display:flex;align-items:center;gap:10px;margin-bottom:13px}
.avatar{background:#493d59;width:34px;height:34px;border-radius:50%;display:grid;place-items:center}
.profile small{display:block;color:#d0c6d9;margin-top:4px}
.logout{display:block;text-align:center;border:1px solid #a99ab9;color:white;border-radius:25px;padding:9px;text-decoration:none;font-style:italic}
.main{margin-left:176px;min-height:100vh}
.topbar{height:75px;background:white;border-bottom:1px solid #e6deec;display:flex;align-items:center;justify-content:space-between;padding:0 30px}
.topbar h2{font-size:20px}
.notification{border:1px solid #a999ba;border-radius:30px;padding:10px 17px;background:white;font-weight:bold}
.notification span{background:#f7b936;border-radius:20px;padding:4px 9px;margin-left:7px}
.content{max-width:1450px;margin:auto;padding:27px 30px 55px}
.back{display:inline-block;color:#65308b;font-weight:bold;margin-bottom:24px}
.columns{display:grid;grid-template-columns:1.5fr 1fr;gap:22px;align-items:start}
.card{background:white;border:1px solid #e5dced;border-radius:19px;padding:23px;margin-bottom:22px}
.card h3{font-size:19px;margin-bottom:19px}
.card-heading{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:20px}
.card-heading h3{margin:0}
.status{background:#dff2ff;color:#12547a;padding:6px 12px;border-radius:20px;font-size:11px;font-weight:bold}
.steps{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:20px}
.step{border:1px solid #d9cce5;border-radius:15px;padding:12px 10px;line-height:1.5;color:#665c72;font-size:12px;min-height:78px}
.step strong{display:block;margin-bottom:4px}
.step.done{background:#effcf6;border-color:#69dfb2;color:#12694d}
.step.current{background:#f4eef9;border:2px solid #713a9a;color:#512574;font-weight:bold}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px}
.info{background:#f3edf9;border-radius:16px;padding:16px}
.info small{display:block;color:#756b81;margin-bottom:5px}
.info strong{font-size:14px}
.note{line-height:1.7;margin:18px 0;color:#62586e}
.btn{border:0;border-radius:28px;padding:13px 22px;cursor:pointer;font-weight:bold}
.btn-purple{background:#632d8b;color:white}
.btn-red{background:white;color:#d32619;border:1.5px solid #d32619}
.btn-outline{background:white;border:1.5px solid #9786aa;color:#34243f}
.invoice-number{color:#756b80;margin:20px 0 10px}
.invoice-desc{color:#62576f;margin-bottom:9px}
.amount{font-size:34px;color:#65308d;font-weight:bold;margin:8px 0 17px}
.helper{color:#766d80;line-height:1.6;font-size:13px}
.line{border:0;border-top:1px solid #eee8f1;margin:20px 0}
.field-label{display:block;font-weight:bold;margin:15px 0 9px}
.file-input{width:100%;border:1px solid #a797b8;border-radius:12px;padding:11px;background:white}
.file-help{font-size:12px;color:#786f83;margin-top:8px}
.full{width:100%;margin-top:19px;padding:16px}
.payment-history{background:#f3edf9;padding:15px;border-radius:15px;line-height:1.8}
.rejected{background:#ffe3e2;color:#a3241d;padding:5px 12px;border-radius:20px;font-weight:bold;font-size:12px}
.history-link{color:#251a37;font-weight:bold;margin-left:8px}
.layout-card{margin-top:0}
.layout-card h3{margin-bottom:25px}
.field{margin-bottom:19px}
.field label{display:block;font-weight:bold;margin-bottom:10px}
.field select,.field textarea{width:100%;border:1.5px solid #a697b9;border-radius:13px;padding:14px;color:#51465f;background:white}
.field textarea{height:105px;resize:vertical}
.element-buttons{display:flex;flex-wrap:wrap;gap:9px}
.element-btn{border:1.5px solid #a697b9;background:white;border-radius:25px;padding:10px 14px;cursor:pointer;color:#382b48}
.dot{display:inline-block;width:12px;height:12px;border-radius:4px;margin-right:7px}
.canvas{position:relative;width:100%;height:370px;border:2px solid #9b8aae;border-radius:20px;overflow:hidden;background-color:white;background-image:linear-gradient(#eee8f2 1px,transparent 1px),linear-gradient(90deg,#eee8f2 1px,transparent 1px);background-size:58px 58px;margin-top:15px;touch-action:pan-y}
.room-item{position:absolute;display:flex;justify-content:center;align-items:center;cursor:grab;user-select:none;touch-action:none;font-weight:bold;color:white}
.room-item.selected{outline:3px solid #f5ae35;outline-offset:4px}
.room-item.stage{width:30%;height:19%;left:35%;top:5%;background:#632d8b;border-radius:8px}
.room-item.table{width:9.5%;aspect-ratio:1;border-radius:50%;background:#f7b936}
.room-item.cake{width:8%;aspect-ratio:1;background:#d92e6c;border-radius:9px}
.room-item.catering{width:23%;height:9%;background:#208e62;border-radius:5px}
.room-item.photo{width:9%;aspect-ratio:1;background:#441c65;border-radius:8px}
.room-item.door{width:10%;height:8%;background:#211632;border-radius:5px}
.room-count{color:#665b75;line-height:1.7;margin:15px 0}
.layout-actions{display:flex;flex-wrap:wrap;gap:10px}
.layout-help{color:#766c81;line-height:1.7;margin:17px 0}
.save{margin-top:12px;padding:17px 30px}
@media(max-width:900px){
.sidebar{width:145px}.main{margin-left:145px}
.topbar{padding:0 15px}.content{padding:20px 15px}
.columns{grid-template-columns:1fr}
}
@media(max-width:600px){
.sidebar{position:relative;width:100%;min-height:auto}
.menu{display:grid;grid-template-columns:1fr 1fr}
.profile{display:none}
.main{margin-left:0}
.topbar{height:65px}
.steps{grid-template-columns:1fr 1fr}
.info-grid{grid-template-columns:1fr 1fr}
.card{padding:17px}
.content{padding:18px 12px}
.amount{font-size:27px}
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
        <a href="#">Profil saya</a>
    </nav>

    <div class="profile">
        <div class="profile-row">
            <div class="avatar">N</div>
            <div><b>Nadia Putri</b><small>nadia@contoh.id</small></div>
        </div>
        <x-logout />
    </div>
</aside>

<main class="main">

<header class="topbar">
    <h2>Aula serbaguna</h2>
    <button class="notification">♧ Notifikasi <span>3</span></button>
</header>

<div class="content">

<a href="{{ route('klien.pesanan-tagihan') }}" class="back">← Kembali ke pesanan</a>

<div class="columns">

<!-- KOLOM KIRI -->
<div>

<section class="card">
    <div class="card-heading">
        <h3>Detail pesanan</h3>
        <span class="status">Diterima, menunggu pembayaran</span>
    </div>

    <div class="steps">
        <div class="step done">
            <strong>Tahap 1 · selesai</strong>
            Menunggu konfirmasi
        </div>
        <div class="step current">
            <strong>Tahap 2 · sekarang</strong>
            Diterima, tagihan terbit
        </div>
        <div class="step">
            <strong>Tahap 3</strong>
            Bukti terkirim
        </div>
        <div class="step">
            <strong>Tahap 4</strong>
            Pembayaran terverifikasi
        </div>
    </div>

    <div class="info-grid">
        <div class="info">
            <small>Acara</small>
            <strong>Ulang Tahun ke-7 Nadia</strong>
        </div>
        <div class="info">
            <small>Tanggal</small>
            <strong>Sabtu, 24 Oktober 2026</strong>
        </div>
        <div class="info">
            <small>Penyedia</small>
            <strong>Aula Melati</strong>
        </div>
        <div class="info">
            <small>Jenis</small>
            <strong>Venue</strong>
        </div>
    </div>

    <p class="note">Catatan Anda: Mohon siapkan 6 meja bundar.</p>

    <button class="btn btn-red" onclick="cancelOrder()">Batalkan pesanan</button>
</section>

<!-- LAYOUT RUANGAN DI BAWAH DETAIL PESANAN -->
<section class="card layout-card">
    <h3>Layout ruangan</h3>

    <div class="field">
        <label>Tipe layout</label>
        <select id="layoutType">
            <option>Meja bundar</option>
            <option>Meja panjang</option>
            <option>Teater</option>
            <option>U-Shape</option>
        </select>
    </div>

    <div class="field">
        <label>Catatan layout</label>
        <textarea> Panggung di sisi utara, meja kue di dekat panggung.</textarea>
    </div>

    <div class="field">
        <label>Denah ruangan</label>
        <div class="element-buttons">
            <button class="element-btn" onclick="addItem('stage')"><span class="dot" style="background:#632d8b"></span>+ Panggung</button>
            <button class="element-btn" onclick="addItem('table')"><span class="dot" style="background:#f7b936"></span>+ Meja bundar</button>
            <button class="element-btn" onclick="addItem('longtable')"><span class="dot" style="background:#efcf86"></span>+ Meja panjang</button>
            <button class="element-btn" onclick="addItem('chair')"><span class="dot" style="background:#9292a7"></span>+ Kursi</button>
            <button class="element-btn" onclick="addItem('cake')"><span class="dot" style="background:#d92e6c"></span>+ Meja kue</button>
            <button class="element-btn" onclick="addItem('catering')"><span class="dot" style="background:#208e62"></span>+ Prasmanan</button>
            <button class="element-btn" onclick="addItem('play')"><span class="dot" style="background:#64dfb5"></span>+ Area bermain</button>
            <button class="element-btn" onclick="addItem('photo')"><span class="dot" style="background:#441c65"></span>+ Photo booth</button>
            <button class="element-btn" onclick="addItem('door')"><span class="dot" style="background:#211632"></span>+ Pintu masuk</button>
        </div>
    </div>

    <div class="canvas" id="roomCanvas">
        <div class="room-item stage" style="left:27%;width:30%;height:19%;top:5%">Panggung</div>

        <div class="room-item table" style="left:16%;top:32%"></div>
        <div class="room-item table" style="left:36%;top:32%"></div>
        <div class="room-item table" style="left:56%;top:32%"></div>
        <div class="room-item table" style="left:16%;top:63%"></div>
        <div class="room-item table" style="left:36%;top:63%"></div>
        <div class="room-item table" style="left:56%;top:63%"></div>

        <div class="room-item cake" style="left:82%;top:26%"></div>
        <div class="room-item catering" style="left:67%;top:50%">Prasmanan</div>
        <div class="room-item photo" style="left:83%;top:76%"></div>
        <div class="room-item door" style="left:46%;top:91%">Pintu</div>
    </div>

    <p class="room-count" id="roomCount">1 panggung, 6 meja bundar, 1 meja kue, 1 prasmanan, 1 photo booth, 1 pintu masuk.</p>

    <div class="layout-actions">
        <button class="btn btn-red" onclick="deleteSelected()">Hapus elemen terpilih</button>
        <button class="btn btn-outline" onclick="clearRoom()">Kosongkan denah</button>
    </div>

    <p class="layout-help">
        Seret elemen untuk memindahkan. Dengan keyboard: pilih elemen dengan Tab, geser dengan tombol panah, hapus dengan Delete.
    </p>

    <button class="btn btn-purple save" onclick="saveLayout()">Simpan layout</button>
</section>

</div>

<!-- KOLOM KANAN -->
<div>

<section class="card">
    <h3>Tagihan</h3>
    <p class="invoice-number">INV-20261015-0007</p>
    <p class="invoice-desc">Aula serbaguna × 1 @ Rp 1.800.000</p>

    <div class="amount">Rp 1.800.000</div>

    <p class="helper">
        Bayar langsung ke penyedia (misalnya transfer bank), lalu unggah buktinya di sini.
    </p>

    <hr class="line">

    <label class="field-label">Bukti pembayaran</label>
    <input type="file" class="file-input" id="paymentFile" accept=".jpg,.jpeg,.png,.pdf">

    <p class="file-help">JPG, PNG, atau PDF, maksimal 4 MB.</p>

    <button class="btn btn-purple full" onclick="uploadPayment()">Unggah bukti bayar</button>
</section>

<section class="card">
    <h3>Riwayat pembayaran</h3>

    <div class="payment-history">
        <span class="rejected">Ditolak</span>
        <a href="#" class="history-link" onclick="alert('Contoh bukti pembayaran sebelumnya')">Lihat bukti</a>

        <p style="color:#756b80;margin-top:8px">14/10/2026 09:12</p>
        <p>Catatan: Nominal belum sesuai tagihan.</p>
    </div>
</section>

</div>
</div>
</div>
</main>

<script>
const canvas = document.getElementById('roomCanvas');
let selectedItem = null;

function selectItem(item) {
    document.querySelectorAll('.room-item').forEach(el => el.classList.remove('selected'));
    selectedItem = item;
    item.classList.add('selected');
}

function enableDrag(item) {
    item.tabIndex = 0;

    item.addEventListener('pointerdown', function(e) {
        selectItem(item);
        item.setPointerCapture(e.pointerId);

        const startX = e.clientX;
        const startY = e.clientY;
        const left = item.offsetLeft;
        const top = item.offsetTop;

        function move(ev) {
            const rect = canvas.getBoundingClientRect();
            const x = left + ev.clientX - startX;
            const y = top + ev.clientY - startY;

            item.style.left = Math.max(0, Math.min(x, rect.width - item.offsetWidth)) + 'px';
            item.style.top = Math.max(0, Math.min(y, rect.height - item.offsetHeight)) + 'px';
        }

        function end() {
            item.removeEventListener('pointermove', move);
            item.removeEventListener('pointerup', end);
            updateCount();
        }

        item.addEventListener('pointermove', move);
        item.addEventListener('pointerup', end);
    });

    item.addEventListener('click', () => selectItem(item));

    item.addEventListener('keydown', function(e) {
        const step = 10;
        if(e.key === 'ArrowLeft') item.style.left = (item.offsetLeft-step)+'px';
        if(e.key === 'ArrowRight') item.style.left = (item.offsetLeft+step)+'px';
        if(e.key === 'ArrowUp') item.style.top = (item.offsetTop-step)+'px';
        if(e.key === 'ArrowDown') item.style.top = (item.offsetTop+step)+'px';
        if(e.key === 'Delete') item.remove();
        updateCount();
    });
}

document.querySelectorAll('.room-item').forEach(enableDrag);

function addItem(type) {
    const item = document.createElement('div');
    item.classList.add('room-item');

    const settings = {
        stage: ['stage','Panggung'],
        table: ['table',''],
        longtable: ['longtable','Meja'],
        chair: ['chair','Kursi'],
        cake: ['cake',''],
        catering: ['catering','Prasmanan'],
        play: ['play','Area bermain'],
        photo: ['photo',''],
        door: ['door','Pintu']
    };

    const [className,label] = settings[type];
    item.classList.add(className);
    item.textContent = label;
    item.style.left = '40%';
    item.style.top = '40%';

    if(type === 'longtable') {
        item.style.cssText += ';width:15%;height:9%;background:#efcf86;border-radius:7px';
    }
    if(type === 'chair') {
        item.style.cssText += ';width:6%;height:8%;background:#9292a7;border-radius:5px';
    }
    if(type === 'play') {
        item.style.cssText += ';width:17%;height:10%;background:#64dfb5;border-radius:7px';
    }

    canvas.appendChild(item);
    enableDrag(item);
    selectItem(item);
    updateCount();
}

function deleteSelected() {
    if(!selectedItem) {
        alert('Pilih elemen yang ingin dihapus.');
        return;
    }
    selectedItem.remove();
    selectedItem = null;
    updateCount();
}

function clearRoom() {
    if(!confirm('Kosongkan seluruh denah ruangan?')) return;
    canvas.innerHTML = '';
    selectedItem = null;
    updateCount();
}

function updateCount() {
    const names = {
        stage:'panggung',
        table:'meja bundar',
        longtable:'meja panjang',
        chair:'kursi',
        cake:'meja kue',
        catering:'prasmanan',
        play:'area bermain',
        photo:'photo booth',
        door:'pintu masuk'
    };

    const counts = {};
    canvas.querySelectorAll('.room-item').forEach(item => {
        const type = Object.keys(names).find(key => item.classList.contains(key));
        if(type) counts[names[type]] = (counts[names[type]] || 0) + 1;
    });

    document.getElementById('roomCount').textContent =
        Object.entries(counts).map(([name,count]) => count+' '+name).join(', ') + '.';
}

function saveLayout() {
    alert('Layout berhasil disiapkan. Penyimpanan permanen memerlukan integrasi database.');
}

function uploadPayment() {
    const file = document.getElementById('paymentFile').files[0];

    if(!file) {
        alert('Silakan pilih bukti pembayaran terlebih dahulu.');
        return;
    }

    if(file.size > 4 * 1024 * 1024) {
        alert('Ukuran file maksimal 4 MB.');
        return;
    }

    alert('File berhasil dipilih. Pengiriman ke server memerlukan integrasi backend.');
}

function cancelOrder() {
    if(confirm('Apakah kamu yakin ingin membatalkan pesanan ini?')) {
        alert('Contoh tampilan pembatalan. Proses sebenarnya memerlukan backend.');
    }
}
</script>

</body>
</html>
