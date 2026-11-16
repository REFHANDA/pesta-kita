@extends('layouts.admin')

@section('title', 'Peninjauan penawaran · Admin Pesta Kita')

@section('content')
<main class="content page-refined" id="konten" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / Permintaan &amp; penawaran</div>
            <h1>Peninjauan penawaran</h1>
            <p class="meta">Bandingkan harga katalog dan harga yang diajukan penyedia sebelum menetapkan harga final untuk Klien.</p>
        </div>
    </div>

    <div class="alert"><span class="ic i-info" aria-hidden="true"></span><p><b>Fitur ini masih berupa prototipe frontend.</b> Pengajuan dan perubahan penawaran belum tersimpan ke server. Semua nominal, nama, dan status di halaman ini adalah contoh statis.</p></div>

    <section class="card workflow-card">
        <div aria-label="Navigasi status" class="tabs" role="navigation">
            <a href="{{ url('/admin/permintaan-penawaran') }}" aria-current="page">Menunggu keputusan <span class="n">2</span></a>
            <a href="{{ url('/admin/permintaan-penawaran') }}?status=disetujui">Disetujui</a>
            <a href="{{ url('/admin/permintaan-penawaran') }}?status=ditolak">Ditolak</a>
            <a href="{{ url('/admin/permintaan-penawaran') }}?status=semua">Semua</a>
        </div>

        <div class="item workflow-item">
            <div class="item-h">
                <div><h2>Graha Melati Ballroom</h2><p class="meta">Graha Melati · Venue · permintaan PWV-0017 · diusulkan 02 Nov 2026</p></div>
                <span class="badge b-warn">Menunggu keputusan</span>
            </div>
            <div class="price-compare">
                <div><small>Harga katalog (referensi)</small><b>Rp 8.600.000</b></div>
                <span>→</span>
                <div><small>Harga diajukan penyedia</small><b>Rp 8.250.000</b></div>
            </div>
            <p>Alasan: penyesuaian jam tambahan dan fasilitas untuk 60 tamu.</p>
            <div class="toolbar">
                <button class="btn" type="button" data-confirm="Setujui penawaran ini?" data-text="Harga final ditetapkan dari harga yang disetujui, lalu komisi 10% ditambahkan ke tagihan Klien. Pada prototipe ini keputusan belum tersimpan ke server." data-label="Setujui penawaran">Setujui penawaran</button>
                <button class="btn btn-danger" type="button" data-confirm="Tolak penawaran ini?" data-text="Penyedia dan Klien diberi tahu, lalu penawaran perlu diusulkan ulang. Pada prototipe ini keputusan belum tersimpan ke server." data-label="Tolak penawaran" data-tone="bad" data-ask="Alasan penolakan">Tolak penawaran</button>
                <a class="btn btn-o" href="{{ url('/admin/permintaan-penawaran-detail') }}">Lihat detail</a>
            </div>
        </div>

        <div class="item workflow-item">
            <div class="item-h">
                <div><h2>Katering prasmanan</h2><p class="meta">Dapur Nusantara · Vendor · permintaan PW-0044 · diusulkan 02 Nov 2026</p></div>
                <span class="badge b-warn">Menunggu keputusan</span>
            </div>
            <div class="price-compare">
                <div><small>Harga katalog (referensi)</small><b>Rp 2.992.000</b></div>
                <span>→</span>
                <div><small>Harga diajukan penyedia</small><b>Rp 3.100.000</b></div>
            </div>
            <p>Alasan: penyesuaian menu bebas kacang dan tambahan dua menu sayur.</p>
            <div class="toolbar">
                <button class="btn" type="button" data-confirm="Setujui penawaran ini?" data-text="Harga final ditetapkan dari harga yang disetujui, lalu komisi 10% ditambahkan ke tagihan Klien. Pada prototipe ini keputusan belum tersimpan ke server." data-label="Setujui penawaran">Setujui penawaran</button>
                <button class="btn btn-danger" type="button" data-confirm="Tolak penawaran ini?" data-text="Penyedia dan Klien diberi tahu, lalu penawaran perlu diusulkan ulang. Pada prototipe ini keputusan belum tersimpan ke server." data-label="Tolak penawaran" data-tone="bad" data-ask="Alasan penolakan">Tolak penawaran</button>
                <a class="btn btn-o" href="{{ url('/admin/permintaan-penawaran-detail') }}">Lihat detail</a>
            </div>
        </div>

        <div class="alert"><span class="ic i-info" aria-hidden="true"></span><p>Harga katalog, harga usulan penyedia, dan harga final adalah tiga hal berbeda. Harga final hanya ditetapkan setelah Admin menyetujui penawaran.</p></div>
    </section>
</main>
@endsection
