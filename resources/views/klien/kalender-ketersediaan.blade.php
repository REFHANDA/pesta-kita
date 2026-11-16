@extends('layouts.klien')

@section('title', 'Kalender ketersediaan · Pesta Kita')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/katalog.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/kalender.css') }}">
@endpush

@section('content')
<main id="konten" class="content" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / <a href="{{ url('/klien/katalog') }}">Vendor &amp; venue</a> / Kalender ketersediaan</div>
            <h1>Kalender ketersediaan</h1>
            <p class="sub">Lihat gambaran jadwal ketersediaan Vendor dan Venue sebelum mengajukan permintaan penawaran. Ketersediaan tetap perlu dikonfirmasi Pesta Kita sebelum pesanan disahkan.</p>
        </div>
        <div class="actions"><a class="btn btn-o" href="{{ url('/klien/katalog') }}">Kembali ke katalog</a></div>
    </div>

    <div class="data-note" role="note">
        <span class="ic i-info" aria-hidden="true"></span>
        <p><b>Data contoh (prototipe).</b> Jadwal pada kalender ini adalah contoh statis untuk pratinjau UI, bukan ketersediaan sebenarnya. Belum ada kalender yang tersimpan di server, dan tanggal contoh tidak boleh disimpulkan sebagai sudah dipesan.</p>
    </div>

    <div data-kalender>
        <section class="card mt-16" data-kal-controls>
            <div class="card-b">
                <div class="kal-toolbar">
                    <div class="field">
                        <label for="kal-kategori">Kategori</label>
                        <select class="input" id="kal-kategori">
                            <option value="vendor">Vendor</option>
                            <option value="venue">Venue</option>
                        </select>
                        <p class="hint">Pilih jenis penyedia yang ingin Anda lihat.</p>
                    </div>
                    <div class="field">
                        <label for="kal-picker">Vendor atau venue</label>
                        <select class="input" id="kal-picker"></select>
                        <p class="hint">Daftar ini hanya berisi contoh.</p>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid two two-wide mt-16">
            <section class="card" id="kal-calendar">
                <div class="card-h">
                    <h2 id="kal-heading">Kalender bulanan</h2>
                    <div class="kal-nav">
                        <button type="button" class="btn btn-o btn-sm" data-kal-prev aria-label="Tampilkan bulan sebelumnya">‹ Sebelumnya</button>
                        <p class="kal-title" id="kal-title" aria-live="polite">—</p>
                        <button type="button" class="btn btn-o btn-sm" data-kal-next aria-label="Tampilkan bulan berikutnya">Berikutnya ›</button>
                    </div>
                </div>
                <div class="card-b">
                    <table class="kal-table" aria-labelledby="kal-title">
                        <caption class="sr-only">Kalender ketersediaan bulan yang sedang ditampilkan</caption>
                        <thead>
                            <tr>
                                <th scope="col" abbr="Senin">Sen</th>
                                <th scope="col" abbr="Selasa">Sel</th>
                                <th scope="col" abbr="Rabu">Rab</th>
                                <th scope="col" abbr="Kamis">Kam</th>
                                <th scope="col" abbr="Jumat">Jum</th>
                                <th scope="col" abbr="Sabtu">Sab</th>
                                <th scope="col" abbr="Minggu">Min</th>
                            </tr>
                        </thead>
                        <tbody id="kal-grid"></tbody>
                    </table>

                    <ul class="kal-legend">
                        <li><span class="kal-key kal-ok" aria-hidden="true">&#10003;</span>Tersedia (contoh)</li>
                        <li><span class="kal-key kal-bad" aria-hidden="true">&#10005;</span>Tidak tersedia (contoh)</li>
                        <li><span class="kal-key kal-warn" aria-hidden="true">?</span>Belum dikonfirmasi</li>
                    </ul>

                    <div class="kal-nodata" id="kal-nodata" hidden>
                        <p><b>Informasi ketersediaan belum tersedia untuk pilihan ini.</b></p>
                        <p class="hint">Silakan hubungi Admin melalui proses permintaan penawaran untuk mendapatkan konfirmasi.</p>
                    </div>
                </div>
            </section>

            <aside class="stack">
                <section class="card">
                    <div class="card-h"><h2>Detail tanggal</h2></div>
                    <div class="card-b" id="kal-detail-body" aria-live="polite">
                        <p class="hint">Pilih tanggal pada kalender untuk melihat keterangan ketersediaannya.</p>
                    </div>
                </section>

                <section class="card">
                    <div class="card-h"><h2>Ajukan permintaan penawaran</h2></div>
                    <div class="card-b">
                        <p class="hint">Tanggal yang Anda pilih dapat dibawa ke pengajuan permintaan penawaran. Status ketersediaan tetap perlu dikonfirmasi dan bukan jaminan.</p>
                        <a class="btn mt-12" id="kal-quote-link" data-base="{{ url('/klien/permintaan-penawaran') }}" href="{{ url('/klien/permintaan-penawaran') }}">Ajukan permintaan penawaran</a>
                    </div>
                </section>
            </aside>
        </div>

        <div class="empty-state card mt-16" id="kal-empty" hidden>
            <span class="ic i-cal" aria-hidden="true"></span>
            <h2>Kalender belum tersedia</h2>
            <p>Belum ada data ketersediaan contoh yang dapat ditampilkan. Silakan hubungi Admin melalui proses permintaan penawaran untuk mendapatkan konfirmasi.</p>
        </div>
    </div>
</main>

<script type="application/json" id="kal-config">
{
  "editable": false,
  "start": { "year": 2026, "month": 11 },
  "datasets": [
    { "key": "dapur-nusantara", "kategori": "vendor", "nama": "Dapur Nusantara", "jenis": "Katering prasmanan",
      "days": { "2026-11-06": "tersedia", "2026-11-07": "tersedia", "2026-11-12": "tidak", "2026-11-13": "tidak", "2026-11-20": "tersedia", "2026-11-21": "tersedia", "2026-11-27": "tersedia", "2026-12-04": "tersedia", "2026-12-05": "tersedia", "2026-12-12": "tidak", "2026-12-19": "tersedia", "2026-12-24": "tidak" } },
    { "key": "balon-ceria", "kategori": "vendor", "nama": "Balon Ceria", "jenis": "Dekorasi balon",
      "days": { "2026-11-08": "tersedia", "2026-11-15": "tersedia", "2026-11-22": "tersedia", "2026-11-29": "tersedia", "2026-12-06": "tersedia", "2026-12-13": "tersedia", "2026-12-20": "tersedia", "2026-12-25": "tidak" } },
    { "key": "harmoni-band", "kategori": "vendor", "nama": "Harmoni Band", "jenis": "Hiburan / musik",
      "days": { "2026-11-01": "tersedia", "2026-11-14": "tersedia", "2026-11-28": "tersedia", "2026-12-05": "tersedia", "2026-12-12": "tersedia", "2026-12-31": "tersedia" } },
    { "key": "graha-melati-ballroom", "kategori": "venue", "nama": "Graha Melati Ballroom", "jenis": "Ballroom",
      "days": { "2026-11-07": "tersedia", "2026-11-14": "tidak", "2026-11-15": "tersedia", "2026-11-21": "tersedia", "2026-11-28": "tidak", "2026-12-05": "tersedia", "2026-12-12": "tersedia", "2026-12-19": "tidak", "2026-12-31": "tersedia" } },
    { "key": "graha-melati-aula", "kategori": "venue", "nama": "Graha Melati Aula", "jenis": "Aula",
      "days": { "2026-11-08": "tersedia", "2026-11-16": "tersedia", "2026-11-30": "tersedia", "2026-12-06": "tersedia", "2026-12-20": "tersedia" } },
    { "key": "taman-sakura", "kategori": "venue", "nama": "Taman Sakura", "jenis": "Area taman (luar ruang)",
      "days": { "2026-11-02": "tersedia", "2026-11-18": "tersedia", "2026-12-08": "tersedia", "2026-12-15": "tersedia" } }
  ]
}
</script>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/kalender.js') }}"></script>
@endpush
