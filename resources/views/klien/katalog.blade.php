@extends('layouts.klien')

@section('title', 'Katalog venue · Pesta Kita')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/katalog.css') }}">
@endpush

@section('content')
@php
$venues = $venues ?? collect();
$kinds = $kinds ?? collect();
$filters = $filters ?? [];
@endphp
<main id="konten" class="content" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / Vendor &amp; venue</div>
            <h1>Katalog venue</h1>
            <p class="sub">Hanya venue tayang dari penyedia terverifikasi yang tampil. Harga sewa dan biaya tambahan ditampilkan terpisah agar tidak mengejutkan pada tagihan. Total akhir ditetapkan sistem setelah pesanan disetujui Admin.</p>
        </div>
    </div>

    <section class="card">
        <div class="tabs">
            <a href="{{ route('klien.katalog.vendor') }}">Vendor</a>
            <a href="{{ route('klien.katalog.venue') }}" aria-current="page">Venue</a>
            <a href="{{ route('klien.katalog.peralatan') }}">Peralatan</a>
        </div>
        <div class="pill-row pill-bar" role="group" aria-label="Filter jenis venue">
            <a class="pill" href="{{ route('klien.katalog.venue') }}" @if(empty($filters['kind'])) aria-current="true" @endif>Semua</a>
            @foreach($kinds as $kind)<a class="pill" href="{{ route('klien.katalog.venue', ['kind' => $kind]) }}" @if(($filters['kind'] ?? null) === $kind) aria-current="true" @endif>{{ $kind }}</a>@endforeach
        </div>
        <form class="toolbar" method="GET" action="{{ route('klien.katalog.venue') }}">
            @if(! empty($filters['kind']))<input type="hidden" name="kind" value="{{ $filters['kind'] }}">@endif
            <label class="sr-only" for="kat-cari">Cari nama venue</label>
            <input class="input grow-300" id="kat-cari" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama…" autocomplete="off">
            <label class="sr-only" for="kat-harga">Sewa maksimum</label>
            <input class="input" id="kat-harga" name="max_price" inputmode="numeric" value="{{ $filters['max_price'] ?? '' }}" placeholder="Sewa maks (Rp)" autocomplete="off">
            <button type="submit" class="btn btn-o">Cari</button>
        </form>
    </section>

    <div class="cards">
        @forelse($venues as $venue)
        @php
        $fee = intdiv($venue->rental_price * 10, 100);
        $total = $venue->rental_price + $fee + ($venue->cleaning_fee ?? 0);
        @endphp
        <a class="card cat" href="{{ route('klien.katalog.venue.detail', $venue) }}">
            <div class="ph"><span class="ic i-home" aria-hidden="true"></span><span class="status status-ok"><span class="dot" aria-hidden="true"></span>Terverifikasi</span></div>
            <div class="bd">
                <h2>{{ $venue->name }}</h2>
                <p>{{ $venue->venueProfile?->business_name }} · {{ $venue->kind }}</p>
                @if(($venueRatings[$venue->id]['count'] ?? 0) > 0)<p class="fs-sm" aria-label="Rating venue"><span aria-hidden="true">{{ str_repeat('★', (int) round($venueRatings[$venue->id]['average'])).str_repeat('☆', max(0, 5 - (int) round($venueRatings[$venue->id]['average']))) }}</span> {{ number_format($venueRatings[$venue->id]['average'], 1, ',', '.') }} ({{ $venueRatings[$venue->id]['count'] }} penilaian)</p>@endif
                <ul class="meta">
                    <li><span class="ic i-home" aria-hidden="true"></span>{{ $venue->address }}</li>
                    <li><span class="ic i-users" aria-hidden="true"></span>Kapasitas {{ $venue->capacity }} orang</li>
                </ul>
                <p class="desc">{{ \Illuminate\Support\Str::limit($venue->description ?? '', 120) }}</p>
                <div class="price-row"><div class="price">Rp {{ number_format($venue->rental_price, 0, ',', '.') }} <small>sewa / acara</small></div><span class="ptag ptag-fixed">Harga sewa</span></div>
                <ul class="cost-list">
                    <li><span>+ Biaya kebersihan</span><span>{{ $venue->cleaning_fee !== null ? 'Rp '.number_format($venue->cleaning_fee, 0, ',', '.') : 'Belum ditentukan' }}</span></li>
                    <li><span>+ Biaya layanan Pesta Kita (10%)</span><span>Rp {{ number_format($fee, 0, ',', '.') }}</span></li>
                    <li class="tot"><span>{{ $venue->cleaning_fee !== null ? 'Estimasi total' : 'Estimasi sementara' }}</span><span>Rp {{ number_format($total, 0, ',', '.') }}</span></li>
                </ul>
                <p class="fs-sm">Estimasi, belum termasuk fasilitas tambahan. Tagihan final ditetapkan sistem.</p>
            </div>
        </a>
        @empty
        <section class="card empty-state">
            <span class="ic i-search" aria-hidden="true"></span>
            <h2>Belum ada venue yang cocok</h2>
            <p>Tidak ada venue tayang yang cocok dengan pencarian atau filter ini. Coba ubah kata kunci, naikkan sewa maksimum, atau pilih jenis venue lain.</p>
            <a class="btn btn-o" href="{{ route('klien.katalog.venue') }}">Tampilkan semua venue</a>
        </section>
        @endforelse
    </div>
</main>
@endsection