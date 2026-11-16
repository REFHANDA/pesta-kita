@extends('layouts.klien')

@section('title', 'Status penawaran · Pesta Kita')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/katalog.css') }}">
@endpush

@section('content')
<main id="konten" class="content detail-page" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / <a href="{{ url('/klien/permintaan-penawaran') }}">Permintaan penawaran</a> / PW-0031</div>
            <h1>Penawaran: Graha Melati Ballroom</h1>
            <p class="meta">Permintaan PW-0031 · Pesta Kebun Raka · 12 Desember 2026 · diajukan 01 November 2026</p>
        </div>
        <a class="btn btn-o" href="{{ url('/klien/permintaan-penawaran') }}">Kembali ke daftar</a>
    </div>

    <div class="data-note" role="note">
        <span class="ic i-info" aria-hidden="true"></span>
        <p><b>Fitur ini masih berupa prototipe frontend.</b> Pengajuan dan perubahan penawaran belum tersimpan ke server. Semua nominal dan status di halaman ini adalah contoh statis.</p>
    </div>

    <div class="detail-grid">
        <div class="detail-stack">
            <section class="card detail-card">
                <div class="card-h"><div class="split-head"><div><h2>Ringkasan permintaan</h2><p class="meta">Yang Anda ajukan ke penyedia melalui Pesta Kita.</p></div><span class="badge b-ok">Penawaran disetujui</span></div></div>
                <div class="card-body">
                    <div class="detail-kv">
                        <div class="kv"><span class="k">Layanan / venue</span><span class="v">Graha Melati Ballroom</span></div>
                        <div class="kv"><span class="k">Penyedia</span><span class="v">Graha Melati</span></div>
                        <div class="kv"><span class="k">Acara</span><span class="v">Pesta Kebun Raka</span></div>
                        <div class="kv"><span class="k">Tanggal acara</span><span class="v">12 Desember 2026</span></div>
                        <div class="kv"><span class="k">Perkiraan tamu</span><span class="v">60 orang</span></div>
                        <div class="kv"><span class="k">Anggaran acuan</span><span class="v">Rp 8.000.000</span></div>
                    </div>
                    <p class="field-label">Kebutuhan khusus</p>
                    <p class="body-copy">Area bermain anak, penambahan jam penggunaan satu jam, dan penataan meja panjang.</p>
                </div>
            </section>

            <section class="card detail-card">
                <div class="card-h"><h2>Perbandingan harga</h2><p class="meta">Harga katalog, harga usulan penyedia, dan harga yang disetujui Admin dibedakan secara terpisah.</p></div>
                <div class="card-body">
                    <div class="detail-list">
                        <div class="dl-row"><span>Harga katalog (referensi)</span><strong>Rp 8.600.000 <span class="badge b-mute">Referensi</span></strong></div>
                        <div class="dl-row"><span>Harga diajukan penyedia</span><strong>Rp 8.250.000 <span class="badge b-warn">Diajukan</span></strong></div>
                        <div class="dl-row"><span>Harga disetujui Admin</span><strong>Rp 8.250.000 <span class="badge b-ok">Disetujui</span></strong></div>
                    </div>
                    <table class="break mt-16">
                        <tr><td>Harga layanan (disetujui Admin)</td><td>Rp 8.250.000</td></tr>
                        <tr><td>Biaya layanan Pesta Kita (10%)</td><td>Rp 825.000</td></tr>
                        <tr class="t"><td>Total tagihan Klien (contoh)</td><td>Rp 9.075.000</td></tr>
                    </table>
                    <div class="warn-box mt-12">Angka di atas contoh statis, bukan tagihan final. Komisi layanan 10% ditambahkan ke tagihan Klien dan tidak dipotong dari harga penyedia. Total akhir ditetapkan sistem setelah pesanan disahkan Admin.</div>
                </div>
            </section>

            <section class="card detail-card">
                <div class="card-h"><h2>Arti status harga</h2><p class="meta">Agar tidak tertukar antara katalog, usulan, dan harga final.</p></div>
                <div class="card-body">
                    <ul class="policy">
                        <li><span class="ic i-tag" aria-hidden="true"></span><span><span class="badge b-mute">Katalog</span> Harga yang tampil di katalog. Hanya referensi awal, belum termasuk penawaran penyedia.</span></li>
                        <li><span class="ic i-tag" aria-hidden="true"></span><span><span class="badge b-warn">Usulan</span> Harga yang diajukan penyedia. Masih menunggu peninjauan Admin dan belum berlaku.</span></li>
                        <li><span class="ic i-tag" aria-hidden="true"></span><span><span class="badge b-info">Menunggu</span> Penawaran sudah masuk tetapi keputusan Admin belum ditetapkan.</span></li>
                        <li><span class="ic i-tag" aria-hidden="true"></span><span><span class="badge b-ok">Disetujui</span> Harga final dari Admin. Menjadi dasar tagihan Klien beserta komisi 10%.</span></li>
                        <li><span class="ic i-tag" aria-hidden="true"></span><span><span class="badge b-mute">Belum tersedia</span> Penyedia belum mengirim penawaran untuk permintaan ini.</span></li>
                    </ul>
                </div>
            </section>

            <section class="card detail-card">
                <div class="card-h"><h2>Riwayat penawaran</h2></div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item"><b>Permintaan diajukan</b><small>01 Nov 2026 · 10.12 WIB</small></div>
                        <div class="timeline-item"><b>Diteruskan ke penyedia</b><small>01 Nov 2026 · 10.13 WIB</small></div>
                        <div class="timeline-item"><b>Penyedia mengusulkan harga Rp 8.250.000</b><small>02 Nov 2026 · 08.40 WIB</small></div>
                        <div class="timeline-item"><b>Disetujui Admin</b><small>02 Nov 2026 · 15.20 WIB · status saat ini</small></div>
                    </div>
                </div>
            </section>
        </div>

        <aside class="detail-side">
            <section class="card detail-card">
                <div class="card-h"><h2>Tindakan Anda</h2><p class="meta">Lanjutkan sesuai alur pemesanan Pesta Kita.</p></div>
                <div class="card-body">
                    <p class="body-copy">Penawaran ini sudah disetujui Admin. Anda dapat melanjutkan ke pemesanan; tagihan gabungan akan mengikuti harga yang disetujui.</p>
                    <div class="detail-actions">
                        <button class="btn" type="button" data-confirm="Lanjutkan ke pemesanan?" data-text="Pada prototipe ini pemesanan belum tersimpan ke server. Total tagihan final ditetapkan sistem saat backend tersedia." data-label="Lanjutkan pemesanan">Lanjutkan pemesanan</button>
                        <button class="btn btn-o" type="button" data-confirm="Tarik permintaan penawaran?" data-text="Permintaan yang ditarik tidak dapat dipulihkan. Pada prototipe ini tindakan belum tersimpan ke server." data-label="Tarik permintaan" data-tone="bad">Tarik permintaan</button>
                    </div>
                    <div class="alert mt-16"><span class="ic i-info" aria-hidden="true"></span><p>Kontak pribadi penyedia tidak ditampilkan. Seluruh komunikasi dan pembayaran dilakukan melalui Pesta Kita.</p></div>
                </div>
            </section>

            <section class="card detail-card">
                <div class="card-h"><h2>Informasi penyedia</h2></div>
                <div class="card-body">
                    <div class="detail-list">
                        <div class="dl-row"><span>Nama penyedia</span><strong>Graha Melati</strong></div>
                        <div class="dl-row"><span>Jenis</span><strong>Pemilik venue</strong></div>
                        <div class="dl-row"><span>Status verifikasi</span><strong class="ok-t">Terverifikasi (contoh)</strong></div>
                        <div class="dl-row"><span>Lokasi</span><strong>Kediri</strong></div>
                    </div>
                    <a class="btn btn-o btn-block" href="{{ url('/klien/penyedia-venue') }}">Lihat profil penyedia</a>
                </div>
            </section>
        </aside>
    </div>
</main>
@endsection
