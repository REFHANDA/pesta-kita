
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detail Acara | Pesta Kita</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
    --purple:#5B2887;
    --dark:#211733;
    --yellow:#F8B936;
    --bg:#FAF8FD;
    --border:#E9E1F0;
    --muted:#777087;
}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--dark)}
.layout{display:flex;min-height:100vh}

/* SIDEBAR */
.sidebar{
    width:220px;background:var(--dark);color:white;
    position:fixed;inset:0 auto 0 0;display:flex;
    flex-direction:column;padding:25px 12px 15px;z-index:10
}
.brand{padding:0 15px 25px}
.brand h2{font-size:21px;font-weight:800}
.brand span{font-size:11px;color:#c9bfd3;font-style:italic}
.menu{display:flex;flex-direction:column;gap:5px}
.menu a{
    color:#ddd4e5;text-decoration:none;padding:12px 14px;
    border-radius:10px;font-size:13px;font-style:italic
}
.menu a:hover{background:#49345e}
.menu a.active{background:var(--yellow);color:var(--dark)}
.profile{margin-top:auto;border-top:1px solid #443650;padding:17px 5px 0}
.profile-info{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.avatar{
    width:36px;height:36px;border-radius:50%;background:#493957;
    display:grid;place-items:center;font-weight:700
}
.profile-info strong{font-size:12px}
.profile-info small{display:block;font-size:10px;color:#c9bfd3}
.logout{
    display:block;text-align:center;color:white;border:1px solid #9b8ba9;
    padding:9px;border-radius:25px;font-size:12px;text-decoration:none
}

/* MAIN */
.main{margin-left:220px;width:calc(100% - 220px);min-height:100vh}
.topbar{
    height:75px;background:white;border-bottom:1px solid var(--border);
    display:flex;align-items:center;justify-content:space-between;padding:0 30px
}
.topbar h3{font-size:19px;font-weight:800}
.notification{
    border:1px solid #9278a7;border-radius:30px;padding:9px 17px;
    font-size:12px;font-weight:700;background:white
}
.notification span{
    background:var(--yellow);padding:4px 9px;border-radius:20px;margin-left:8px
}

/* CONTENT */
.content{padding:18px 25px 35px}
.progress{
    display:flex;gap:8px;align-items:center;margin-bottom:17px;flex-wrap:wrap
}
.step{
    padding:6px 12px;border:1px solid var(--border);
    border-radius:20px;background:white;font-size:10px;
    font-weight:700;color:var(--muted)
}
.step.done{background:#D5F6E7;color:#16754D;border-color:#D5F6E7}
.step.current{background:#F0E8F7;color:var(--purple);border-color:#F0E8F7}

/* GRID */
.detail-grid{
    display:grid;
    grid-template-columns:minmax(0,1.55fr) minmax(270px,1fr);
    gap:18px;
    align-items:start
}
.left-column,.right-column{display:flex;flex-direction:column;gap:15px}
.card{
    background:white;border:1px solid var(--border);
    border-radius:17px;padding:19px
}
.card-heading{
    display:flex;justify-content:space-between;
    align-items:flex-start;gap:10px;margin-bottom:8px
}
.card h4{font-size:15px;font-weight:800}
.subheading{font-size:11px;color:var(--muted);margin-top:5px}
.badges{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
.badge{
    display:inline-block;padding:5px 9px;border-radius:20px;
    font-size:9px;font-weight:700
}
.badge.blue{background:#DFF3FF;color:#17536D}
.badge.purple{background:#F0E8F7;color:var(--purple)}
.badge.green{background:#D5F6E7;color:#16754D}
.badge.yellow{background:#FFF0C7;color:#88600B}
.badge.gray{background:#ECEBEF;color:#555}

/* BUTTONS */
.btn{
    display:inline-flex;align-items:center;justify-content:center;
    padding:9px 17px;border-radius:25px;font-family:inherit;
    font-size:11px;font-weight:700;text-decoration:none;cursor:pointer
}
.btn-outline{background:white;border:1px solid #9278a7;color:var(--dark)}
.btn-primary{background:var(--purple);border:1px solid var(--purple);color:white}
.btn-danger{background:white;border:1px solid #E94B4B;color:#D93636;width:100%}
.btn:hover{opacity:.85}

/* SUMMARY */
.summary-date{font-size:11px;color:var(--muted);margin-top:6px}
.summary-info{
    display:grid;grid-template-columns:1fr 1fr;
    gap:10px;margin:15px 0
}
.info-box{background:#F2ECF8;border-radius:13px;padding:12px}
.info-box span{display:block;font-size:10px;color:var(--muted);margin-bottom:5px}
.info-box strong{font-size:12px}
.budget-track{
    width:100%;height:8px;background:#F0E8F7;
    border-radius:20px;overflow:hidden;margin:10px 0 5px
}
.budget-fill{height:100%;background:var(--purple);border-radius:20px}
.budget-caption{font-size:10px;color:var(--muted)}
.description{margin-top:17px}
.description h5{font-size:12px;margin-bottom:7px}
.description p{font-size:11px;line-height:1.7;color:#5F586B}

/* RUNDOWN */
.rundown-list{display:flex;flex-direction:column;gap:8px;margin:15px 0}
.rundown-item{
    background:#F2ECF8;border-radius:9px;padding:9px 11px;
    display:flex;gap:12px;align-items:center;font-size:11px
}
.rundown-item strong{font-size:11px;min-width:32px}
.rundown-item span{color:#5F586B}
.rundown-button{margin-top:3px}

/* EVALUATION */
.evaluation p{font-size:11px;color:var(--muted);line-height:1.6;margin-top:12px}

/* ORDERS */
.orders-heading{
    display:flex;justify-content:space-between;
    align-items:center;gap:10px;flex-wrap:wrap
}
.orders-heading h4{font-size:14px;font-weight:800}
.order-filters{display:flex;gap:7px}
.order-card{
    background:white;border:1px solid var(--border);
    border-radius:16px;padding:15px;margin-top:10px;
    position:relative
}
.order-card.featured{border-left:4px solid var(--yellow)}
.order-top{
    display:flex;justify-content:space-between;
    align-items:center;gap:8px;flex-wrap:wrap
}
.order-top h5{font-size:12px;font-weight:800}
.order-vendor{font-size:10px;color:var(--muted);margin-top:5px}
.order-bottom{
    display:flex;justify-content:space-between;
    align-items:center;gap:8px;margin-top:12px
}
.order-date{font-size:10px;color:var(--muted)}
.order-price{font-size:11px;font-weight:800}
.order-status{font-size:9px;padding:5px 8px;border-radius:20px}
.status-yellow{background:#FFF0C7;color:#88600B}
.status-blue{background:#DFF3FF;color:#17536D}
.status-green{background:#D5F6E7;color:#16754D}

/* RESPONSIVE */
@media(max-width:1000px){
    .detail-grid{grid-template-columns:1fr}
    .right-column{display:grid;grid-template-columns:1fr 1fr}
    .right-column .btn-danger{grid-column:1/-1}
}
@media(max-width:650px){
    .sidebar{width:75px;padding:20px 7px}
    .brand{padding:0 0 25px;text-align:center}
    .brand h2{font-size:11px}
    .brand span,.menu a span,.profile-info div,.logout{display:none}
    .menu a{text-align:center;font-size:0;padding:13px 5px}
    .menu a::first-letter{font-size:17px}
    .profile-info{justify-content:center}
    .main{margin-left:75px;width:calc(100% - 75px)}
    .topbar{height:65px;padding:0 12px}
    .topbar h3{font-size:13px}
    .notification{font-size:10px;padding:7px}
    .content{padding:13px 11px}
    .card{padding:14px}
    .right-column{display:flex}
    .summary-info{gap:7px}
    .card-heading{flex-wrap:wrap}
    .orders-heading{align-items:flex-start}
    .order-top{align-items:flex-start}
}
</style>
</head>

<body>

@php
$user = auth()->user();
$nama = $user->name ?? 'Nama Klien';
$email = $user->email ?? 'Email belum tersedia';
$inisial = strtoupper(substr($nama,0,1));

$acara = $acara ?? [
    'nama' => 'Ulang Tahun ke-7',
    'tanggal' => 'Sabtu, 24 Oktober 2026',
    'jumlah_tamu' => 40,
    'tema' => 'Putri Salju',
    'anggaran' => 5000000,
    'biaya' => 2350000,
    'persentase' => 47,
    'deskripsi' => 'Pesta bertema Putri Salju dengan dekorasi biru-putih, kue bertingkat, dan permainan untuk anak.'
];

$rundown = $rundown ?? [
    ['waktu'=>'15:00','kegiatan'=>'Dekorasi tiba'],
    ['waktu'=>'16:30','kegiatan'=>'Katering dan sound system siap'],
    ['waktu'=>'17:00','kegiatan'=>'Tamu datang'],
    ['waktu'=>'17:30','kegiatan'=>'Tiup lilin']
];

$pesanan = $pesanan ?? [
    [
        'nama'=>'Aula serbaguna',
        'jenis'=>'Venue',
        'vendor'=>'Aula Melati',
        'status'=>'Perlu dibayar',
        'status_class'=>'status-yellow',
        'tambahan'=>'Diterima, menunggu pembayaran',
        'tambahan_class'=>'status-blue',
        'tanggal'=>'Sabtu, 24 Okt 2026',
        'harga'=>1800000
    ],
    [
        'nama'=>'Katering prasmanan',
        'jenis'=>'Vendor',
        'vendor'=>'Dapur Bu Rina',
        'status'=>'Menunggu konfirmasi',
        'status_class'=>'status-yellow',
        'tambahan'=>null,
        'tambahan_class'=>'',
        'tanggal'=>'Sabtu, 24 Okt 2026',
        'harga'=>1800000
    ],
    [
        'nama'=>'Dekorasi balon dan backdrop',
        'jenis'=>'Vendor',
        'vendor'=>'Balon & Co',
        'status'=>'Pembayaran terverifikasi',
        'status_class'=>'status-green',
        'tambahan'=>null,
        'tambahan_class'=>'',
        'tanggal'=>'Sabtu, 24 Okt 2026',
        'harga'=>550000
    ]
];
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
        <a href="{{ route('klien.rancang') }}" class="active">✎ <span>Rancang acara</span></a>
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

<!-- MAIN -->
<main class="main">

<header class="topbar">
    <h3>{{ $acara['nama'] }}</h3>
    <button class="notification">♧ Notifikasi <span>3</span></button>
</header>

<div class="content">

    <!-- PROGRESS -->
    <div class="progress">
        <span class="step done">✓ Konsep</span>
        <span class="step done">✓ Pesan</span>
        <span class="step">3 Bayar</span>
        <span class="step">4 Rundown</span>
        <span class="step">5 Evaluasi</span>
    </div>

    <div class="detail-grid">

        <!-- KOLOM KIRI -->
        <div class="left-column">

            <!-- RINGKASAN KONSEP -->
            <section class="card">

                <div class="card-heading">
                    <div>
                        <div class="badges">
                            <h4>Ringkasan konsep</h4>
                            <span class="badge blue">Dalam pemesanan</span>
                            <span class="badge purple">H-9</span>
                        </div>
                        <p class="summary-date">{{ $acara['tanggal'] }}</p>
                    </div>

                    <a href="{{ route('klien.buat-konsep') }}" class="btn btn-outline">Ubah</a>
                </div>

                <div class="summary-info">
                    <div class="info-box">
                        <span>Jumlah tamu</span>
                        <strong>{{ $acara['jumlah_tamu'] }} orang</strong>
                    </div>

                    <div class="info-box">
                        <span>Tema</span>
                        <strong>{{ $acara['tema'] }}</strong>
                    </div>

                    <div class="info-box">
                        <span>Anggaran</span>
                        <strong>Rp {{ number_format($acara['anggaran'],0,',','.') }}</strong>
                    </div>

                    <div class="info-box">
                        <span>Biaya terkonfirmasi</span>
                        <strong>Rp {{ number_format($acara['biaya'],0,',','.') }}</strong>
                    </div>
                </div>

                <div class="budget-track">
                    <div class="budget-fill" style="width:{{ $acara['persentase'] }}%"></div>
                </div>

                <p class="budget-caption">{{ $acara['persentase'] }}% dari anggaran terpakai.</p>

                <div class="description">
                    <h5>Deskripsi</h5>
                    <p>{{ $acara['deskripsi'] }}</p>
                </div>

            </section>

            <!-- PESANAN -->
            <section>

                <div class="orders-heading">
                    <h4>Pesanan untuk acara ini</h4>

                    <div class="order-filters">
                        <a href="#" class="btn btn-outline">Vendor & venue</a>
                        <a href="#" class="btn btn-outline">Peralatan</a>
                    </div>
                </div>

                @foreach($pesanan as $item)
                <article class="order-card {{ $loop->first ? 'featured' : '' }}">

                    <div class="order-top">
                        <h5>{{ $item['nama'] }} - {{ $item['jenis'] }}</h5>

                        <div class="badges">
                            <span class="order-status {{ $item['status_class'] }}">
                                {{ $item['status'] }}
                            </span>

                            @if($item['tambahan'])
                            <span class="order-status {{ $item['tambahan_class'] }}">
                                {{ $item['tambahan'] }}
                            </span>
                            @endif
                        </div>
                    </div>

                    <p class="order-vendor">{{ $item['vendor'] }}</p>

                    <div class="order-bottom">
                        <span class="order-date">{{ $item['tanggal'] }}</span>
                        <strong class="order-price">
                            Rp {{ number_format($item['harga'],0,',','.') }}
                        </strong>
                    </div>

                </article>
                @endforeach

            </section>

        </div>

        <!-- KOLOM KANAN -->
        <div class="right-column">

            <!-- RUNDOWN -->
            <section class="card">
                <h4>Rundown</h4>
                <p class="subheading">{{ count($rundown) }} segmen tersusun.</p>

                <div class="rundown-list">
                    @foreach($rundown as $item)
                    <div class="rundown-item">
                        <strong>{{ $item['waktu'] }}</strong>
                        <span>{{ $item['kegiatan'] }}</span>
                    </div>
                    @endforeach
                </div>

                <a href="#" class="btn btn-primary rundown-button">
                    Susun jadwal acara
                </a>
            </section>

            <!-- EVALUASI -->
            <section class="card evaluation">
                <h4>Evaluasi</h4>
                <p>Evaluasi bisa diisi mulai hari acara berlangsung.</p>
            </section>

            <!-- HAPUS ACARA -->
            <button type="button" class="btn btn-danger"
                onclick="if(confirm('Apakah Anda yakin ingin menghapus acara ini?')) alert('Fitur hapus perlu dihubungkan ke backend.')">
                Hapus acara
            </button>

        </div>

    </div>
</div>
</main>
</div>

</body>
</html>
