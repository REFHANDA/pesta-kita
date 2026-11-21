@extends('layouts.klien')

@section('title', 'Acara saya · Pesta Kita')

@php
$events = $events ?? collect();
$tab = $tab ?? 'mendatang';

// Kelas & label badge status acara — sama dengan sumber data beranda.
$badgeFor = fn ($status) => match ($status) {
    \App\Enums\EventStatus::Draft => ['b-mute', 'Draft'],
    \App\Enums\EventStatus::Diajukan => ['b-warn', 'Diajukan'],
    \App\Enums\EventStatus::MenungguPembayaran => ['b-warn', 'Menunggu pembayaran'],
    \App\Enums\EventStatus::Terkonfirmasi => ['b-ok', 'Terkonfirmasi'],
    \App\Enums\EventStatus::Selesai => ['b-ok', 'Selesai'],
    \App\Enums\EventStatus::Dievaluasi => ['b-ok', 'Dievaluasi'],
    \App\Enums\EventStatus::Dibatalkan => ['b-mute', 'Dibatalkan'],
};
@endphp

@section('content')
<main id="konten" class="content" tabindex="-1">
<div class="page-head">
    <div>
        <div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / Acara saya</div>
        <h1>Acara saya</h1>
        <p class="sub">{{ $tab === 'riwayat' ? 'Acara yang sudah selesai, dinilai, atau dibatalkan.' : 'Acara milik akun Anda yang masih berjalan. Buka acara untuk melihat pesanan dan tagihan.' }}</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a class="btn" href="{{ route('klien.acara.baru') }}"><span class="ic i-plus" aria-hidden="true"></span>Buat Konsep Acara</a>
        <a class="btn btn-o" href="{{ route('klien.katalog.vendor') }}"><span class="ic i-box" aria-hidden="true"></span>Jelajahi katalog</a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success mb-16" style="background:#dcfce7;border:1px solid #22c55e;color:#15803d;padding:12px;border-radius:6px;margin-bottom:16px;">
    {{ session('success') }}
</div>
@endif

<x-subnav label="Acara saya" :items="[
    ['label' => 'Mendatang', 'href' => route('klien.acara'), 'active' => $tab === 'mendatang'],
    ['label' => 'Riwayat', 'href' => route('klien.acara', ['tab' => 'riwayat']), 'active' => $tab === 'riwayat'],
]" />

<div class="cards">
@forelse($events as $event)
    @php([$badgeClass, $badgeLabel] = $badgeFor($event->status))
    <div class="card ev">
        <div class="item-h">
            <h2><a class="link-plain" href="{{ route('klien.acara.detail', $event) }}">{{ $event->name }}</a></h2>
            <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
        </div>
        <p>{{ $event->event_date?->translatedFormat('l, d F Y') ?? 'Tanggal belum diisi' }}</p>
        <p>{{ $event->guest_count }} tamu{{ $event->theme ? ' · '.$event->theme : '' }} · Anggaran: {{ $event->budget ? 'Rp '.number_format($event->budget, 0, ',', '.') : 'Belum diisi' }}</p>
    </div>
@empty
    <div class="card" style="padding:24px;text-align:center;">
        @if($tab === 'riwayat')
        <p class="hint">Belum ada acara yang selesai, dinilai, atau dibatalkan.</p>
        @else
        <p class="hint">Belum ada acara mendatang tercatat untuk akun Anda. Mulai rancang acara ulang tahun Anda sekarang!</p>
        <a class="btn mt-12" href="{{ route('klien.acara.baru') }}">Buat Konsep Acara Baru</a>
        @endif
    </div>
@endforelse
</div>
</main>
@endsection
