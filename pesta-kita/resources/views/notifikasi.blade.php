
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifikasi | Pesta Kita</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;background:#fbf9fd;color:#241a36;font-size:14px}
.sidebar{position:fixed;inset:0 auto 0 0;width:206px;background:#201632;color:white;display:flex;flex-direction:column}
.brand{padding:25px 17px 15px}
.brand h2{font-size:21px;margin-bottom:5px}
.brand small,.profile small{font-style:italic;color:#d4cadd;font-size:11px}
.menu{padding:12px 10px}
.menu a{display:block;padding:12px 13px;margin-bottom:4px;text-decoration:none;color:#eee8f3;border-radius:10px;font-style:italic}
.menu a.active{background:#f8b936;color:#241a32;font-weight:bold}
.profile{margin-top:auto;padding:15px;border-top:1px solid #40354f}
.profile-info{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.avatar{width:33px;height:33px;border-radius:50%;background:#493e59;display:grid;place-items:center}
.profile small{display:block;margin-top:3px}
.logout{display:block;padding:9px;text-align:center;border:1px solid #a99bb9;border-radius:25px;color:white;text-decoration:none;font-style:italic}
.main{margin-left:206px;min-height:100vh}
.topbar{height:69px;background:white;border-bottom:1px solid #e5ddec;display:flex;align-items:center;justify-content:space-between;padding:0 25px}
.topbar h2{font-size:19px}
.notification{background:white;border:1px solid #a99ab9;border-radius:25px;padding:9px 16px;font-weight:bold}
.notification span{background:#f7b936;padding:4px 9px;border-radius:20px;margin-left:7px}
.content{padding:25px}
.notification-area{max-width:650px}
.toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;gap:10px}
.unread-count{color:#756b80;font-size:13px}
.read-all{border:1px solid #a797ba;background:white;border-radius:25px;padding:9px 17px;cursor:pointer;font-weight:bold;color:#30243e}
.read-all:hover{background:#f3edf8}
.notification-list{display:flex;flex-direction:column;gap:12px}
.notice{position:relative;background:white;border:1px solid #e7dfee;border-radius:17px;padding:19px 17px;overflow:hidden}
.notice.unread{background:#f4eef9}
.notice.unread:before{content:"";position:absolute;left:0;top:0;bottom:0;width:4px;background:#f8b936}
.notice-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:8px}
.notice-head strong{font-size:14px}
.new-label{background:#f8b936;color:#30243e;padding:5px 11px;border-radius:20px;font-size:11px;font-style:italic;font-weight:bold}
.notice p{font-size:13px;line-height:1.5}
.notice time{display:block;color:#756b80;font-size:11px;margin-top:6px}
.empty{background:white;border:1px dashed #c9bdd4;border-radius:15px;text-align:center;padding:35px;color:#756b80}
@media(max-width:700px){
.sidebar{width:145px}.main{margin-left:145px}
.menu{padding:8px 6px}.menu a{padding:10px 7px;font-size:11px}
.content{padding:15px}.topbar{padding:0 12px}
.topbar h2{font-size:15px}.toolbar{align-items:flex-start;flex-direction:column}
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
        <a href="#">Profil saya</a>
    </nav>
    <div class="profile">
        <div class="profile-info">
            <div class="avatar">N</div>
            <div><b>Nadia Putri</b><small>nadia@contoh.id</small></div>
        </div>
        <a href="{{ route('login') }}" class="logout">Keluar</a>
    </div>
</aside>

<main class="main">
    <header class="topbar">
        <h2>Notifikasi</h2>
        <button class="notification" type="button">
            ♧ Notifikasi <span id="topCount">3</span>
        </button>
    </header>

    <div class="content">
        <section class="notification-area">
            <div class="toolbar">
                <span class="unread-count" id="unreadCount">3 belum dibaca</span>
                <button class="read-all" onclick="markAllRead()">Tandai semua sudah dibaca</button>
            </div>

            <div class="notification-list" id="notificationList">
                <article class="notice unread">
                    <div class="notice-head">
                        <strong>Pesanan Anda diterima</strong>
                        <span class="new-label">Baru</span>
                    </div>
                    <p>Dapur Bu Rina menerima pesanan katering Anda. Tagihan sudah terbit.</p>
                    <time>2 jam yang lalu</time>
                </article>

                <article class="notice unread">
                    <div class="notice-head">
                        <strong>Pembayaran terverifikasi</strong>
                        <span class="new-label">Baru</span>
                    </div>
                    <p>Pembayaran untuk Sound system 1000 watt sudah dikonfirmasi penyedia.</p>
                    <time>Kemarin, 16:20</time>
                </article>

                <article class="notice unread">
                    <div class="notice-head">
                        <strong>Pesanan ditolak</strong>
                        <span class="new-label">Baru</span>
                    </div>
                    <p>Ceria Entertainment menolak pesanan Badut dan MC anak. Silakan pilih layanan lain.</p>
                    <time>2 hari yang lalu</time>
                </article>

                <article class="notice">
                    <div class="notice-head">
                        <strong>Akun Anda aktif</strong>
                    </div>
                    <p>Selamat datang di Pesta Kita. Mulai dengan menyusun konsep acara.</p>
                    <time>1 minggu yang lalu</time>
                </article>
            </div>
        </section>
    </div>
</main>

<script>
function updateCount(){
    const count=document.querySelectorAll('.notice.unread').length;
    document.getElementById('unreadCount').textContent=count+' belum dibaca';
    document.getElementById('topCount').textContent=count;
}

function markAllRead(){
    document.querySelectorAll('.notice.unread').forEach(item=>{
        item.classList.remove('unread');
        const label=item.querySelector('.new-label');
        if(label)label.remove();
    });
    updateCount();
}

document.querySelectorAll('.notice').forEach(item=>{
    item.addEventListener('click',()=>{
        item.classList.remove('unread');
        const label=item.querySelector('.new-label');
        if(label)label.remove();
        updateCount();
    });
});

updateCount();
</script>
</body>
</html>
