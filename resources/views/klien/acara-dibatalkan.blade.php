@extends('layouts.klien')

@section('title', 'Acara dibatalkan · Pesta Kita')

@php
$events = $events ?? collect();
@endphp

@section('content')
<main id="konten" class="content" tabindex="-1"><div class="page-head"><div><div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / <a href="{{ url('/klien/riwayat') }}">Riwayat &amp; evaluasi</a> / Dibatalkan</div><h1>Acara dibatalkan</h1><p class="sub">Acara milik akun Anda yang berstatus dibatalkan.</p></div><a class="btn" href="{{ url('/klien/riwayat') }}"><span class="ic i-info" aria-hidden="true"></span>Semua riwayat</a></div>
<div class="data-note" role="note"><span class="ic i-info" aria-hidden="true"></span><p><b>Nominal refund belum tersedia.</b> Daftar ini membaca status acara dari basis data. Pengajuan pembatalan, perhitungan refund, dan bukti transfer dikelola oleh backend BE-6 — halaman ini tidak menghitung nominal apa pun.</p></div>
<div class="cards">
@forelse($events as $event)
    <div class="card ev"><div class="item-h"><h2><a class="link-plain" href="{{ url('/klien/acara-detail/'.$event->id) }}">{{ $event->name }}</a></h2><span class="badge b-bad">Dibatalkan</span></div><p>{{ $event->event_date?->translatedFormat('l, d F Y') ?? 'Tanggal belum diisi' }}</p><p>{{ $event->guest_count }} tamu{{ $event->theme ? ' · '.$event->theme : '' }}</p></div>
@empty
    <p class="hint" style="padding:16px">Belum ada acara yang dibatalkan oleh akun Anda.</p>
@endforelse
</div>
</main>
@endsection
