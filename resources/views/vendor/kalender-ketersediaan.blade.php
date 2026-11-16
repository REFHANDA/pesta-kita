@extends('layouts.vendor')

@section('title', 'Kalender ketersediaan · Panel Vendor Pesta Kita')

@php
$items = $items ?? collect();
$statusToKey = [
    \App\Enums\AvailabilityStatus::Tersedia->value => 'tersedia',
    \App\Enums\AvailabilityStatus::TidakTersedia->value => 'tidak',
    \App\Enums\AvailabilityStatus::BelumDikonfirmasi->value => 'belum',
];
$datasets = $items->map(fn ($item) => [
    'key' => (string) $item->id,
    'nama' => $item->name,
    'jenis' => $item->kind->value === 'layanan' ? 'Layanan' : 'Peralatan',
    'days' => (object) $item->availabilityDays->mapWithKeys(fn ($day) => [$day->date->toDateString() => $statusToKey[$day->status->value] ?? 'belum'])->all(),
])->all();
$kalConfig = json_encode([
    'editable' => true,
    'start' => ['year' => (int) now()->format('Y'), 'month' => (int) now()->format('n')],
    'datasets' => $datasets,
], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
@endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/kalender.css') }}">
@endpush

@section('content')
<main id="konten" class="content" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/vendor') }}">Beranda</a> / Kalender ketersediaan</div>
            <h1>Kalender ketersediaan</h1>
            <p class="sub">Lihat dan atur jadwal ketersediaan layanan dan peralatan Anda. Klien hanya melihat gambaran jadwal ini; ketersediaan tetap perlu dikonfirmasi sebelum pesanan disahkan Admin.</p>
        </div>
    </div>

    @if(session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
    @error('date')<div class="alert warn" role="alert">{{ $message }}</div>@enderror
    @error('status')<div class="alert warn" role="alert">{{ $message }}</div>@enderror

    @if(count($datasets) === 0)
    <div class="empty-state card mt-16">
        <span class="ic i-cal" aria-hidden="true"></span>
        <h2>Belum ada item untuk dijadwalkan</h2>
        <p>Kalender menampilkan item katalog milik usaha Anda. Tambahkan layanan atau peralatan terlebih dahulu.</p>
        <a class="btn mt-12" href="{{ url('/vendor/item-form') }}">Tambah layanan</a>
    </div>
    @else

    <div data-kalender>
        <section class="card" data-kal-controls>
            <div class="card-b">
                <div class="kal-toolbar">
                    <div class="field">
                        <label for="kal-picker">Layanan atau peralatan</label>
                        <select class="input" id="kal-picker"></select>
                        <p class="hint">Pilih item untuk melihat dan menandai jadwal ketersediaannya.</p>
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
                        <caption class="sr-only">Kalender ketersediaan item pada bulan yang sedang ditampilkan</caption>
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
                        <p><b>Belum ada jadwal yang ditandai untuk item ini.</b></p>
                        <p class="hint">Pilih tanggal pada kalender, lalu tandai statusnya melalui panel kelola di samping.</p>
                    </div>
                </div>
            </section>

            <aside class="stack">
                <section class="card">
                    <div class="card-h"><h2>Detail tanggal</h2></div>
                    <div class="card-b" id="kal-detail-body" aria-live="polite">
                        <p class="hint">Pilih tanggal pada kalender untuk melihat dan mengubah ketersediaannya.</p>
                    </div>
                </section>

                <section class="card" data-kal-editable hidden>
                    <div class="card-h"><h2>Kelola jadwal item</h2></div>
                    <div class="card-b">
                        <p class="hint">Pilih item dan tanggal pada kalender, lalu tandai statusnya. Perubahan tersimpan ke server dan langsung terlihat oleh Klien di katalog.</p>
                        <form method="POST" action="{{ route('vendor.kalender.update') }}" class="kal-edit">
                            @csrf
                            <input type="hidden" name="item_id" id="kal-form-item" value="{{ $selectedItemId }}">
                            <div class="kal-edit-row mt-12">
                                <button class="btn btn-o" type="submit" name="status" value="tersedia">Tandai tersedia</button>
                                <button class="btn btn-o" type="submit" name="status" value="tidak_tersedia">Tandai tidak tersedia</button>
                                <button class="btn btn-o" type="submit" name="status" value="belum_dikonfirmasi">Atur ulang ke belum dikonfirmasi</button>
                            </div>
                            <p class="hint mt-12">Tanggal terpilih: <span id="kal-form-date-view">—</span></p>
                            <input type="hidden" name="date" id="kal-form-date" value="">
                            <div class="warn-box mt-12">Status manual adalah gambaran jadwal, bukan jaminan. Konfirmasi final tetap lewat proses penawaran dan persetujuan Admin.</div>
                        </form>
                    </div>
                </section>

                <section class="card">
                    <div class="card-h"><h2>Daftar tanggal berstatus</h2></div>
                    <div class="card-b" id="kal-list" aria-live="polite"></div>
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
<script>
/* Wiring BE-3b: salin tanggal terpilih pada kalender ke form kelola. */
(function () {
    'use strict';
    var grid = document.getElementById('kal-grid');
    var dateInput = document.getElementById('kal-form-date');
    var dateView = document.getElementById('kal-form-date-view');
    var picker = document.getElementById('kal-picker');
    var formItem = document.getElementById('kal-form-item');
    if (!grid || !dateInput) return;
    function sync(date) {
        dateInput.value = date || '';
        if (dateView) dateView.textContent = date || '—';
    }
    grid.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('.kal-day') : null;
        sync(btn ? btn.getAttribute('data-date') : '');
    });
    if (picker && formItem) {
        picker.addEventListener('change', function () { formItem.value = picker.value; sync(''); });
        formItem.value = picker.value || formItem.value;
    }
})();
</script>
@endpush
@endif
