@extends('layouts.admin')

@section('title', 'Review pesanan · Admin Pesta Kita')

@php
    $orderItems = $orderItems ?? collect();
    $statusFilter = $statusFilter ?? 'menunggu_admin';
    $labels = [
        'menunggu_admin' => 'Menunggu review',
        'masa_keberatan' => 'Masa keberatan',
        'mengikat' => 'Mengikat',
        'ditolak' => 'Ditolak',
        'semua' => 'Semua',
    ];
@endphp

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ route('admin.beranda') }}">Admin</a> / Review pesanan</div>
            <h1>Review pesanan</h1>
            <p class="sub">Setiap item diputuskan terpisah. Harga final diambil dari snapshot server.</p>
        </div>
        <a class="btn btn-o" href="{{ route('admin.keberatan') }}">Keberatan penyedia</a>
    </div>
    @if (session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
    <section class="card">
        <nav aria-label="Filter status" class="tabs">
            @foreach ($labels as $value => $label)
                <a href="{{ route('admin.pesanan', ['status' => $value]) }}" @if ($statusFilter === $value) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        <div class="table-wrap" role="region" tabindex="0" aria-label="Daftar item pesanan">
            <table class="t-wide">
                <caption class="sr-only">Item pesanan acara</caption>
                <thead><tr><th scope="col">Item &amp; penyedia</th><th scope="col">Acara &amp; klien</th><th scope="col">Tanggal</th><th scope="col" class="num">Harga</th><th scope="col">Status</th></tr></thead>
                <tbody>
                @forelse ($orderItems as $orderItem)
                    @php
                        $provider = $orderItem->provider;
                        $providerName = $provider?->business_name ?? 'Penyedia tidak tersedia';
                        $subjectName = $orderItem->subject?->name ?? 'Item tidak tersedia';
                        if ($orderItem->subject instanceof \App\Models\VenueRoom) {
                            $subjectName = $orderItem->subject->venue?->name.' — '.$subjectName;
                        }
                        $amount = $orderItem->final_price ?? $orderItem->proposed_price_snapshot ?? $orderItem->catalog_price_snapshot;
                    @endphp
                    <tr>
                        <td><a class="fw-7 no-ul" href="{{ route('admin.pesanan.detail', $orderItem) }}">{{ $subjectName }}</a><span class="sub-t">{{ $providerName }}</span></td>
                        <td>{{ $orderItem->event?->name }}<span class="sub-t">{{ $orderItem->event?->client?->name }}</span></td>
                        <td>{{ $orderItem->event?->event_date?->translatedFormat('d M Y') }}</td>
                        <td class="num">Rp {{ number_format((int) $amount, 0, ',', '.') }}</td>
                        <td>{{ $labels[$orderItem->status->value] ?? $orderItem->status->value }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="hint">Belum ada item pesanan untuk filter ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($orderItems, 'links'))<div class="card-b">{{ $orderItems->links() }}</div>@endif
    </section>
</main>
@endsection
