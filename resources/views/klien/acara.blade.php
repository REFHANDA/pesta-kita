@extends('layouts.klien')

@section('title', 'Rancang acara · Pesta Kita')

@php
$events = $events ?? collect();

// Kelas & label badge status acara — sama dengan sumber data beranda.
$badgeFor = fn ($status) => match ($status) {
    \App\Enums\EventStatus::Draft => ['b-mute', 'Draft'],
    \App\Enums\EventStatus::Diajukan => ['b-warn', 'Diajukan'],
    \App\Enums\EventStatus::MenungguPembayaran => ['b-warn', 'Menunggu pembayaran'],
    \App\Enums\EventStatus::Terkonfirmasi => ['b-ok', 'Terkonfirmasi'],
    \App\Enums\EventStatus::Selesai => ['b-ok', 'Selesai'],
    \App\Enums\EventStatus::Dinilai => ['b-ok', 'Dinilai'],
    \App\Enums\EventStatus::Dibatalkan => ['b-mute', 'Dibatalkan'],
};
@endphp

@section('content')
<main id="konten" class="content" tabindex="-1"><div class="page-head"><div><div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / Rancang acara</div><h1>Rancang acara</h1><p class="sub">Acara milik akun Anda yang masih berjalan.</p></div><a class="btn" href="{{ url('/klien/katalog-vendor') }}"><span class="ic i-box" aria-hidden="true"></span>Jelajahi katalog</a></div>
<div class="tabs card"><a href="{{ url('/klien/acara') }}" aria-current="page">Mendatang</a><a href="{{ url('/klien/riwayat') }}">Riwayat</a></div>
<div class="data-note" role="note"><span class="ic i-info" aria-hidden="true"></span><p><b>Belum semua alur terhubung.</b> Daftar di halaman ini dibaca dari basis data acara milik akun Anda dan dapat dibuka untuk melihat detail tersimpan. Pembuatan konsep acara dan pesanan menyusul pada backend BE-4–BE-6.</p></div>
<div class="cards">
@forelse($events as $event)
    @php([$badgeClass, $badgeLabel] = $badgeFor($event->status))
    <div class="card ev"><div class="item-h"><h2><a class="link-plain" href="{{ url('/klien/acara-detail/'.$event->id) }}">{{ $event->name }}</a></h2><span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span></div><p>{{ $event->event_date?->translatedFormat('l, d F Y') ?? 'Tanggal belum diisi' }}</p><p>{{ $event->guest_count }} tamu{{ $event->theme ? ' · '.$event->theme : '' }}</p></div>
@empty
    <p class="hint" style="padding:16px">Belum ada acara mendatang tercatat untuk akun Anda. Pembuatan konsep acara tersedia setelah backend alur acara (BE-4/BE-5) terhubung; sementara itu Anda dapat menjelajahi katalog penyedia dan peralatan.</p>
@endforelse
</div>
</main>
@endsection
