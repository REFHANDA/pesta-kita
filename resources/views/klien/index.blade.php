@extends('layouts.klien')

@section('title', 'Beranda Klien · Pesta Kita')

@section('content')
@php
$events = $events ?? collect();
$berjalanCount = $berjalanCount ?? 0;
$selesaiCount = $selesaiCount ?? 0;
@endphp
<main id="konten" class="content" tabindex="-1"><div class="page-head"><div><div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a></div><h1>Halo, {{ auth()->user()->name }}</h1><p class="sub">Susun acara, pilih penyedia, dan bayar dalam satu tagihan.</p></div><a class="btn" href="{{ url('/klien/katalog-vendor') }}"><span class="ic i-box" aria-hidden="true"></span>Jelajahi katalog</a></div>
<div class="data-note" role="note"><span class="ic i-info" aria-hidden="true"></span><p><b>Sebagian fitur belum terhubung ke backend.</b> Angka ringkasan dan daftar acara di halaman ini bersumber dari database akun Anda. Pembuatan konsep acara, detail acara, tagihan, dan penilaian menyusul pada tahap backend berikutnya (BE-4–BE-7).</p></div>
<div class="grid stats s3"><div class="card stat"><span class="ic-box"><span class="ic i-cal" aria-hidden="true"></span></span><div><div class="lbl">Acara berjalan</div><div class="val">{{ $berjalanCount }}</div></div></div><div class="card stat"><span class="ic-box"><span class="ic i-ok" aria-hidden="true"></span></span><div><div class="lbl">Acara selesai</div><div class="val">{{ $selesaiCount }}</div></div></div><div class="card stat"><span class="ic-box"><span class="ic i-star" aria-hidden="true"></span></span><div><div class="lbl">Total acara</div><div class="val">{{ $events->count() }}</div></div></div></div>
<div class="grid two"><section class="card"><div class="card-h"><h2>Acara Anda</h2></div>
@forelse($events as $event)
@php
[$badgeClass, $badgeLabel] = match($event->status) {
    \App\Enums\EventStatus::Draft => ['b-mute', 'Draft'],
    \App\Enums\EventStatus::Diajukan => ['b-warn', 'Diajukan'],
    \App\Enums\EventStatus::MenungguPembayaran => ['b-warn', 'Menunggu pembayaran'],
    \App\Enums\EventStatus::Terkonfirmasi => ['b-ok', 'Terkonfirmasi'],
    \App\Enums\EventStatus::Selesai => ['b-ok', 'Selesai'],
    \App\Enums\EventStatus::Dinilai => ['b-ok', 'Dinilai'],
    \App\Enums\EventStatus::Dibatalkan => ['b-mute', 'Dibatalkan'],
};
@endphp
<div class="row-link"><span class="ic-box"><span class="ic i-cal" aria-hidden="true"></span></span><span class="grow">{{ $event->name }}<span class="help">{{ $event->event_date?->format('d/m/Y') ?? 'Tanggal belum diisi' }} · {{ $event->guest_count }} tamu{{ $event->theme ? ' · '.$event->theme : '' }}</span></span><span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span></div>
@empty
<div class="card-b"><p class="hint" style="margin-top:0">Belum ada acara tercatat. Pembuatan konsep acara tersedia setelah alur acara backend (BE-4/BE-5) terhubung; sementara itu Anda dapat menjelajahi katalog penyedia.</p><a class="btn btn-o mt-12" href="{{ url('/klien/katalog-vendor') }}">Jelajahi katalog</a></div>
@endforelse
</section>
<div class="stack"><section class="card"><div class="card-h"><h2>Tagihan menunggu dibayar</h2></div><div class="card-b"><div class="data-note" role="note"><span class="ic i-info" aria-hidden="true"></span><p>Tagihan gabungan dan pembayaran transfer manual tersedia setelah backend transaksi (BE-6) terhubung. Belum ada data tagihan untuk ditampilkan.</p></div></div></section>
<section class="card"><div class="card-h"><h2>Menunggu penilaian</h2></div><div class="card-b"><div class="data-note" role="note"><span class="ic i-info" aria-hidden="true"></span><p>Penilaian penyedia tersedia setelah acara selesai dan backend ulasan (BE-7) terhubung.</p></div></div></section></div></div></main>
@endsection