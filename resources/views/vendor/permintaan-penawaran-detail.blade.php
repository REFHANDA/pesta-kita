@extends('layouts.vendor')

@section('title', 'Usulan penawaran · Panel Vendor Pesta Kita')

@section('content')
<main id="konten" class="content detail-page" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/vendor') }}">Beranda</a> / <a href="{{ url('/vendor/permintaan-penawaran') }}">Permintaan penawaran</a> / PW-0044</div>
            <h1>Usulan penawaran: Katering prasmanan</h1>
            <p class="meta">Permintaan PW-0044 · Pesta Kebun Raka · 12 Desember 2026 · masuk 01 November 2026</p>
        </div>
        <a class="btn btn-o" href="{{ url('/vendor/permintaan-penawaran') }}">Kembali ke daftar</a>
    </div>

    <div class="alert"><span class="ic i-info" aria-hidden="true"></span><p><b>Fitur ini masih berupa prototipe frontend.</b> Pengajuan dan perubahan penawaran belum tersimpan ke server. Semua nominal dan status di halaman ini adalah contoh statis.</p></div>

    <div class="detail-grid">
        <div class="detail-stack">
            <section class="card detail-card">
                <div class="card-h"><div class="split-head"><div><h2>Permintaan Klien</h2><p class="meta">Kebutuhan acara yang diteruskan Pesta Kita.</p></div><span class="badge b-warn">Perlu diusulkan</span></div></div>
                <div class="card-body">
                    <div class="detail-kv">
                        <div class="kv"><span class="k">Layanan</span><span class="v">Katering prasmanan</span></div>
                        <div class="kv"><span class="k">Acara</span><span class="v">Pesta Kebun Raka</span></div>
                        <div class="kv"><span class="k">Tanggal acara</span><span class="v">12 Desember 2026</span></div>
                        <div class="kv"><span class="k">Perkiraan tamu</span><span class="v">60 orang</span></div>
                        <div class="kv"><span class="k">Anggaran acuan Klien</span><span class="v">Rp 3.000.000</span></div>
                        <div class="kv"><span class="k">Harga katalog saat ini</span><span class="v">Rp 2.992.000</span></div>
                    </div>
                    <p class="field-label">Kebutuhan khusus dari Klien</p>
                    <div class="quote-panel">Menu bebas kacang untuk dua anak, penambahan dua menu sayur, dan penataan meja prasmanan di tepi taman.</div>
                </div>
            </section>

            <section class="card detail-card">
                <div class="card-h"><h2>Harga katalog penyedia (referensi)</h2><p class="meta">Harga ini tampil ke Klien di katalog dan hanya menjadi acuan, bukan harga final.</p></div>
                <div class="card-body">
                    <div class="detail-list">
                        <div class="dl-row"><span>Harga layanan (katalog)</span><strong>Rp 2.720.000</strong></div>
                        <div class="dl-row"><span>Biaya layanan Pesta Kita (10%)</span><strong>Rp 272.000</strong></div>
                        <div class="dl-row"><span>Harga tampil ke Klien</span><strong>Rp 2.992.000</strong></div>
                    </div>
                    <div class="alert mt-16"><span class="ic i-info" aria-hidden="true"></span><p>Komisi 10% ditambahkan ke tagihan Klien oleh sistem; Anda menerima harga penyedia yang disetujui, bukan harga setelah komisi.</p></div>
                </div>
            </section>

            <section class="card detail-card">
                <div class="card-h"><h2>Usulkan harga</h2><p class="meta">Usulan ini akan ditinjau Admin sebelum berlaku bagi Klien.</p></div>
                <div class="card-body">
                    <label class="field-label" for="harga-usulan">Harga usulan Anda (harga penyedia)</label>
                    <input class="field-control" id="harga-usulan" inputmode="numeric" placeholder="cth. Rp 2.900.000">
                    <label class="field-label" for="rincian-usulan">Rincian atau penyesuaian</label>
                    <textarea class="field-control" id="rincian-usulan" rows="3" placeholder="cth. penyesuaian jumlah tamu dan menu tambahan"></textarea>
                    <label class="field-label" for="catatan-usulan">Catatan untuk Admin</label>
                    <textarea class="field-control" id="catatan-usulan" rows="3" placeholder="Alasan perubahan atau informasi tambahan"></textarea>
                    <div class="detail-actions">
                        <button class="btn" type="button" data-confirm="Kirim usulan harga ke Admin?" data-text="Usulan ditinjau Admin sebelum berlaku. Pada prototipe ini usulan belum tersimpan ke server." data-label="Kirim usulan" data-need="main #harga-usulan" data-need-msg="Harga usulan wajib diisi.">Kirim usulan</button>
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
                        <div class="timeline-item"><b>Diteruskan ke penyedia</b><small>01 Nov 2026 · 10.13 WIB</small></div>
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
                    <p class="m-0">Jika permintaan tidak sesuai layanan Anda, sampaikan alasan ke Admin melalui catatan usulan.</p>
                    <p class="mt-8 fw-7">0341-555-0123</p>
                </div>
            </section>
        </aside>
    </div>
</main>
@endsection
