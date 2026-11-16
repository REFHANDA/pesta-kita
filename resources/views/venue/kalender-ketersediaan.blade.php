@extends('layouts.venue')

@section('title', 'Kalender ketersediaan · Panel Pemilik Venue Pesta Kita')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/kalender.css') }}">
@endpush

@section('content')
<main id="konten" class="content" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/venue') }}">Beranda</a> / Kalender ketersediaan</div>
            <h1>Kalender ketersediaan</h1>
            <p class="sub">Graha Melati — lihat dan atur tampilan jadwal ketersediaan tiap ruangan. Ketersediaan tetap perlu dikonfirmasi sebelum pesanan venue disahkan Admin.</p>
        </div>
    </div>

    <div class="alert"><span class="ic i-info" aria-hidden="true"></span><p><b>Fitur ini masih berupa prototipe frontend.</b> Data ketersediaan ruangan adalah contoh statis dan belum tersimpan di server. Perubahan status pada halaman ini tidak dikirim atau disimpan ke mana pun.</p></div>

    <div data-kalender>
        <section class="card" data-kal-controls>
            <div class="card-b">
                <div class="kal-toolbar">
                    <div class="field">
                        <label for="kal-picker">Ruangan</label>
                        <select class="input" id="kal-picker"></select>
                        <p class="hint">Setiap ruangan memiliki jadwalnya sendiri; ketersediaan satu ruangan tidak otomatis sama dengan ruangan lain. Daftar ini hanya berisi contoh.</p>
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
                        <caption class="sr-only">Kalender ketersediaan ruangan pada bulan yang sedang ditampilkan</caption>
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
                        <p><b>Belum ada jadwal yang ditandai untuk ruangan ini.</b></p>
                        <p class="hint">Tandai tanggal melalui panel kelola untuk menampilkan contoh jadwal pada prototipe ini.</p>
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

                <section class="card" data-kal-editable hidden>
                    <div class="card-h"><h2>Kelola jadwal ruangan (prototipe)</h2></div>
                    <div class="card-b">
                        <p class="hint">Pilih ruangan dan tanggal, lalu tandai statusnya. Interaksi ini hanya mengubah tampilan pada perangkat Anda selama halaman terbuka.</p>
                        <div class="kal-edit">
                            <div class="kal-edit-row">
                                <button type="button" class="btn" data-kal-set="tersedia">Tandai tersedia</button>
                                <button type="button" class="btn btn-o" data-kal-set="tidak">Tandai tidak tersedia</button>
                                <button type="button" class="btn btn-o" data-kal-set="belum">Atur ulang ke belum dikonfirmasi</button>
                            </div>
                            <div class="warn-box mt-12">Perubahan jadwal ini hanya berlaku pada tampilan prototype dan belum tersimpan ke server.</div>
                        </div>
                    </div>
                </section>

                <section class="card">
                    <div class="card-h"><h2>Daftar tanggal berstatus</h2></div>
                    <div class="card-b" id="kal-list" aria-live="polite"></div>
                </section>
            </aside>
        </div>

        <div class="empty-state card mt-16" id="kal-empty" hidden>
            <span class="ic i-cal" aria-hidden="true"></span>
            <h2>Kalender belum tersedia</h2>
            <p>Belum ada data ketersediaan contoh yang dapat ditampilkan.</p>
        </div>
    </div>
</main>

<script type="application/json" id="kal-config">
{
  "editable": true,
  "start": { "year": 2026, "month": 11 },
  "datasets": [
    { "key": "ballroom", "nama": "Graha Melati Ballroom", "jenis": "Ballroom",
      "days": { "2026-11-07": "tersedia", "2026-11-14": "tidak", "2026-11-15": "tersedia", "2026-11-21": "tersedia", "2026-11-28": "tidak", "2026-12-05": "tersedia", "2026-12-12": "tersedia", "2026-12-19": "tidak", "2026-12-31": "tersedia" } },
    { "key": "aula", "nama": "Graha Melati Aula", "jenis": "Aula",
      "days": { "2026-11-08": "tersedia", "2026-11-16": "tersedia", "2026-11-30": "tersedia", "2026-12-06": "tersedia", "2026-12-20": "tersedia" } },
    { "key": "taman", "nama": "Graha Melati Taman", "jenis": "Area taman (luar ruang)",
      "days": { "2026-11-02": "tersedia", "2026-11-18": "tersedia", "2026-12-08": "tersedia", "2026-12-15": "tersedia" } },
    { "key": "ruang-vip", "nama": "Ruang VIP", "jenis": "Ruang pertemuan kecil",
      "days": {} }
  ]
}
</script>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/kalender.js') }}"></script>
@endpush
