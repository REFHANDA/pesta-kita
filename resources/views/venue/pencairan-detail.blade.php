@extends('layouts.venue')

@section('title', 'Detail pencairan · Panel Pemilik Venue Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ route('venue.beranda') }}">Beranda</a> / <a href="{{ route('venue.pencairan') }}">Riwayat pencairan</a> / Detail</div><h1>Detail pencairan</h1></div><a class="btn btn-o" href="{{ route('venue.pencairan') }}">Kembali</a></div>
    <section class="card">
        <div class="card-h"><h2>Transfer tercatat</h2><span class="badge b-ok">Dicatat Admin</span></div>
        <div class="card-b">
            <dl class="dl">
                <div><dt>Acara</dt><dd>{{ $payout->orderItem->event->name }} · {{ $payout->orderItem->event->event_date?->translatedFormat('d M Y') }}</dd></div>
                <div><dt>Item venue</dt><dd>{{ $payout->orderItem->subject?->name ?? 'Item tidak tersedia' }}</dd></div>
                <div><dt>Hak penyedia</dt><dd>Rp {{ number_format($payout->amount, 0, ',', '.') }}</dd></div>
                <div><dt>Tanggal transfer</dt><dd>{{ $payout->transferred_at->translatedFormat('d M Y') }}</dd></div>
                <div><dt>Rekening tujuan</dt><dd>{{ $payout->bank_name_snapshot }} · •••• {{ substr($payout->account_number_snapshot, -4) }} · {{ $payout->account_name_snapshot }}</dd></div>
            </dl>
            @if ($payout->proof_path)<a class="btn btn-o mt-16" href="{{ route('venue.pencairan.bukti', $payout) }}">Unduh bukti transfer</a>@endif
            @if ($payout->notes)<p class="hint mt-16">{{ $payout->notes }}</p>@endif
        </div>
    </section>
</main>
@endsection
