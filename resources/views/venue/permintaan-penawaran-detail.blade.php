@extends('layouts.venue')

@section('title', 'Usulan penawaran venue · Panel Pemilik Venue Pesta Kita')

@section('content')
<main id="konten" class="content detail-page" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/venue') }}">Beranda</a> / <a href="{{ url('/venue/permintaan-penawaran') }}">Permintaan penawaran</a> / PWV-0017</div>
            <h1>Usulan penawaran: Graha Melati Ballroom</h1>
            <p class="meta">Permintaan PWV-0017 · Pesta Kebun Raka · 12 Desember 2026 · masuk 01 November 2026</p>
        </div>
        <a class="btn btn-o" href="{{ url('/venue/permintaan-penawaran') }}">Kembali ke daftar</a>
    </div>

    <div class="alert"><span class="ic i-info" aria-hidden="true"></span><p><b>Fitur ini masih berupa prototipe frontend.</b> Pengajuan dan perubahan penawaran belum tersimpan ke server. Semua nominal dan status di halaman ini adalah contoh statis.</p></div>

    <div class="detail-grid">
        <div class="detail-stack">
            <section class="card detail-card">
                <div class="card-h"><div class="split-head"><div><h2>Permintaan Klien</h2><p class="meta">Kebutuhan acara yang diteruskan Pesta Kita.</p></div><span class="badge b-warn">Perlu diusulkan</span></div></div>
                <div class="card-body">
                    <div class="detail-kv">
                        <div class="kv"><span class="k">Venue</span><span class="v">Graha Melati Ballroom</span></div>
                        <div class="kv"><span class="k">Acara</span><span class="v">Pesta Kebun Raka</span></div>
                        <div class="kv"><span class="k">Tanggal acara</span><span class="v">12 Desember 2026</span></div>
                        <div class="kv"><span class="k">Perkiraan tamu</span><span class="v">60 orang</span></div>
                        <div class="kv"><span class="k">Anggaran acuan Klien</span><span class="v">Rp 8.000.000</span></div>
                        <div class="kv"><span class="k">Harga katalog saat ini</span><span class="v">Rp 8.600.000</span></div>
                    </div>
                    <p class="field-label">Kebutuhan khusus dari Klien</p>
                    <div class="quote-panel">Penambahan jam penggunaan satu jam, area bermain anak, dan penataan meja panjang.</div>
                </div>
            </section>

            <section class="card detail-card">
                <div class="card-h"><h2>Harga katalog venue (referensi)</h2><p class="meta">Harga ini tampil ke Klien di katalog dan hanya menjadi acuan, bukan harga final.</p></div>
                <div class="card-body">
                    <div class="detail-list">
                        <div class="dl-row"><span>Harga sewa venue (katalog)</span><strong>Rp 7.500.000</strong></div>
                        <div class="dl-row"><span>Biaya kebersihan</span><strong>Rp 350.000</strong></div>
                        <div class="dl-row"><span>Biaya layanan Pesta Kita (10%)</span><strong>Rp 750.000</strong></div>
                        <div class="dl-row"><span>Estimasi total tampil ke Klien</span><strong>Rp 8.600.000</strong></div>
                    </div>
                    <div class="alert mt-16"><span class="ic i-info" aria-hidden="true"></span><p>Biaya kebersihan dan biaya lain ditampilkan terpisah agar tidak mengejutkan Klien. Komisi 10% ditambahkan ke tagihan Klien, bukan dipotong dari harga sewa.</p></div>
                </div>
            </section>

            <section class="card detail-card">
                <div class="card-h"><h2>Usulkan harga</h2><p class="meta">Usulan ini akan ditinjau Admin sebelum berlaku bagi Klien.</p></div>
                <div class="card-body">
                    <label class="field-label" for="harga-sewa">Harga sewa usulan (harga venue)</label>
                    <input class="field-control" id="harga-sewa" inputmode="numeric" placeholder="cth. Rp 7.800.000">
                    <label class="field-label" for="biaya-tambahan">Biaya tambahan yang diusulkan</label>
                    <textarea class="field-control" id="biaya-tambahan" rows="3" placeholder="cth. jam tambahan, fasilitas tambahan, kebersihan"></textarea>
                    <label class="field-label" for="catatan-usulan">Catatan untuk Admin</label>
                    <textarea class="field-control" id="catatan-usulan" rows="3" placeholder="Alasan perubahan atau informasi tambahan"></textarea>
                    <div class="detail-actions">
                        <button class="btn" type="button" data-confirm="Kirim usulan harga ke Admin?" data-text="Usulan ditinjau Admin sebelum berlaku. Pada prototipe ini usulan belum tersimpan ke server." data-label="Kirim usulan" data-need="main #harga-sewa" data-need-msg="Harga sewa usulan wajib diisi.">Kirim usulan</button>
                        <button class="btn btn-o" type="button" data-confirm="Simpan sebagai draf?" data-text="Draf usulan belum dikirim ke Admin. Pada prototipe ini draf belum tersimpan ke server." data-label="Simpan draf">Simpan draf</button>
                    </div>
                    <div class="alert mt-16"><span class="ic i-info" aria-hidden="true"></span><p>Harga yang Anda usulkan belum menjadi harga final. Harga final ditetapkan setelah Admin menyetujui usulan ini.</p></div>
                </div>
            </section>

            <section class="card detail-card">
                <div class="card-h"><h2>Riwayat permintaan</h2></div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item"><b>Permintaan Klien masuk</b><small>01 Nov 2026 · 10.12 WIB</small></div>
                        <div class="timeline-item"><b>Diteruskan ke pemilik venue</b><small>01 Nov 2026 · 10.13 WIB</small></div>
                        <div class="timeline-item"><b>Menunggu usulan harga Anda</b><small>Status saat ini</small></div>
                    </div>
                </div>
            </section>
        </div>

        <aside class="detail-side">
            <section class="card detail-card">
                <div class="card-h"><h2>Ketentuan penawaran</h2></div>
                <div class="card-body">
                    <div class="detail-list">
                        <div class="dl-row"><span>Peninjau keputusan</span><strong>Admin Pesta Kita</strong></div>
                        <div class="dl-row"><span>Harga berlaku</span><strong>Setelah disetujui Admin</strong></div>
                        <div class="dl-row"><span>Komisi layanan</span><strong>10% ditambahkan ke Klien</strong></div>
                    </div>
                    <div class="alert mt-16"><span class="ic i-info" aria-hidden="true"></span><p>Identitas dan kontak Klien tidak ditampilkan. Seluruh komunikasi dan pembayaran dilakukan melalui Pesta Kita.</p></div>
                </div>
            </section>

            <section class="card detail-card">
                <div class="card-h"><h2>Butuh bantuan?</h2></div>
                <div class="card-body">
                    <p class="m-0">Jika permintaan tidak sesuai ketersediaan venue, sampaikan alasan ke Admin melalui catatan usulan.</p>
                    <p class="mt-8 fw-7">0341-555-0456</p>
                </div>
            </section>
        </aside>
    </div>
</main>
@endsection
