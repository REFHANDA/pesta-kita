@extends('layouts.klien')

@section('title', 'Acara draft · Pesta Kita')

@php
$events = $events ?? collect();
@endphp

@section('content')
<main id="konten" class="content" tabindex="-1"><div class="page-head"><div><div class="crumbs"><a href="{{ route('klien.beranda') }}">Beranda</a> / <a href="{{ route('klien.acara') }}">Rancang acara</a> / Draft</div><h1>Acara draft</h1><p class="sub">Acara milik akun Anda yang masih berstatus draft.</p></div><a class="btn" href="{{ route('klien.acara.create') }}"><span class="ic i-box" aria-hidden="true"></span>Buat konsep acara</a></div>
<div class="data-note" role="note"><span class="ic i-info" aria-hidden="true"></span><p>Draft dapat diubah, diisi dengan layanan harga tetap atau penawaran yang sudah disetujui, lalu diajukan ke Admin.</p></div>
<div class="cards">
@forelse($events as $event)
    <div class="card ev"><div class="item-h"><h2><a class="link-plain" href="{{ route('klien.acara.detail', $event) }}">{{ $event->name }}</a></h2><span class="badge b-mute">Draft</span></div><p>{{ $event->event_date?->translatedFormat('l, d F Y') ?? 'Tanggal belum diisi' }}</p><p>{{ $event->guest_count }} tamu{{ $event->theme ? ' · '.$event->theme : '' }}</p><div class="actions"><a class="btn btn-o btn-sm" href="{{ route('klien.acara.edit', $event) }}">Ubah draft</a><a class="btn btn-o btn-sm" href="{{ route('klien.acara.detail', $event) }}">Kelola item</a></div></div>
@empty
    <p class="hint" style="padding:16px">Belum ada acara berstatus draft untuk akun Anda.</p>
@endforelse
</div>
</main>
@endsection
