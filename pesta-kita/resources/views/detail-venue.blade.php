
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Aula Melati | Pesta Kita</title>

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

.content{padding:17px 25px 35px}

.back-link{
    display:inline-block;
    font-size:11px;
    font-weight:700;
    color:var(--purple);
    margin-bottom:17px;
    text-decoration:none;
}

/* DETAIL GRID */
.detail-grid{
    display:grid;
    grid-template-columns:minmax(0,1.5fr) minmax(300px,1fr);
    gap:18px;
    align-items:start;
}

.card{
    background:white;
    border:1px solid var(--border);
    border-radius:16px;
    padding:19px;
}

/* VENUE */
.venue-card{min-height:170px}

.venue-heading{
    display:flex;
    align-items:center;
    gap:8px;
    margin-bottom:8px;
}

.badge{
    display:inline-block;
    padding:5px 10px;
    border-radius:20px;
    font-size:9px;
    font-weight:700;
}

.badge.blue{
    background:#DFF3FF;
    color:#17536D;
}

.venue-name{
    font-size:12px;
    color:var(--muted);
}

.price{
    font-size:23px;
    color:var(--purple);
    font-weight:800;
    margin-bottom:15px;
}

.price small{
    font-size:10px;
    font-weight:400;
    color:var(--muted);
}

.venue-info{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
    margin-bottom:17px;
}

.info-box{
    background:#F2ECF8;
    padding:12px;
    border-radius:11px;
}

.info-box span{
    display:block;
    font-size:10px;
    color:var(--muted);
    margin-bottom:5px;
}

.info-box strong{font-size:11px}

.description h4{
    font-size:12px;
    margin-bottom:7px;
}

.description p{
    font-size:11px;
    color:#5F586B;
    line-height:1.7;
}

/* BOOKING FORM */
.booking-card h4{
    font-size:14px;
    font-weight:800;
    margin-bottom:14px;
}

.step-title{
    font-size:11px;
    font-weight:800;
    margin:14px 0 10px;
    color:var(--purple);
}

.form-group{margin-bottom:10px}

.form-group label{
    display:block;
    font-size:10px;
    font-weight:600;
    margin-bottom:6px;
}

.form-group input,
.form-group select,
.form-group textarea{
    width:100%;
    padding:10px;
    border:1px solid #A99AB9;
    border-radius:9px;
    background:white;
    font-family:inherit;
    font-size:10px;
    color:var(--dark);
    outline:none;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus{
    border-color:var(--purple);
}

.form-group textarea{
    min-height:75px;
    resize:vertical;
    line-height:1.6;
}

.check-button{
    width:100%;
    padding:10px;
    border:1px solid #A99AB9;
    border-radius:25px;
    background:white;
    font-family:inherit;
    font-size:10px;
    font-weight:700;
    cursor:pointer;
}

.check-button:hover{background:#F2ECF8}

.availability{
    display:none;
    padding:10px;
    border-radius:9px;
    font-size:10px;
    margin-top:9px;
    line-height:1.5;
}

.availability.success{
    display:block;
    background:#E5FAF0;
    border:1px solid #A7EBCB;
    color:#16815A;
}

.availability.error{
    display:block;
    background:#FFF0F0;
    border:1px solid #F3B8B8;
    color:#B52E2E;
}

.divider{
    border:0;
    border-top:1px solid #F0ECF3;
    margin:16px 0;
}

/* DENAH LEGEND */
.layout-options{
    display:flex;
    flex-wrap:wrap;
    gap:7px;
    margin:9px 0 11px;
}

.layout-chip{
    display:inline-flex;
    align-items:center;
    gap:6px;
    border:1px solid #A99AB9;
    border-radius:22px;
    background:white;
    padding:8px 11px;
    font-family:inherit;
    font-size:9px;
    cursor:pointer;
    color:var(--dark);
    transition:.2s;
}

.layout-chip:hover{
    background:#F2ECF8;
    border-color:var(--purple);
}

.layout-chip.active{
    background:#F2ECF8;
    border-color:var(--purple);
    color:var(--purple);
    font-weight:800;
}

.dot{
    width:9px;
    height:9px;
    border-radius:3px;
    flex-shrink:0;
}

/* DENAH */
.floor-plan{
    position:relative;
    width:100%;
    height:190px;
    border:1.5px solid #A99AB9;
    border-radius:11px;
    overflow:hidden;
    background-color:#fff;
    background-image:
        linear-gradient(#EEEAF3 1px,transparent 1px),
        linear-gradient(90deg,#EEEAF3 1px,transparent 1px);
    background-size:20px 20px;
    margin-top:10px;
    touch-action:none;
}

.stage-label{
    position:absolute;
    top:4%;
    left:34%;
    width:32%;
    height:17%;
    background:var(--purple);
    color:white;
    border-radius:5px;
    display:grid;
    place-items:center;
    font-size:9px;
    font-weight:700;
    pointer-events:none;
    z-index:1;
}

/* DENAH ELEMENTS */
.floor-element{
    position:absolute;
    transform:translate(-50%,-50%);
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:grab;
    user-select:none;
    touch-action:none;
    z-index:2;
}

.floor-element:active{cursor:grabbing}

.floor-element.selected{
    outline:2px dashed var(--purple);
    outline-offset:4px;
}

.floor-element.table{
    width:27px;
    height:27px;
    border-radius:50%;
    background:#F8B936;
}

.floor-element.long-table{
    width:38px;
    height:19px;
    border-radius:5px;
    background:#F0CF88;
}

.floor-element.chair{
    width:19px;
    height:19px;
    border-radius:5px;
    background:#9295A9;
}

.floor-element.cake{
    width:24px;
    height:24px;
    border-radius:6px;
    background:#D82F70;
}

.floor-element.buffet{
    width:65px;
    height:14px;
    border-radius:4px;
    background:#29966A;
    color:white;
    font-size:8px;
    font-weight:700;
}

.floor-element.play{
    width:25px;
    height:25px;
    border-radius:50%;
    background:#68DDB1;
}

.floor-element.photo{
    width:27px;
    height:27px;
    border-radius:5px;
    background:#5B2887;
}

.floor-element.entrance{
    width:30px;
    height:14px;
    border-radius:3px;
    background:#211733;
}

/* PLAN CAPTION */
.plan-caption{
    font-size:10px;
    color:var(--muted);
    line-height:1.6;
    margin-top:8px;
}

/* PLAN BUTTONS */
.plan-actions{
    display:flex;
    flex-wrap:wrap;
    gap:7px;
    margin-top:10px;
}

.small-btn{
    padding:8px 13px;
    border:1px solid #A99AB9;
    background:white;
    border-radius:22px;
    color:var(--dark);
    font-family:inherit;
    font-size:10px;
    font-weight:700;
    cursor:pointer;
}

.small-btn.danger{
    border-color:#E65A5A;
    color:#D93636;
}

.small-btn:hover{background:#F4EFF8}

.helper{
    font-size:9px;
    color:var(--muted);
    line-height:1.7;
    margin-top:9px;
}

/* FINAL FORM */
.send-btn{
    width:100%;
    border:0;
    border-radius:25px;
    padding:13px;
    background:var(--purple);
    color:white;
    font-family:inherit;
    font-size:11px;
    font-weight:700;
    cursor:pointer;
    margin-top:10px;
}

.send-btn:hover{background:#472068}

/* RESPONSIVE */
@media(max-width:1000px){
    .detail-grid{grid-template-columns:1fr}
}

@media(max-width:650px){
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
    .content{padding:14px 11px}
    .card{padding:14px}
    .venue-info{gap:7px}
    .floor-plan{height:190px}
    .layout-chip{padding:8px 9px}
}
</style>
</head>

<body>

@php
$user = auth()->user();
$nama = $user->name ?? 'Nama Klien';
$email = $user->email ?? 'Email belum tersedia';
$inisial = strtoupper(substr($nama,0,1));
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
        <a href="{{ route('klien.sewa-peralatan') }}">▣ <span>Sewa peralatan</span></a>
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
    <h3>Aula Melati</h3>
    <button class="notification">♧ Notifikasi <span>3</span></button>
</header>

<div class="content">

    <a href="{{ route('klien.vendor-venue') }}" class="back-link">
        ← Kembali ke katalog
    </a>

    <div class="detail-grid">

        <!-- DETAIL VENUE -->
        <section class="card venue-card">

            <div class="venue-heading">
                <span class="badge blue">Venue</span>
                <span class="venue-name">Aula Melati</span>
            </div>

            <div class="price">
                Rp 1.800.000 <small>per hari</small>
            </div>

            <div class="venue-info">
                <div class="info-box">
                    <span>Kapasitas</span>
                    <strong>120 orang</strong>
                </div>

                <div class="info-box">
                    <span>Alamat</span>
                    <strong>Jl. Dhono No. 12, Kediri</strong>
                </div>
            </div>

            <div class="description">
                <h4>Deskripsi</h4>
                <p>
                    Aula serbaguna dengan AC, panggung kecil,
                    area parkir luas, dan dapur bersih.
                    Layout ruangan dapat diatur sesuai jumlah tamu.
                </p>
            </div>

        </section>

        <!-- BOOKING -->
        <section class="card booking-card">

            <h4>Tambahkan ke acara</h4>

            <form id="bookingForm">

                <!-- STEP 1 -->
                <p class="step-title">Langkah 1 - Cek ketersediaan</p>

                <div class="form-group">
                    <label for="acara">Pilih acara</label>

                    <select id="acara" name="acara" required>
                        <option value="Ulang Tahun ke-7 Nadia">
                            Ulang Tahun ke-7 Nadia · 24 Okt 2026
                        </option>
                        <option value="Ulang Tahun Kakek 70 Tahun">
                            Ulang Tahun Kakek 70 Tahun · 14 Nov 2026
                        </option>
                        <option value="Pesta Sweet Seventeen Sinta">
                            Pesta Sweet Seventeen Sinta · 6 Des 2026
                        </option>
                    </select>
                </div>

                <button type="button" class="check-button" id="checkAvailability">
                    Cek stok & tanggal
                </button>

                <div id="availability" class="availability"></div>

                <hr class="divider">

                <!-- STEP 2 -->
                <p class="step-title">Langkah 2 - Detail pesanan</p>

                <div class="form-group">
                    <label for="layout">Layout ruangan</label>

                    <select id="layout" name="layout">
                        <option value="Meja bundar">Meja bundar</option>
                        <option value="Meja panjang">Meja panjang</option>
                        <option value="Teater">Teater</option>
                        <option value="U-shape">U-shape</option>
                        <option value="Kombinasi">Kombinasi</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="catatan-layout">Catatan layout</label>

                    <textarea id="catatan-layout"
                        placeholder="Panggung di sisi utara, meja kue di dekat panggung."></textarea>

                    <p class="helper">
                        Posisi panggung, meja kue, area bermain, dll.
                    </p>
                </div>

                <!-- DENAH -->
                <div class="form-group">

                    <label>Denah ruangan (opsional)</label>

                    <div class="layout-options">

                        <button type="button" class="layout-chip" data-type="stage">
                            <span class="dot" style="background:#5B2887"></span>
                            + Panggung
                        </button>

                        <button type="button" class="layout-chip" data-type="table">
                            <span class="dot" style="background:#F8B936"></span>
                            + Meja bundar
                        </button>

                        <button type="button" class="layout-chip" data-type="long-table">
                            <span class="dot" style="background:#F0CF88"></span>
                            + Meja panjang
                        </button>

                        <button type="button" class="layout-chip" data-type="chair">
                            <span class="dot" style="background:#9295A9"></span>
                            + Kursi
                        </button>

                        <button type="button" class="layout-chip" data-type="cake">
                            <span class="dot" style="background:#D82F70"></span>
                            + Meja kue
                        </button>

                        <button type="button" class="layout-chip" data-type="buffet">
                            <span class="dot" style="background:#29966A"></span>
                            + Prasmanan
                        </button>

                        <button type="button" class="layout-chip" data-type="play">
                            <span class="dot" style="background:#68DDB1"></span>
                            + Area bermain
                        </button>

                        <button type="button" class="layout-chip" data-type="photo">
                            <span class="dot" style="background:#5B2887"></span>
                            + Photo booth
                        </button>

                        <button type="button" class="layout-chip" data-type="entrance">
                            <span class="dot" style="background:#211733"></span>
                            + Pintu masuk
                        </button>

                    </div>

                    <!-- FLOOR PLAN -->
                    <div class="floor-plan" id="floorPlan">

                        <div class="stage-label">Panggung</div>

                        <!-- 6 MEJA BUNDAR -->
                        <div class="floor-element table"
                             style="left:20%;top:39%" data-name="Meja bundar"></div>

                        <div class="floor-element table"
                             style="left:40%;top:39%" data-name="Meja bundar"></div>

                        <div class="floor-element table"
                             style="left:60%;top:39%" data-name="Meja bundar"></div>

                        <div class="floor-element table"
                             style="left:20%;top:65%" data-name="Meja bundar"></div>

                        <div class="floor-element table"
                             style="left:40%;top:65%" data-name="Meja bundar"></div>

                        <div class="floor-element table"
                             style="left:60%;top:65%" data-name="Meja bundar"></div>

                        <!-- MEJA KUE -->
                        <div class="floor-element cake"
                             style="left:87%;top:28%" data-name="Meja kue"></div>

                        <!-- PRASMANAN -->
                        <div class="floor-element buffet"
                             style="left:78%;top:55%" data-name="Prasmanan">
                            Prasmanan
                        </div>

                        <!-- PHOTO BOOTH -->
                        <div class="floor-element photo"
                             style="left:89%;top:82%" data-name="Photo booth"></div>

                        <!-- PINTU MASUK -->
                        <div class="floor-element entrance"
                             style="left:50%;top:96%" data-name="Pintu masuk"></div>

                    </div>

                    <p class="plan-caption" id="planCaption">
                        1 panggung, 6 meja bundar, 1 meja kue,
                        1 prasmanan, 1 photo booth, 1 pintu masuk.
                    </p>

                    <div class="plan-actions">

                        <button type="button" class="small-btn danger" id="deleteElement">
                            Hapus elemen terpilih
                        </button>

                        <button type="button" class="small-btn" id="clearPlan">
                            Kosongkan denah
                        </button>

                    </div>

                    <p class="helper">
                        Seret elemen untuk memindahkan. Dengan keyboard:
                        pilih elemen dengan Tab, geser dengan tombol panah,
                        hapus dengan Delete.
                    </p>

                </div>

                <!-- CATATAN -->
                <div class="form-group">
                    <label for="catatan-penyedia">
                        Catatan untuk penyedia (opsional)
                    </label>

                    <textarea id="catatan-penyedia"
                        placeholder="Tuliskan permintaan tambahan untuk penyedia..."></textarea>
                </div>

                <button type="submit" class="send-btn">
                    Kirim pesanan
                </button>

            </form>

        </section>

    </div>

</div>
</main>
</div>

<script>
const floorPlan = document.getElementById('floorPlan');
const chips = document.querySelectorAll('.layout-chip');
const caption = document.getElementById('planCaption');

let selectedElement = null;
let dragElement = null;

const elementNames = {
    stage:'Panggung',
    table:'Meja bundar',
    'long-table':'Meja panjang',
    chair:'Kursi',
    cake:'Meja kue',
    buffet:'Prasmanan',
    play:'Area bermain',
    photo:'Photo booth',
    entrance:'Pintu masuk'
};

function selectElement(element){
    document.querySelectorAll('.floor-element').forEach(item=>{
        item.classList.remove('selected');
    });

    selectedElement = element;

    if(element){
        element.classList.add('selected');
    }
}

function updateCaption(){
    const elements = [...floorPlan.querySelectorAll('.floor-element')];

    const counts = {};

    elements.forEach(item=>{
        const name = item.dataset.name;
        counts[name] = (counts[name] || 0) + 1;
    });

    const order = [
        'Panggung',
        'Meja bundar',
        'Meja panjang',
        'Kursi',
        'Meja kue',
        'Prasmanan',
        'Area bermain',
        'Photo booth',
        'Pintu masuk'
    ];

    const result = order
        .filter(name=>counts[name])
        .map(name=>counts[name]+' '+name.toLowerCase())
        .join(', ');

    caption.textContent = result
        ? result + '.'
        : 'Belum ada elemen pada denah ruangan.';
}

/* ADD ELEMENT */
chips.forEach(chip=>{
    chip.addEventListener('click',()=>{
        const type = chip.dataset.type;

        const element = document.createElement('div');
        element.className = 'floor-element';

        if(type==='stage'){
            element.classList.add('stage-element');
            element.style.background='#5B2887';
            element.style.width='45px';
            element.style.height='20px';
            element.style.borderRadius='5px';
            element.style.color='white';
            element.textContent='Panggung';
        }else{
            element.classList.add(type);
            if(type==='buffet'){
                element.textContent='Prasmanan';
            }
        }

        element.dataset.name = elementNames[type];
        element.style.left='50%';
        element.style.top='70%';

        floorPlan.appendChild(element);

        selectElement(element);
        updateCaption();
    });
});

/* SELECT ELEMENT */
floorPlan.addEventListener('pointerdown',e=>{
    const element = e.target.closest('.floor-element');

    if(!element) return;

    selectElement(element);
    dragElement = element;
    element.setPointerCapture(e.pointerId);
});

/* DRAG ELEMENT */
floorPlan.addEventListener('pointermove',e=>{
    if(!dragElement) return;

    const rect = floorPlan.getBoundingClientRect();

    const left = Math.max(3,Math.min(97,
        ((e.clientX-rect.left)/rect.width)*100
    ));

    const top = Math.max(14,Math.min(96,
        ((e.clientY-rect.top)/rect.height)*100
    ));

    dragElement.style.left=left+'%';
    dragElement.style.top=top+'%';
});

floorPlan.addEventListener('pointerup',()=>{
    dragElement=null;
});

floorPlan.addEventListener('pointercancel',()=>{
    dragElement=null;
});

/* DELETE SELECTED */
document.getElementById('deleteElement').addEventListener('click',()=>{
    if(!selectedElement){
        alert('Pilih elemen denah yang ingin dihapus.');
        return;
    }

    selectedElement.remove();
    selectedElement=null;
    updateCaption();
});

/* CLEAR PLAN */
document.getElementById('clearPlan').addEventListener('click',()=>{
    if(!confirm('Kosongkan seluruh elemen denah?')) return;

    floorPlan.querySelectorAll('.floor-element').forEach(item=>{
        item.remove();
    });

    selectedElement=null;
    updateCaption();
});

/* KEYBOARD MOVEMENT */
document.addEventListener('keydown',e=>{
    if(!selectedElement) return;

    if(['INPUT','TEXTAREA','SELECT'].includes(
        document.activeElement.tagName
    )) return;

    if(e.key==='Delete' || e.key==='Backspace'){
        selectedElement.remove();
        selectedElement=null;
        updateCaption();
    }

    const left=parseFloat(selectedElement.style.left)||50;
    const top=parseFloat(selectedElement.style.top)||50;
    const step=2;

    if(e.key==='ArrowLeft'){
        e.preventDefault();
        selectedElement.style.left=Math.max(3,left-step)+'%';
    }

    if(e.key==='ArrowRight'){
        e.preventDefault();
        selectedElement.style.left=Math.min(97,left+step)+'%';
    }

    if(e.key==='ArrowUp'){
        e.preventDefault();
        selectedElement.style.top=Math.max(14,top-step)+'%';
    }

    if(e.key==='ArrowDown'){
        e.preventDefault();
        selectedElement.style.top=Math.min(96,top+step)+'%';
    }
});

/* CHECK AVAILABILITY */
document.getElementById('checkAvailability').addEventListener('click',()=>{
    const result=document.getElementById('availability');
    const acara=document.getElementById('acara').value;

    result.className='availability success';
    result.textContent='✓ Aula Melati tersedia pada tanggal acara: '+acara+'.';
});

/* SUBMIT */
document.getElementById('bookingForm').addEventListener('submit',e=>{
    e.preventDefault();

    const result=document.getElementById('availability');

    if(!result.classList.contains('success')){
        result.className='availability error';
        result.textContent='Silakan cek stok dan tanggal terlebih dahulu.';
        return;
    }

    alert('Tampilan pesanan sudah siap. Penyimpanan pesanan memerlukan integrasi backend.');
});

updateCaption();
</script>

</body>
</html>
