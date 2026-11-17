@extends('layouts.venue')

@section('title', 'Pesanan · Panel Pemilik Venue Pesta Kita')

@php
    $statusLabel = fn ($status) => match ($status) {
        \App\Enums\OrderItemStatus::MenungguAdmin => ['b-warn', 'Menunggu review Admin'],
        \App\Enums\OrderItemStatus::MasaKeberatan => ['b-warn', 'Masa keberatan'],
        \App\Enums\OrderItemStatus::Mengikat => ['b-ok', 'Mengikat'],
        \App\Enums\OrderItemStatus::Disetujui => ['b-ok', 'Disetujui'],
        \App\Enums\OrderItemStatus::Ditolak => ['b-bad', 'Ditolak'],
        default => ['b-mute', $status->value],
    };
@endphp

@section('content')
<main id="konten" class="content" tabindex="-1">
    <div class="page-head">
        <div><div class="crumbs"><a href="{{ route('venue.beranda') }}">Beranda</a> / Pesanan</div><h1>Pesanan</h1><p class="sub">Nama dan identitas Klien tidak ditampilkan.</p></div>
        <a class="btn btn-o" href="{{ route('venue.keberatan') }}">Keberatan saya</a>
    </div>
    <section class="card">
        <div class="table-wrap" role="region" tabindex="0" aria-label="Daftar pesanan">
            <table class="t-wide">
                <caption class="sr-only">Pesanan ruangan venue Anda</caption>
                <thead><tr><th scope="col">Pesanan</th><th scope="col">Tanggal acara</th><th scope="col" class="num">Tamu</th><th scope="col" class="num">Harga final</th><th scope="col">Status</th></tr></thead>
                <tbody>
                @forelse ($orderItems as $orderItem)
                    @php [$badgeClass, $badgeLabel] = $statusLabel($orderItem->status); @endphp
                    <tr>
                        <td><a href="{{ route('venue.pesanan.detail', $orderItem) }}"><b>Pesanan #{{ $orderItem->id }}</b></a><span class="sub-t">{{ $orderItem->subject?->name ?? 'Ruangan tidak tersedia' }}</span></td>
                        <td>{{ $orderItem->event?->event_date?->translatedFormat('l, d M Y') }}</td>
                        <td class="num">{{ number_format($orderItem->event?->guest_count ?? 0) }}</td>
                        <td class="num">{{ $orderItem->final_price === null ? 'Belum ditetapkan' : 'Rp '.number_format($orderItem->final_price, 0, ',', '.') }}</td>
                        <td><span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span></td>
                    </tr>
                @empty
                    <tr><td class="hint" colspan="5">Belum ada pesanan untuk ruangan venue Anda.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($orderItems, 'links'))<div class="card-b">{{ $orderItems->links() }}</div>@endif
    </section>
</main>
@endsection
