@extends('layouts.klien')

@section('title', 'Kalender ketersediaan · Pesta Kita')

@php
$items = $items ?? collect();
$venues = $venues ?? collect();
$statusToKey = [
    \App\Enums\AvailabilityStatus::Tersedia->value => 'tersedia',
    \App\Enums\AvailabilityStatus::TidakTersedia->value => 'tidak',
    \App\Enums\AvailabilityStatus::BelumDikonfirmasi->value => 'belum',
];
$toDays = fn ($days) => (object) $days->mapWithKeys(fn ($day) => [$day->date->toDateString() => $statusToKey[$day->status->value] ?? 'belum'])->all();
$datasets = collect($items
    ->map(fn ($item) => [
        'key' => 'item-'.$item->id,
        'kategori' => 'vendor',
        'nama' => $item->vendorProfile?->business_name ?? 'Vendor',
        'jenis' => $item->name.' ('.($item->kind->value === 'layanan' ? 'Layanan' : 'Peralatan').')',
        'days' => $toDays($item->availabilityDays),
    ])->all())
    ->merge($venues->flatMap(fn ($venue) => $venue->rooms->map(fn ($room) => [
        'key' => 'room-'.$room->id,
        'kategori' => 'venue',
        'nama' => $venue->name,
        'jenis' => $room->name.' · kapasitas '.$room->capacity,
        'days' => $toDays($room->availabilityDays),
    ]))->all())
    ->all();
$kalConfig = json_encode([
    'editable' => false,
    'start' => ['year' => (int) now()->format('Y'), 'month' => (int) now()->format('n')],
    'datasets' => $datasets,
], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
@endphp

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
        <p><b>Jadwal diisi manual oleh penyedia.</b> Status tersedia/tidak tersedia pada kalender ini adalah gambaran dari penyedia, bukan jaminan dan bukan berarti tanggal sudah dipesan. Konfirmasi final tetap lewat proses permintaan penawaran dan persetujuan Admin Pesta Kita.</p>
    </div>

    @if(count($datasets) === 0)
    <div class="empty-state card mt-16">
        <span class="ic i-cal" aria-hidden="true"></span>
        <h2>Belum ada jadwal yang dapat ditampilkan</h2>
        <p>Vendor dan venue yang tampil di katalog belum menandai tanggal ketersediaan. Silakan ajukan permintaan penawaran untuk mendapatkan konfirmasi.</p>
    </div>
    @else

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
                        <p class="hint">Hanya penyedia terverifikasi dengan katalog tayang yang muncul di sini.</p>
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
                        <li><span class="kal-key kal-ok" aria-hidden="true">&#10003;</span>Tersedia</li>
                        <li><span class="kal-key kal-bad" aria-hidden="true">&#10005;</span>Tidak tersedia</li>
                        <li><span class="kal-key kal-warn" aria-hidden="true">?</span>Belum dikonfirmasi</li>
                    </ul>

                    <div class="kal-nodata" id="kal-nodata" hidden>
                        <p><b>Informasi ketersediaan belum tersedia untuk pilihan ini.</b></p>
                        <p class="hint">Penyedia ini belum menandai tanggal. Silakan ajukan permintaan penawaran untuk mendapatkan konfirmasi.</p>
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
                        <p class="hint">Status ketersediaan di atas adalah gambaran dari penyedia dan tetap perlu dikonfirmasi. Proses permintaan penawaran sedang dikembangkan (BE-4); untuk saat ini halaman tersebut masih prototipe.</p>
                        <a class="btn mt-12" id="kal-quote-link" data-base="{{ url('/klien/permintaan-penawaran') }}" href="{{ url('/klien/permintaan-penawaran') }}">Lihat prototipe permintaan penawaran</a>
                    </div>
                </section>
            </aside>
        </div>
    </div>
    @endif
</main>

@if(count($datasets) > 0)
<script type="application/json" id="kal-config">{!! $kalConfig !!}</script>
@endif
@endsection

@if(count($datasets) > 0)
@push('scripts')
<script src="{{ asset('assets/js/kalender.js') }}"></script>
@endpush
@endif
