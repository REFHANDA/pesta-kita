@extends('layouts.admin')

@section('title', 'Detail penawaran · Admin Pesta Kita')

@section('content')
<main class="content page-refined detail-page" id="konten" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / <a href="{{ url('/admin/permintaan-penawaran') }}">Peninjauan penawaran</a> / PWV-0017</div>
            <h1>Detail penawaran: Graha Melati Ballroom</h1>
            <p class="meta">Permintaan PWV-0017 · Pesta Kebun Raka · 12 Desember 2026 · diusulkan 02 November 2026</p>
        </div>
        <a class="btn btn-o" href="{{ url('/admin/permintaan-penawaran') }}">Kembali ke daftar</a>
    </div>

    <div class="alert"><span class="ic i-info" aria-hidden="true"></span><p><b>Fitur ini masih berupa prototipe frontend.</b> Pengajuan dan perubahan penawaran belum tersimpan ke server. Semua nominal dan status di halaman ini adalah contoh statis.</p></div>

    <div class="detail-grid">
        <div class="detail-stack">
            <section class="card detail-card">
                <div class="card-h"><div class="split-head"><div><h2>Perbandingan harga</h2><p class="meta">Graha Melati · Venue · ID permintaan PWV-0017</p></div><span class="badge b-warn">Menunggu keputusan</span></div></div>
                <div class="card-body">
                    <div class="detail-kv">
                        <div class="kv"><span class="k">Diajukan pada</span><span class="v">02 November 2026, 08.40 WIB</span></div>
                        <div class="kv"><span class="k">Diajukan oleh</span><span class="v">Graha Melati (Pemilik venue)</span></div>
                        <div class="kv"><span class="k">Acara</span><span class="v">Pesta Kebun Raka</span></div>
                        <div class="kv"><span class="k">Perkiraan tamu</span><span class="v">60 orang</span></div>
                    </div>
                    <div class="price-compare">
                        <div><small>Harga katalog (referensi)</small><b>Rp 8.600.000</b></div>
                        <span>→</span>
                        <div><small>Harga diajukan penyedia</small><b>Rp 8.250.000</b></div>
                    </div>
                    <div class="detail-kv">
                        <div class="kv"><span class="k">Perubahan nominal</span><span class="v">− Rp 350.000</span></div>
                        <div class="kv"><span class="k">Persentase perubahan</span><span class="v">−4,07%</span></div>
                    </div>
                    <div class="detail-list">
                        <div class="dl-row"><span>Harga final (belum ditetapkan)</span><strong class="warn-t">Menunggu keputusan Admin</strong></div>
                        <div class="dl-row"><span>Komisi layanan Pesta Kita (10%)</span><strong>Ditambahkan ke tagihan Klien</strong></div>
                    </div>
                </div>
            </section>

            <section class="card detail-card">
                <div class="card-h"><h2>Kebutuhan Klien</h2><p class="meta">Keterangan permintaan yang diteruskan ke penyedia.</p></div>
                <div class="card-body">
                    <div class="quote-panel">Penambahan jam penggunaan satu jam, area bermain anak, dan penataan meja panjang.</div>
                    <div class="alert mt-16"><span class="ic i-info" aria-hidden="true"></span><p>Identitas dan kontak Klien tidak ditampilkan ke penyedia; komunikasi dilakukan melalui Pesta Kita.</p></div>
                </div>
            </section>

            <section class="card detail-card">
                <div class="card-h"><h2>Rincian penawaran</h2><p class="meta">Komponen harga katalog dan penawaran yang diajukan.</p></div>
                <div aria-label="Rincian penawaran. Gulir horizontal untuk melihat seluruh kolom" class="table-wrap" role="region" tabindex="0">
                    <table class="detail-table t-wide">
                        <caption class="sr-only">Tabel: Rincian penawaran</caption>
                        <thead>
                            <tr><th scope="col">Komponen</th><th scope="col">Keterangan</th><th class="num" scope="col">Harga katalog</th><th class="num" scope="col">Harga usulan</th></tr>
                        </thead>
                        <tbody>
                            <tr><td><b>Harga sewa venue</b></td><td>Penggunaan ruangan utama</td><td class="num">Rp 7.500.000</td><td class="num">Rp 7.150.000</td></tr>
                            <tr><td><b>Jam tambahan</b></td><td>Penambahan 1 jam</td><td class="num">Belum ditentukan</td><td class="num">Rp 750.000</td></tr>
                            <tr><td><b>Biaya kebersihan</b></td><td>Ketentuan venue</td><td class="num">Rp 350.000</td><td class="num">Rp 350.000</td></tr>
                            <tr><td><b>Biaya layanan Pesta Kita (10%)</b></td><td>Ditambahkan ke tagihan Klien</td><td class="num">Rp 750.000</td><td class="num">Ditambah sistem</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-body"><div class="alert m-0"><span class="ic i-info" aria-hidden="true"></span><p>Angka di atas contoh statis. Total tagihan final Klien dihitung backend dari harga yang disetujui ditambah komisi 10%; halaman ini tidak menetapkan nominal final.</p></div></div>
            </section>

            <section class="card detail-card">
                <div class="card-h"><h2>Riwayat penawaran</h2></div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item"><b>Permintaan Klien masuk</b><small>01 Nov 2026 · 10.12 WIB</small></div>
                        <div class="timeline-item"><b>Diteruskan ke penyedia</b><small>01 Nov 2026 · 10.13 WIB</small></div>
                        <div class="timeline-item"><b>Penyedia mengusulkan harga Rp 8.250.000</b><small>02 Nov 2026 · 08.40 WIB</small></div>
                        <div class="timeline-item"><b>Masuk antrean keputusan Admin</b><small>02 Nov 2026 · 08.41 WIB</small></div>
                        <div class="timeline-item"><b>Menunggu keputusan</b><small>Status saat ini</small></div>
                    </div>
                </div>
            </section>
        </div>

        <aside class="detail-side">
            <section class="card detail-card">
                <div class="card-h"><h2>Tindakan Admin</h2><p class="meta">Tinjau dan tetapkan keputusan penawaran.</p></div>
                <div class="card-body">
                    <label class="field-label" for="decision-quote">Keputusan</label>
                    <select aria-label="Keputusan" class="field-control" id="decision-quote">
                        <option>Belum diputuskan</option>
                        <option>Setujui penawaran</option>
                        <option>Tolak penawaran</option>
                        <option>Minta usulan ulang</option>
                    </select>
                    <label class="field-label" for="reason-quote">Catatan Admin</label>
                    <textarea class="field-control" id="reason-quote" placeholder="Tuliskan pertimbangan keputusan…" rows="4"></textarea>
                    <div class="detail-actions">
                        <button class="btn" type="button" data-confirm="Simpan keputusan?" data-text="Keputusan wajib beralasan dan dicatat di log aktivitas. Pada prototipe ini keputusan belum tersimpan ke server." data-label="Simpan keputusan" data-need="main #reason-quote" data-need-msg="Alasan keputusan wajib diisi.">Simpan keputusan</button>
                        <button class="btn btn-danger" type="button" data-confirm="Tolak penawaran ini?" data-text="Penyedia dan Klien diberi tahu, lalu penawaran perlu diusulkan ulang. Pada prototipe ini keputusan belum tersimpan ke server." data-label="Tolak penawaran" data-tone="bad" data-ask="Alasan penolakan">Tolak penawaran</button>
                    </div>
                    <div class="alert mt-16"><span class="ic i-info" aria-hidden="true"></span><p>Persetujuan hanya berlaku untuk permintaan ini. Harga pada pesanan yang telah disetujui tetap mengikuti harga sebelumnya.</p></div>
                </div>
            </section>

            <section class="card detail-card">
                <div class="card-h"><h2>Informasi penyedia</h2></div>
                <div class="card-body">
                    <div class="detail-list">
                        <div class="dl-row"><span>Nama penyedia</span><strong>Graha Melati</strong></div>
                        <div class="dl-row"><span>Jenis</span><strong>Pemilik venue</strong></div>
                        <div class="dl-row"><span>Status penyedia</span><strong class="ok-t">Terverifikasi</strong></div>
                        <div class="dl-row"><span>Penawaran sebelumnya</span><strong>2 kali</strong></div>
                    </div>
                    <a class="btn btn-o btn-block" href="{{ url('/admin/mitra-detail') }}">Lihat detail penyedia</a>
                </div>
            </section>
        </aside>
    </div>
</main>
@endsection
