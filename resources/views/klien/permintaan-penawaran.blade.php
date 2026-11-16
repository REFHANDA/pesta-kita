@extends('layouts.klien')

@section('title', 'Permintaan penawaran · Pesta Kita')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/katalog.css') }}">
@endpush

@section('content')
<main id="konten" class="content" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / <a href="{{ url('/klien/katalog-vendor') }}">Vendor &amp; venue</a> / Permintaan penawaran</div>
            <h1>Permintaan penawaran</h1>
            <p class="sub">Ajukan kebutuhan acara Anda, lalu lihat usulan harga dari penyedia. Harga katalog, harga yang diajukan penyedia, dan harga yang disetujui Admin adalah tiga hal yang berbeda.</p>
        </div>
    </div>

    <div class="data-note" role="note">
        <span class="ic i-info" aria-hidden="true"></span>
        <p><b>Fitur ini masih berupa prototipe frontend.</b> Pengajuan dan perubahan penawaran belum tersimpan ke server. Seluruh nama, harga, dan status di halaman ini adalah contoh statis.</p>
    </div>

    @if(request()->filled('tanggal'))
    <div class="alert mt-16" role="status">
        <span class="ic i-cal" aria-hidden="true"></span>
        <p>Tanggal pilihan dari kalender ketersediaan: <b>{{ request('tanggal') }}</b>. Tanggal ini hanya usulan tampilan dan belum tersimpan; ini bukan tanggal yang sudah dipesan dan status ketersediaannya tetap perlu dikonfirmasi.</p>
    </div>
    @endif

    <section class="card mt-16">
        <div class="tabs">
            <a href="{{ url('/klien/permintaan-penawaran') }}" aria-current="page">Semua</a>
            <a href="{{ url('/klien/permintaan-penawaran') }}?status=menunggu">Menunggu penawaran</a>
            <a href="{{ url('/klien/permintaan-penawaran') }}?status=ditinjau">Ditinjau Admin</a>
            <a href="{{ url('/klien/permintaan-penawaran') }}?status=disetujui">Penawaran disetujui</a>
        </div>
        <div class="table-wrap" role="region" tabindex="0" aria-label="Daftar permintaan penawaran. Gulir horizontal untuk melihat seluruh kolom">
            <table class="t-wide">
                <caption class="sr-only">Tabel: Daftar permintaan penawaran</caption>
                <thead>
                    <tr>
                        <th scope="col">Layanan / venue</th>
                        <th scope="col">Acara</th>
                        <th scope="col">Tanggal acara</th>
                        <th scope="col" class="num">Harga katalog (referensi)</th>
                        <th scope="col">Status penawaran</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><b><a class="link-plain" href="{{ url('/klien/permintaan-penawaran-detail') }}">Graha Melati Ballroom</a></b><span class="sub-t">Venue · Graha Melati</span></td>
                        <td>Ulang Tahun ke-7 Nadia</td>
                        <td>14 Nov 2026</td>
                        <td class="num">Rp 8.600.000</td>
                        <td><span class="badge b-ok">Penawaran disetujui</span></td>
                    </tr>
                    <tr>
                        <td><b>Katering prasmanan</b><span class="sub-t">Vendor · Dapur Nusantara</span></td>
                        <td>Pesta Kebun Raka</td>
                        <td>12 Des 2026</td>
                        <td class="num">Rp 2.992.000</td>
                        <td><span class="badge b-warn">Ditinjau Admin</span></td>
                    </tr>
                    <tr>
                        <td><b>Dekorasi balon</b><span class="sub-t">Vendor · Balon Ceria</span></td>
                        <td>Pesta Kebun Raka</td>
                        <td>12 Des 2026</td>
                        <td class="num">Perlu penawaran</td>
                        <td><span class="badge b-mute">Belum ada penawaran</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card mt-16">
        <div class="card-h"><h2>Ajukan permintaan penawaran baru</h2></div>
        <div class="card-b">
            <p class="hint">Pilih layanan atau venue dari katalog, lalu jelaskan kebutuhan acara Anda. Penyedia mengusulkan harga melalui Pesta Kita dan Admin meninjau sebelum penawaran berlaku. Nominal final ditetapkan sistem, bukan oleh halaman ini.</p>
            <div class="grid two two-wide mt-16">
                <div>
                    <form class="form-grid" onsubmit="return false">
                        <div class="field full">
                            <label for="item">Layanan atau venue</label>
                            <select class="input" id="item">
                                <option>Graha Melati Ballroom — Venue · Graha Melati</option>
                                <option>Katering prasmanan — Vendor · Dapur Nusantara</option>
                                <option>Dekorasi balon — Vendor · Balon Ceria</option>
                            </select>
                            <p class="hint">Daftar ini hanya berisi contoh. Pilih dari katalog agar penyedia yang tepat menerima permintaan.</p>
                        </div>
                        <div class="field">
                            <label for="acara">Acara</label>
                            <select class="input" id="acara">
                                <option>Pesta Kebun Raka · 12 Des 2026</option>
                                <option>Ulang Tahun ke-7 Nadia · 14 Nov 2026</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="tanggal">Tanggal acara</label>
                            <input class="input" id="tanggal" value="{{ request()->filled('tanggal') ? request('tanggal') : '12 Des 2026' }}" readonly>
                            <p class="hint">Mengikuti tanggal acara yang dipilih{{ request()->filled('tanggal') ? ' (dari kalender ketersediaan)' : '' }}.</p>
                        </div>
                        <div class="field">
                            <label for="tamu">Perkiraan jumlah tamu</label>
                            <input class="input" id="tamu" inputmode="numeric" placeholder="cth. 60">
                        </div>
                        <div class="field">
                            <label for="anggaran">Anggaran (opsional)</label>
                            <input class="input" id="anggaran" inputmode="numeric" placeholder="cth. Rp 3.000.000">
                            <p class="hint">Anggaran hanya acuan; bukan harga final.</p>
                        </div>
                        <div class="field full">
                            <label for="kebutuhan">Kebutuhan khusus</label>
                            <textarea class="input" id="kebutuhan" rows="3" placeholder="cth. menu bebas kacang, area bermain anak, jam tambahan"></textarea>
                        </div>
                        <div class="full">
                            <button class="btn" type="button" data-confirm="Kirim permintaan penawaran?" data-text="Permintaan akan diteruskan ke penyedia, lalu usulan harga ditinjau Admin sebelum berlaku. Pada prototipe ini permintaan tidak tersimpan ke server." data-label="Kirim permintaan">Kirim permintaan</button>
                        </div>
                    </form>
                </div>
                <aside>
                    <div class="card">
                        <div class="card-h"><h3>Ringkasan permintaan</h3></div>
                        <div class="card-b">
                            <p class="hint mb-8">Periksa ringkasan berikut sebelum mengirim. Angka dan isi di bawah adalah contoh statis.</p>
                            <div class="detail-list">
                                <div class="dl-row"><span>Layanan / venue</span><strong>Graha Melati Ballroom</strong></div>
                                <div class="dl-row"><span>Penyedia</span><strong>Graha Melati</strong></div>
                                <div class="dl-row"><span>Acara</span><strong>Pesta Kebun Raka</strong></div>
                                <div class="dl-row"><span>Tanggal acara</span><strong>12 Des 2026</strong></div>
                                <div class="dl-row"><span>Perkiraan tamu</span><strong>60</strong></div>
                                <div class="dl-row"><span>Harga katalog (referensi)</span><strong>Rp 8.600.000</strong></div>
                            </div>
                            <div class="warn-box mt-12">Harga katalog hanya referensi awal. Harga yang berlaku adalah harga yang disetujui Admin setelah penyedia mengusulkan penawaran.</div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>
</main>
@endsection
