
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sewa Peralatan | Pesta Kita</title>

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

body{
    font-family:'Plus Jakarta Sans',sans-serif;
    background:var(--bg);
    color:var(--dark);
}

.layout{display:flex;min-height:100vh}

/* SIDEBAR */
.sidebar{
    width:220px;
    background:var(--dark);
    color:white;
    position:fixed;
    inset:0 auto 0 0;
    display:flex;
    flex-direction:column;
    padding:25px 12px 15px;
    z-index:10;
}

.brand{padding:0 15px 25px}
.brand h2{font-size:21px;font-weight:800}
.brand span{font-size:11px;color:#c9bfd3;font-style:italic}

.menu{display:flex;flex-direction:column;gap:5px}

.menu a{
    color:#ddd4e5;
    text-decoration:none;
    padding:12px 14px;
    border-radius:10px;
    font-size:13px;
    font-style:italic;
    transition:.2s;
}

.menu a:hover{background:#49345e}
.menu a.active{background:var(--yellow);color:var(--dark)}

.profile{
    margin-top:auto;
    border-top:1px solid #443650;
    padding:17px 5px 0;
}

.profile-info{
    display:flex;
    align-items:center;
    gap:10px;
    margin-bottom:12px;
}

.avatar{
    width:36px;
    height:36px;
    border-radius:50%;
    background:#493957;
    display:grid;
    place-items:center;
    font-weight:700;
}

.profile-info strong{font-size:12px}
.profile-info small{
    display:block;
    font-size:10px;
    color:#c9bfd3;
}

.logout{
    display:block;
    text-align:center;
    color:white;
    border:1px solid #9b8ba9;
    padding:9px;
    border-radius:25px;
    font-size:12px;
    text-decoration:none;
}

/* MAIN */
.main{
    margin-left:220px;
    width:calc(100% - 220px);
    min-height:100vh;
}

.topbar{
    height:75px;
    background:white;
    border-bottom:1px solid var(--border);
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 30px;
}

.topbar h3{font-size:19px;font-weight:800}

.notification{
    border:1px solid #9278a7;
    border-radius:30px;
    padding:9px 17px;
    font-size:12px;
    font-weight:700;
    background:white;
}

.notification span{
    background:var(--yellow);
    padding:4px 9px;
    border-radius:20px;
    margin-left:8px;
}

/* CONTENT */
.content{padding:18px 25px 35px}

/* TABS */
.tabs{
    display:inline-flex;
    align-items:center;
    padding:3px;
    border:1px solid var(--border);
    border-radius:30px;
    background:white;
    margin-bottom:13px;
}

.tab{
    text-decoration:none;
    color:var(--dark);
    padding:9px 19px;
    border-radius:25px;
    font-size:11px;
    font-weight:600;
}

.tab.active{
    background:var(--purple);
    color:white;
}

/* INTRO */
.intro{
    font-size:12px;
    color:var(--muted);
    margin-bottom:15px;
}

/* SEARCH */
.search-form{
    display:grid;
    grid-template-columns:minmax(0,1fr) auto;
    gap:10px;
    align-items:end;
    margin-bottom:13px;
}

.field label{
    display:block;
    font-size:11px;
    font-weight:600;
    margin-bottom:7px;
}

.field input{
    width:100%;
    height:42px;
    padding:0 12px;
    border:1px solid #A99AB9;
    border-radius:9px;
    background:white;
    font-family:inherit;
    font-size:11px;
    color:var(--dark);
    outline:none;
}

.field input:focus{border-color:var(--purple)}

.search-btn{
    height:42px;
    padding:0 20px;
    border:0;
    border-radius:25px;
    background:var(--purple);
    color:white;
    font-family:inherit;
    font-size:11px;
    font-weight:700;
    cursor:pointer;
}

.search-btn:hover{background:#472068}

.result-count{
    font-size:11px;
    color:var(--muted);
    margin:9px 0 17px;
}

/* EQUIPMENT GRID */
.equipment-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:13px;
}

.equipment-card{
    background:white;
    border:1px solid var(--border);
    border-radius:17px;
    padding:16px;
    min-height:155px;
    display:flex;
    flex-direction:column;
    transition:.2s;
}

.equipment-card:hover{
    border-color:#BBA6D0;
    transform:translateY(-2px);
}

/* STOCK */
.stock{
    display:inline-block;
    width:fit-content;
    background:#FFF0C7;
    color:#88600B;
    padding:5px 9px;
    border-radius:20px;
    font-size:9px;
    font-weight:700;
    margin-bottom:10px;
}

.equipment-card h4{
    font-size:13px;
    font-weight:800;
    margin-bottom:5px;
    line-height:1.5;
}

.vendor-name{
    font-size:10px;
    color:var(--muted);
    margin-bottom:8px;
}

.description{
    font-size:10px;
    line-height:1.6;
    color:#5F586B;
    margin-bottom:10px;
}

.card-footer{
    border-top:1px solid #F0ECF3;
    padding-top:9px;
    margin-top:auto;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:8px;
}

.price{
    font-size:11px;
    font-weight:800;
    color:var(--purple);
}

.price small{
    font-size:9px;
    color:var(--muted);
    font-weight:400;
}

.detail-link{
    text-decoration:none;
    color:var(--purple);
    font-size:10px;
    font-weight:700;
    white-space:nowrap;
}

.detail-link:hover{text-decoration:underline}

.empty{
    grid-column:1/-1;
    background:white;
    border:1px solid var(--border);
    border-radius:15px;
    padding:35px;
    text-align:center;
    font-size:12px;
    color:var(--muted);
}

/* RESPONSIVE */
@media(max-width:1050px){
    .equipment-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
}

@media(max-width:700px){
    .sidebar{
        width:75px;
        padding:20px 7px;
    }

    .brand{
        padding:0 0 25px;
        text-align:center;
    }

    .brand h2{font-size:11px}

    .brand span,
    .menu a span,
    .profile-info div,
    .logout{display:none}

    .menu a{
        text-align:center;
        font-size:0;
        padding:13px 5px;
    }

    .menu a::first-letter{font-size:17px}

    .profile-info{justify-content:center}

    .main{
        margin-left:75px;
        width:calc(100% - 75px);
    }

    .topbar{
        height:65px;
        padding:0 12px;
    }

    .topbar h3{font-size:13px}
    .notification{font-size:10px;padding:7px}

    .content{padding:14px 12px}

    .tabs{width:100%;justify-content:space-between}
    .tab{text-align:center;flex:1;padding:9px 7px}

    .equipment-grid{grid-template-columns:1fr}

    .equipment-card{min-height:145px}
}
</style>
</head>

<body>

@php
$user = auth()->user();
$nama = $user->name ?? 'Nama Klien';
$email = $user->email ?? 'Email belum tersedia';
$inisial = strtoupper(substr($nama,0,1));

$keyword = request('q','');

$peralatan = [
    [
        'nama'=>'Sound system 1000 watt',
        'stok'=>4,
        'vendor'=>'Sound & MC Ceria',
        'deskripsi'=>'Sound system lengkap dengan 2 mikrofon nirkabel dan mixer.',
        'harga'=>350000,
        'satuan'=>'per unit'
    ],
    [
        'nama'=>'Proyektor dan layar',
        'stok'=>2,
        'vendor'=>'Multimedia Kediri',
        'deskripsi'=>'Proyektor 4000 lumen dengan layar tripod 2 meter.',
        'harga'=>250000,
        'satuan'=>'per unit'
    ],
    [
        'nama'=>'Kursi tiffany',
        'stok'=>150,
        'vendor'=>'Rental Pesta Jaya',
        'deskripsi'=>'Kursi tiffany putih, termasuk pengantaran dan pengambilan.',
        'harga'=>8000,
        'satuan'=>'per unit'
    ],
    [
        'nama'=>'Meja bundar 10 orang',
        'stok'=>20,
        'vendor'=>'Rental Pesta Jaya',
        'deskripsi'=>'Meja bundar diameter 150 cm dengan taplak putih.',
        'harga'=>45000,
        'satuan'=>'per unit'
    ],
    [
        'nama'=>'Mesin popcorn',
        'stok'=>3,
        'vendor'=>'Snack Corner',
        'deskripsi'=>'Mesin popcorn dengan bahan untuk 100 porsi.',
        'harga'=>120000,
        'satuan'=>'per unit'
    ],
    [
        'nama'=>'Photo booth set',
        'stok'=>2,
        'vendor'=>'Lensa Ceria',
        'deskripsi'=>'Photo booth dengan properti dan cetak instan.',
        'harga'=>400000,
        'satuan'=>'per unit'
    ],
];

$hasil = array_values(array_filter($peralatan,function($item) use ($keyword){
    return $keyword === '' ||
        stripos($item['nama'],$keyword)!==false ||
        stripos($item['vendor'],$keyword)!==false;
}));
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

        <a href="{{ route('klien.rancang') }}">✎ <span>Rancang acara</span></a>

        <a href="{{ route('klien.vendor-venue') }}">♙ <span>Vendor & venue</span></a>

        <a href="{{ route('klien.sewa-peralatan') }}" class="active">
            ▣ <span>Sewa peralatan</span>
        </a>

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
        <h3>Sewa peralatan</h3>

        <button class="notification">
            ♧ Notifikasi <span>3</span>
        </button>
    </header>

    <div class="content">

        <!-- TABS -->
        <div class="tabs">

            <a href="{{ route('klien.vendor-venue',['jenis'=>'vendor']) }}"
               class="tab">
                Vendor
            </a>

            <a href="{{ route('klien.vendor-venue',['jenis'=>'venue']) }}"
               class="tab">
                Venue
            </a>

            <a href="{{ route('klien.sewa-peralatan') }}"
               class="tab active">
                Peralatan
            </a>

        </div>

        <p class="intro">
            Sewa peralatan per unit. Stok dicek otomatis untuk tanggal acara.
        </p>

        <!-- SEARCH -->
        <form class="search-form" method="GET"
              action="{{ route('klien.sewa-peralatan') }}">

            <div class="field">
                <label for="q">Cari nama</label>

                <input
                    type="text"
                    id="q"
                    name="q"
                    value="{{ $keyword }}"
                    placeholder="Ketik nama..."
                >
            </div>

            <button type="submit" class="search-btn">
                Cari
            </button>

        </form>

        <p class="result-count">{{ count($hasil) }} hasil</p>

        <!-- EQUIPMENT CARDS -->
        <div class="equipment-grid">

            @forelse($hasil as $item)

                <article class="equipment-card">

                    <span class="stock">
                        Stok {{ $item['stok'] }} unit
                    </span>

                    <h4>{{ $item['nama'] }}</h4>

                    <p class="vendor-name">{{ $item['vendor'] }}</p>

                    <p class="description">
                        {{ $item['deskripsi'] }}
                    </p>

                    <div class="card-footer">

                        <div class="price">
                            Rp {{ number_format($item['harga'],0,',','.') }}
                            <small>{{ $item['satuan'] }}</small>
                        </div>

                        <a href="#" class="detail-link">
                            Lihat →
                        </a>

                    </div>

                </article>

            @empty

                <div class="empty">
                    Peralatan tidak ditemukan.
                    Silakan coba kata kunci lain.
                </div>

            @endforelse

        </div>

    </div>

</main>
</div>

</body>
</html>
