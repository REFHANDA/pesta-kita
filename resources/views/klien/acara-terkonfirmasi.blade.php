@extends('layouts.klien')

@section('title', 'Acara terkonfirmasi · Pesta Kita')

@php
$events = $events ?? collect();
@endphp

@section('content')
<main id="konten" class="content" tabindex="-1"><div class="page-head"><div><div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / <a href="{{ url('/klien/acara') }}">Acara saya</a> / Terkonfirmasi</div><h1>Acara terkonfirmasi</h1><p class="sub">Acara milik akun Anda yang sudah terkonfirmasi pembayarannya.</p></div><a class="btn" href="{{ url('/klien/acara') }}"><span class="ic i-info" aria-hidden="true"></span>Semua acara mendatang</a></div>

<div class="cards">
@forelse($events as $event)
    <div class="card ev"><div class="item-h"><h2><a class="link-plain" href="{{ url('/klien/acara-detail/'.$event->id) }}">{{ $event->name }}</a></h2><span class="badge b-ok">Terkonfirmasi</span></div><p>{{ $event->event_date?->translatedFormat('l, d F Y') ?? 'Tanggal belum diisi' }}</p><p>{{ $event->guest_count }} tamu{{ $event->theme ? ' · '.$event->theme : '' }}</p></div>
@empty
    <p class="hint" style="padding:16px">Belum ada acara terkonfirmasi untuk akun Anda.</p>
@endforelse
</div>
</main>
@endsection
