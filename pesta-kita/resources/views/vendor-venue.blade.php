
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vendor & Venue | Pesta Kita</title>

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

/* CATEGORY TABS */
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

/* DESCRIPTION */
.intro{
    font-size:12px;
    color:var(--muted);
    margin-bottom:15px;
}

/* SEARCH */
.search-form{
    display:grid;
    grid-template-columns:minmax(0,1fr) 190px auto;
    gap:9px;
    align-items:end;
    margin-bottom:13px;
}

.field label{
    display:block;
    font-size:11px;
    font-weight:600;
    margin-bottom:7px;
}

.field input,
.field select{
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

.field input:focus,
.field select:focus{
    border-color:var(--purple);
}

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

/* VENDOR GRID */
.vendor-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:13px;
}

.vendor-card{
    background:white;
    border:1px solid var(--border);
    border-radius:17px;
    padding:16px;
    min-height:155px;
    display:flex;
    flex-direction:column;
    transition:.2s;
}

.vendor-card:hover{
    border-color:#BBA6D0;
    transform:translateY(-2px);
}

.category{
    display:inline-block;
    width:fit-content;
    background:#F1EAF8;
    color:var(--purple);
    padding:5px 9px;
    border-radius:20px;
    font-size:9px;
    font-weight:700;
    margin-bottom:10px;
}

.vendor-card h4{
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

.vendor-description{
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
    .vendor-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
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

    .search-form{
        grid-template-columns:1fr;
    }

    .search-btn{width:100%}

    .vendor-grid{grid-template-columns:1fr}

    .vendor-card{min-height:145px}
}
</style>
</head>

<body>

@php
$user = auth()->user();
$nama = $user->name ?? 'Nama Klien';
$email = $user->email ?? 'Email belum tersedia';
$inisial = strtoupper(substr($nama,0,1));

$jenis = request('jenis','vendor');
$keyword = request('q','');
$kategori = request('kategori','');

$semuaVendor = [
    [
        'jenis'=>'vendor',
        'kategori'=>'Katering',
        'nama'=>'Katering prasmanan',
        'vendor'=>'Dapur Bu Rina',
        'deskripsi'=>'Paket menu sesuai jumlah tamu, termasuk nasi, lauk, dan buah.',
        'harga'=>45000,
        'satuan'=>'per porsi'
    ],
    [
        'jenis'=>'vendor',
        'kategori'=>'Dekorasi',
        'nama'=>'Dekorasi balon dan backdrop',
        'vendor'=>'Balon & Co',
        'deskripsi'=>'Tema mengikuti konsep acara, lengkap dengan balon arch dan backdrop foto.',
        'harga'=>550000,
        'satuan'=>'per acara'
    ],
    [
        'jenis'=>'vendor',
        'kategori'=>'Fotografer & Videografer',
        'nama'=>'Dokumentasi foto 4 jam',
        'vendor'=>'Lensa Ceria',
        'deskripsi'=>'Fotografer profesional, 200 foto edit, cetak 10 foto ukuran 8R.',
        'harga'=>900000,
        'satuan'=>'per acara'
    ],
    [
        'jenis'=>'vendor',
        'kategori'=>'Hiburan (MC, Badut, Musik)',
        'nama'=>'Badut dan MC anak',
        'vendor'=>'Ceria Entertainment',
        'deskripsi'=>'Pembawa acara dan badut dengan permainan interaktif selama 2 jam.',
        'harga'=>750000,
        'satuan'=>'per acara'
    ],
    [
        'jenis'=>'vendor',
        'kategori'=>'Kue & Dessert',
        'nama'=>'Kue tart custom 2 tingkat',
        'vendor'=>'Manis Manja Bakery',
        'deskripsi'=>'Kue tart dengan desain tema pesta, rasa dapat dipilih.',
        'harga'=>480000,
        'satuan'=>'per kue'
    ],
    [
        'jenis'=>'vendor',
        'kategori'=>'Katering',
        'nama'=>'Snack box ulang tahun',
        'vendor'=>'Dapur Bu Rina',
        'deskripsi'=>'Isi 4 jenis kudapan dan minuman, cocok untuk tamu anak.',
        'harga'=>22000,
        'satuan'=>'per boks'
    ],
    [
        'jenis'=>'venue',
        'kategori'=>'Aula',
        'nama'=>'Aula serbaguna',
        'vendor'=>'Aula Melati',
        'deskripsi'=>'Ruangan luas untuk acara keluarga dan pesta ulang tahun.',
        'harga'=>1800000,
        'satuan'=>'per acara'
    ],
    [
        'jenis'=>'venue',
        'kategori'=>'Gedung',
        'nama'=>'Gedung pesta keluarga',
        'vendor'=>'Gedung Bahagia',
        'deskripsi'=>'Gedung nyaman dengan kapasitas tamu yang fleksibel.',
        'harga'=>2500000,
        'satuan'=>'per acara'
    ],
    [
        'jenis'=>'peralatan',
        'kategori'=>'Sound System',
        'nama'=>'Paket sound system',
        'vendor'=>'Audio Ceria',
        'deskripsi'=>'Peralatan audio lengkap untuk mendukung jalannya acara.',
        'harga'=>450000,
        'satuan'=>'per acara'
    ],
    [
        'jenis'=>'peralatan',
        'kategori'=>'Meja & Kursi',
        'nama'=>'Paket meja dan kursi',
        'vendor'=>'Sewa Peralatan Kita',
        'deskripsi'=>'Paket meja dan kursi untuk kebutuhan tamu undangan.',
        'harga'=>350000,
        'satuan'=>'per paket'
    ],
];

$hasil = array_values(array_filter($semuaVendor,function($item) use ($jenis,$keyword,$kategori){
    $cocokJenis = $item['jenis'] === $jenis;
    $cocokKeyword = $keyword === '' ||
        stripos($item['nama'],$keyword)!==false ||
        stripos($item['vendor'],$keyword)!==false ||
        stripos($item['kategori'],$keyword)!==false;
    $cocokKategori = $kategori === '' || $item['kategori'] === $kategori;

    return $cocokJenis && $cocokKeyword && $cocokKategori;
}));

$daftarKategori = array_unique(array_column(
    array_filter($semuaVendor,fn($item)=>$item['jenis']===$jenis),
    'kategori'
));
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
        <a href="{{ route('klien.vendor-venue') }}" class="active">♙ <span>Vendor & venue</span></a>
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

        <x-logout />
    </div>

</aside>

<!-- MAIN -->
<main class="main">

    <header class="topbar">
        <h3>Vendor & venue</h3>

        <button class="notification">
            ♧ Notifikasi <span>3</span>
        </button>
    </header>

    <div class="content">

        <!-- TABS -->
        <div class="tabs">
            <a href="{{ route('klien.vendor-venue',['jenis'=>'vendor']) }}"
               class="tab {{ $jenis==='vendor'?'active':'' }}">
                Vendor
            </a>

            <a href="{{ route('klien.vendor-venue',['jenis'=>'venue']) }}"
               class="tab {{ $jenis==='venue'?'active':'' }}">
                Venue
            </a>

            <a href="{{ route('klien.vendor-venue',['jenis'=>'peralatan']) }}"
               class="tab {{ $jenis==='peralatan'?'active':'' }}">
                Peralatan
            </a>
        </div>

        <p class="intro">
            @if($jenis==='vendor')
                Katering, dekorasi, dan jasa pendukung acara.
            @elseif($jenis==='venue')
                Temukan tempat yang sesuai untuk acara Anda.
            @else
                Temukan peralatan yang dibutuhkan untuk acara Anda.
            @endif
        </p>

        <!-- SEARCH -->
        <form class="search-form" method="GET"
              action="{{ route('klien.vendor-venue') }}">

            <input type="hidden" name="jenis" value="{{ $jenis }}">

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

            <div class="field">
                <label for="kategori">Kategori</label>
                <select id="kategori" name="kategori">
                    <option value="">Semua kategori</option>
                    @foreach($daftarKategori as $item)
                        <option value="{{ $item }}"
                            {{ $kategori===$item?'selected':'' }}>
                            {{ $item }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="search-btn">Cari</button>

        </form>

        <p class="result-count">{{ count($hasil) }} hasil</p>

        <!-- VENDOR CARDS -->
        <div class="vendor-grid">

            @forelse($hasil as $item)

                <article class="vendor-card">

                    <span class="category">{{ $item['kategori'] }}</span>

                    <h4>{{ $item['nama'] }}</h4>

                    <p class="vendor-name">{{ $item['vendor'] }}</p>

                    <p class="vendor-description">
                        {{ $item['deskripsi'] }}
                    </p>

                    <div class="card-footer">

                        <div class="price">
                            Rp {{ number_format($item['harga'],0,',','.') }}
                            <small>{{ $item['satuan'] }}</small>
                        </div>

                        <a href="#" class="detail-link">Lihat →</a>

                    </div>

                </article>

            @empty

                <div class="empty">
                    Tidak ada vendor atau layanan yang ditemukan.
                    Coba gunakan kata kunci lain.
                </div>

            @endforelse

        </div>

    </div>

</main>
</div>

</body>
</html>
