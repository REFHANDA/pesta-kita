@extends('layouts.klien')

@section('title', 'Beranda Klien · Pesta Kita')

@section('content')
@php
$events = $events ?? collect();
$berjalanCount = $berjalanCount ?? 0;
$selesaiCount = $selesaiCount ?? 0;
@endphp
<main id="konten" class="content" tabindex="-1"><div class="page-head"><div><div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a></div><h1>Halo, {{ auth()->user()->name }}</h1><p class="sub">Susun acara, pilih penyedia, dan bayar dalam satu tagihan.</p></div><a class="btn" href="{{ url('/klien/katalog-vendor') }}"><span class="ic i-box" aria-hidden="true"></span>Jelajahi katalog</a></div>
<div class="grid stats s3"><div class="card stat"><span class="ic-box"><span class="ic i-cal" aria-hidden="true"></span></span><div><div class="lbl">Acara berjalan</div><div class="val">{{ $berjalanCount }}</div></div></div><div class="card stat"><span class="ic-box"><span class="ic i-ok" aria-hidden="true"></span></span><div><div class="lbl">Acara selesai</div><div class="val">{{ $selesaiCount }}</div></div></div><div class="card stat"><span class="ic-box"><span class="ic i-star" aria-hidden="true"></span></span><div><div class="lbl">Total acara</div><div class="val">{{ $events->count() }}</div></div></div></div>
<div class="grid two"><section class="card"><div class="card-h"><h2>Acara Anda</h2><div class="actions"><a class="btn btn-o btn-sm" href="{{ route('klien.acara.baru') }}">Buat acara baru</a></div></div>
@forelse($events as $event)
@php
[$badgeClass, $badgeLabel] = match($event->status) {
    \App\Enums\EventStatus::Draft => ['b-mute', 'Draft'],
    \App\Enums\EventStatus::Diajukan => ['b-warn', 'Diajukan'],
    \App\Enums\EventStatus::MenungguPembayaran => ['b-warn', 'Menunggu pembayaran'],
    \App\Enums\EventStatus::Terkonfirmasi => ['b-ok', 'Terkonfirmasi'],
    \App\Enums\EventStatus::Selesai => ['b-ok', 'Selesai'],
    \App\Enums\EventStatus::Dievaluasi => ['b-ok', 'Dievaluasi'],
    \App\Enums\EventStatus::Dibatalkan => ['b-mute', 'Dibatalkan'],
};
$latestCancellation = $event->cancellations->sortByDesc('id')->first();
if ($latestCancellation?->isActive()) {
    [$badgeClass, $badgeLabel] = ['b-warn', 'Pembatalan diproses · '.$latestCancellation->status->label()];
} elseif ($latestCancellation?->status === \App\Enums\CancellationStatus::RefundSelesai) {
    $badgeLabel = 'Dibatalkan · refund selesai';
}
@endphp
<a class="row-link" href="{{ route('klien.acara.detail', $event) }}"><span class="ic-box"><span class="ic i-cal" aria-hidden="true"></span></span><span class="grow">{{ $event->name }}<span class="help">{{ $event->event_date?->format('d/m/Y') ?? 'Tanggal belum diisi' }} · {{ $event->guest_count }} tamu{{ $event->theme ? ' · '.$event->theme : '' }}</span></span><span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span></a>
@empty
<div class="card-b"><p class="hint" style="margin-top:0">Belum ada acara tercatat. Mulai buat acara dan pilih layanan atau venue dari katalog penyedia.</p><a class="btn btn-o mt-12" href="{{ route('klien.acara.baru') }}">Buat acara baru</a></div>
@endforelse
</section>
<div class="stack">
<section class="card">
    <div class="card-h" style="display:flex;justify-content:space-between;align-items:center;">
        <h2>Tagihan menunggu dibayar</h2>
        @if(isset($pendingInvoices) && $pendingInvoices->isNotEmpty())
        <span class="chip">{{ $pendingInvoices->count() }} menunggu</span>
        @endif
    </div>
    <div class="card-b">
        @forelse($pendingInvoices ?? [] as $inv)
        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--line, #e2e8f0);">
            <div>
                <b><a href="{{ route('klien.tagihan.show', $inv) }}">{{ $inv->invoice_number }}</a></b>
                <p class="hint" style="margin:2px 0 0;">{{ $inv->event?->name }} · Total: <b>Rp {{ number_format($inv->transfer_total, 0, ',', '.') }}</b></p>
            </div>
            <div>
                <a class="btn btn-sm" href="{{ route('klien.tagihan.show', $inv) }}">
                    {{ $inv->status === \App\Enums\InvoiceStatus::MenungguValidasi ? 'Lihat' : 'Bayar' }}
                </a>
            </div>
        </div>
        @empty
        <p class="hint" style="margin:0;">Tidak ada tagihan yang menunggu pembayaran saat ini.</p>
        @endforelse
    </div>
</section>
<section class="card"><div class="card-h"><h2>Menunggu penilaian</h2></div><div class="card-b"><p class="hint" style="margin:0">Belum ada acara yang menunggu penilaian.</p></div></section></div></div></main>
@endsection