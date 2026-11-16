@extends('layouts.klien')

@section('title', 'Sewa peralatan · Pesta Kita')

@section('content')
@php
$items = $items ?? collect();
$categories = $categories ?? collect();
$filters = $filters ?? [];
@endphp
<main id="konten" class="content" tabindex="-1"><div class="page-head"><div><div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / Sewa peralatan</div><h1>Sewa peralatan</h1><p class="sub">Hanya peralatan dari penyedia terverifikasi yang tampil. Ketersediaan pada tanggal acara Anda diperiksa sebelum pesanan disahkan.</p></div></div>
<section class="card"><div class="tabs"><a href="{{ route('klien.katalog.vendor') }}">Vendor</a><a href="{{ route('klien.katalog.venue') }}">Venue</a><a href="{{ route('klien.katalog.peralatan') }}" aria-current="page">Peralatan</a></div>
<div class="pill-row pill-bar" role="group" aria-label="Filter kategori peralatan"><a class="pill" href="{{ route('klien.katalog.peralatan') }}" @if(empty($filters['category'])) aria-current="true" @endif>Semua</a>@foreach($categories as $category)<a class="pill" href="{{ route('klien.katalog.peralatan', ['category' => $category]) }}" @if(($filters['category'] ?? null) === $category) aria-current="true" @endif>{{ $category }}</a>@endforeach</div>
<form class="toolbar" method="GET" action="{{ route('klien.katalog.peralatan') }}">@if(! empty($filters['category']))<input type="hidden" name="category" value="{{ $filters['category'] }}">@endif<label class="sr-only" for="kat-cari">Cari nama peralatan</label><input class="input grow-300" id="kat-cari" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama…" autocomplete="off"><label class="sr-only" for="kat-harga">Harga maksimum</label><input class="input" id="kat-harga" name="max_price" inputmode="numeric" value="{{ $filters['max_price'] ?? '' }}" placeholder="Harga maks (Rp)" autocomplete="off"><button type="submit" class="btn btn-o">Cari</button></form></section>
<div class="cards">
@forelse($items as $item)
<a class="card cat" href="{{ route('klien.katalog.vendor.detail', $item) }}"><div class="ph"><span class="ic i-box" aria-hidden="true"></span></div><div class="bd"><h2>{{ $item->name }}</h2><p>{{ $item->vendorProfile?->business_name }} · {{ $item->category }}</p><p class="desc">{{ \Illuminate\Support\Str::limit($item->description ?? '', 100) }}</p><div class="price-row">@if($item->pricing_mode === \App\Enums\PricingMode::PerluPenawaran)<span class="ptag ptag-quote">Perlu penawaran</span>@else<div class="price">Rp {{ number_format($item->catalog_price, 0, ',', '.') }}</div><span class="ptag ptag-fixed">Harga katalog</span>@endif</div><p class="fs-sm">Harga akhir ditetapkan sistem setelah pesanan disetujui Admin.</p></div></a>
@empty
<section class="card empty-state"><span class="ic i-search" aria-hidden="true"></span><h2>Belum ada peralatan yang cocok</h2><p>Tidak ada peralatan terverifikasi yang cocok dengan pencarian atau filter ini. Coba ubah kata kunci atau pilih kategori lain.</p><a class="btn btn-o" href="{{ route('klien.katalog.peralatan') }}">Tampilkan semua peralatan</a></section>
@endforelse
</div></main>
@endsection