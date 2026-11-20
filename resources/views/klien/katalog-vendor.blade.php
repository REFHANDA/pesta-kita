@extends('layouts.klien')

@section('title', 'Katalog vendor · Pesta Kita')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/katalog.css') }}">
@endpush

@section('content')
@php
$items = $items ?? collect();
$categories = $categories ?? collect();
$filters = $filters ?? [];
@endphp
<main id="konten" class="content" tabindex="-1"><div class="page-head"><div><div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / Vendor &amp; venue</div><h1>Katalog vendor</h1><p class="sub">Hanya layanan dari penyedia terverifikasi yang tampil. Kontak penyedia tidak ditampilkan; semua pesanan dan pembayaran diproses lewat Pesta Kita.</p></div></div>
<section class="card"><div class="tabs"><a href="{{ route('klien.katalog.vendor') }}" aria-current="page">Vendor</a><a href="{{ route('klien.katalog.venue') }}">Venue</a><a href="{{ route('klien.katalog.peralatan') }}">Peralatan</a></div>
<div class="pill-row pill-bar" role="group" aria-label="Filter kategori layanan"><a class="pill" href="{{ route('klien.katalog.vendor') }}" @if(empty($filters['category'])) aria-current="true" @endif>Semua</a>@foreach($categories as $category)<a class="pill" href="{{ route('klien.katalog.vendor', ['category' => $category]) }}" @if(($filters['category'] ?? null) === $category) aria-current="true" @endif>{{ $category }}</a>@endforeach</div>
<form class="toolbar" method="GET" action="{{ route('klien.katalog.vendor') }}">@if(! empty($filters['category']))<input type="hidden" name="category" value="{{ $filters['category'] }}">@endif<label class="sr-only" for="kat-cari">Cari nama layanan</label><input class="input grow-300" id="kat-cari" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama…" autocomplete="off"><label class="sr-only" for="kat-harga">Harga maksimum</label><input class="input" id="kat-harga" name="max_price" inputmode="numeric" value="{{ $filters['max_price'] ?? '' }}" placeholder="Harga maks (Rp)" autocomplete="off"><button type="submit" class="btn btn-o">Cari</button></form></section>
<div class="cards">
@forelse($items as $item)
<a class="card cat" href="{{ route('klien.katalog.vendor.detail', $item) }}"><div class="ph"><span class="ic i-users" aria-hidden="true"></span><span class="status status-ok"><span class="dot" aria-hidden="true"></span>Terverifikasi</span></div><div class="bd"><h2>{{ $item->name }}</h2><p>{{ $item->vendorProfile?->business_name }} · {{ $item->category }}</p>@if($item->vendorProfile?->service_area)<ul class="meta"><li><span class="ic i-home" aria-hidden="true"></span>{{ $item->vendorProfile->service_area }}</li></ul>@endif<p class="desc">{{ \Illuminate\Support\Str::limit($item->description ?? '', 120) }}</p><div class="price-row"><div class="price">Rp {{ number_format($item->catalog_price, 0, ',', '.') }} <small>per acara</small></div><span class="ptag ptag-fixed">Harga katalog</span></div><p class="fs-sm">Harga akhir ditetapkan sistem setelah pesanan disetujui Admin.</p></div></a>
@empty
<section class="card empty-state"><span class="ic i-search" aria-hidden="true"></span><h2>Belum ada layanan yang cocok</h2><p>Tidak ada layanan vendor terverifikasi yang cocok dengan pencarian atau filter ini. Coba ubah kata kunci, naikkan harga maksimum, atau pilih kategori lain.</p><a class="btn btn-o" href="{{ route('klien.katalog.vendor') }}">Tampilkan semua layanan</a></section>
@endforelse
</div></main>
@endsection