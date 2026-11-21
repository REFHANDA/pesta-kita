@extends('layouts.klien')

@section('title', 'Acara draft · Pesta Kita')

@php
$events = $events ?? collect();
@endphp

@section('content')
<main id="konten" class="content" tabindex="-1"><div class="page-head"><div><div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / <a href="{{ url('/klien/acara') }}">Acara saya</a> / Draft</div><h1>Acara draft</h1><p class="sub">Acara milik akun Anda yang masih berstatus draft.</p></div><a class="btn" href="{{ url('/klien/katalog-vendor') }}"><span class="ic i-box" aria-hidden="true"></span>Jelajahi katalog</a></div>

<div class="cards">
@forelse($events as $event)
    <div class="card ev"><div class="item-h"><h2><a class="link-plain" href="{{ url('/klien/acara-detail/'.$event->id) }}">{{ $event->name }}</a></h2><span class="badge b-mute">Draft</span></div><p>{{ $event->event_date?->translatedFormat('l, d F Y') ?? 'Tanggal belum diisi' }}</p><p>{{ $event->guest_count }} tamu{{ $event->theme ? ' · '.$event->theme : '' }}</p></div>
@empty
    <p class="hint" style="padding:16px">Belum ada acara berstatus draft untuk akun Anda.</p>
@endforelse
</div>
</main>
@endsection
