@extends('layouts.klien')

@section('title', 'Riwayat & evaluasi · Pesta Kita')

@php
$events = $events ?? collect();

$badgeFor = fn ($status) => match ($status) {
    \App\Enums\EventStatus::Draft => ['b-mute', 'Draft'],
    \App\Enums\EventStatus::Diajukan => ['b-warn', 'Diajukan'],
    \App\Enums\EventStatus::MenungguPembayaran => ['b-warn', 'Menunggu pembayaran'],
    \App\Enums\EventStatus::Terkonfirmasi => ['b-ok', 'Terkonfirmasi'],
    \App\Enums\EventStatus::Selesai => ['b-ok', 'Selesai'],
    \App\Enums\EventStatus::Dinilai => ['b-brand', 'Dinilai'],
    \App\Enums\EventStatus::Dibatalkan => ['b-bad', 'Dibatalkan'],
};
@endphp

@section('content')
<main id="konten" class="content" tabindex="-1"><div class="page-head"><div><div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / Riwayat &amp; evaluasi</div><h1>Riwayat &amp; evaluasi</h1><p class="sub">Acara milik akun Anda yang sudah selesai, dinilai, atau dibatalkan.</p></div></div>
<div class="tabs card"><a href="{{ url('/klien/acara') }}">Mendatang</a><a href="{{ url('/klien/riwayat') }}" aria-current="page">Riwayat</a></div>
<div class="data-note" role="note"><span class="ic i-info" aria-hidden="true"></span><p><b>Penilaian dan refund belum terhubung.</b> Daftar acara di bawah dibaca dari basis data dan dapat dibuka untuk melihat detail tersimpan. Memberi penilaian (BE-7) dan melihat rincian refund (BE-6) belum dapat dilakukan.</p></div>
<section class="card"><div class="table-wrap" role="region" tabindex="0" aria-label="Riwayat acara. Gulir horizontal untuk melihat seluruh kolom"><table class="t-wide"><caption class="sr-only">Tabel: Riwayat acara</caption><thead><tr><th scope="col">Acara</th><th scope="col">Tanggal</th><th scope="col">Tamu</th><th scope="col">Status</th></tr></thead><tbody>
@forelse($events as $event)
    @php([$badgeClass, $badgeLabel] = $badgeFor($event->status))
    <tr><td><b><a class="link-plain" href="{{ url('/klien/acara-detail/'.$event->id) }}">{{ $event->name }}</a></b>@if($event->theme)<small class="sub-t">{{ $event->theme }}</small>@endif</td><td>{{ $event->event_date?->translatedFormat('d M Y') ?? '—' }}</td><td>{{ $event->guest_count }} tamu</td><td><span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span></td></tr>
@empty
    <tr><td colspan="4"><p class="hint" style="padding:16px;margin:0">Belum ada acara yang selesai, dinilai, atau dibatalkan. Riwayat terisi otomatis setelah status acara berubah pada backend alur acara (BE-5).</p></td></tr>
@endforelse
</tbody></table></div></section></main>
@endsection
