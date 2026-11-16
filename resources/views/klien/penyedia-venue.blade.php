@extends('layouts.klien')

@section('title', 'Profil '.($provider?->business_name ?? 'penyedia').' · Pesta Kita')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/katalog.css') }}">
@endpush

@section('content')
@php
$provider = $provider ?? null;
$venues = $venues ?? collect();
$photos = $photos ?? collect();
@endphp
<main id="konten" class="content" tabindex="-1">
@if($provider === null)
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / <a href="{{ route('klien.katalog.venue') }}">Vendor &amp; venue</a> / Profil penyedia</div><h1>Profil penyedia</h1></div></div>
<section class="card empty-state"><span class="ic i-search" aria-hidden="true"></span><h2>Penyedia tidak tersedia</h2><p>Pilih venue dari katalog untuk melihat profil penyedia pemiliknya.</p><a class="btn btn-o" href="{{ route('klien.katalog.venue') }}">Kembali ke katalog</a></section>
@else
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / <a href="{{ route('klien.katalog.venue') }}">Vendor &amp; venue</a> / Profil penyedia</div><h1>{{ $provider->business_name }}</h1><p class="vendor-sub">Pemilik venue · Profil penyedia <span class="badge b-ok">Terverifikasi</span></p></div><div class="actions"><a class="btn btn-o" href="{{ route('klien.katalog.venue') }}">Kembali ke katalog</a></div></div>
<div class="grid two two-wide"><div class="stack">
<section class="card"><div class="card-b">
@if($photos->isNotEmpty())<div class="hero-ph"><img src="{{ route('media.show', $photos->first()) }}" alt="{{ $provider->business_name }}" style="width:100%;height:100%;object-fit:cover"></div>@else<div class="hero-ph"><span class="ic i-home" aria-hidden="true"></span></div>@endif
<dl class="dl">@if($provider->category)<div><dt>Kategori</dt><dd>{{ $provider->category }}</dd></div>@endif @if($provider->service_area)<div><dt>Area layanan</dt><dd>{{ $provider->service_area }}</dd></div>@endif</dl>
@if($provider->description)<p class="lead mt-16">{{ $provider->description }}</p>@endif
</div></section>
<section class="card"><div class="card-h"><h2>Venue dari penyedia ini</h2></div><div class="card-b">
@if($venues->isNotEmpty())
<ul class="pkg-list">@foreach($venues as $venue)
<li class="pkg"><div class="pkg-h"><h3><a href="{{ route('klien.katalog.venue.detail', $venue) }}">{{ $venue->name }}</a></h3><span class="pr">Rp {{ number_format($venue->rental_price, 0, ',', '.') }}</span></div><ul><li>{{ $venue->kind }} — kapasitas {{ number_format($venue->capacity, 0, ',', '.') }} orang</li><li>{{ $venue->address }}</li>@if($venue->description)<li>{{ \Illuminate\Support\Str::limit($venue->description ?? '', 100) }}</li>@endif</ul></li>
@endforeach</ul>
<p class="hint mt-12">Harga sewa belum termasuk biaya kebersihan dan biaya layanan Pesta Kita; rincian estimasi ada di halaman detail venue.</p>
@else
<p class="hint">Penyedia ini belum memiliki venue yang tayang.</p>
@endif
</div></section>
<section class="card"><div class="card-h"><h2>Galeri foto penyedia</h2></div><div class="card-b">
@if($photos->isNotEmpty())<div class="gallery-ph mb-16">@foreach($photos as $photo)<div class="tile"><img src="{{ route('media.show', $photo) }}" alt="{{ $photo->original_name ?? 'Foto '.$provider->business_name }}" style="width:100%;height:100%;object-fit:cover"></div>@endforeach</div>@else<p class="hint">Belum ada foto yang diunggah penyedia. Foto tiap venue tersedia di halaman detail venue.</p>@endif
</div></section>
</div><aside class="stack">
<section class="card"><div class="card-h"><h2>Status verifikasi</h2></div><div class="card-b"><p class="mb-8"><span class="badge b-ok">Terverifikasi</span></p><p class="hint">Status verifikasi mengikuti keputusan Admin Pesta Kita; venue mengikuti status verifikasi profil penyedia.</p></div></section>
<section class="card"><div class="card-h"><h2>Kontak penyedia</h2></div><div class="card-b"><p class="hint">Kontak pribadi pemilik venue tidak ditampilkan. Seluruh komunikasi, pesanan, dan pembayaran diproses melalui Pesta Kita dalam satu tagihan gabungan.</p></div></section>
<section class="card"><div class="card-h"><h2>Ketersediaan</h2></div><div class="card-b"><p class="mb-8"><span class="badge b-warn">Perlu konfirmasi</span></p><div class="warn-box">Ketersediaan venue perlu dikonfirmasi sebelum pesanan dapat diproses. Kalender ketersediaan akan tersedia pada tahap berikutnya (BE-3b).</div></div></section>
<section class="card"><div class="card-h"><h2>Ajukan kebutuhan acara</h2></div><div class="card-b"><p class="hint">Pilih venue penyedia ini dari katalog; penambahan ke acara draft tersedia pada tahap berikutnya (BE-4/BE-5).</p><a class="btn" href="{{ route('klien.katalog.venue') }}">Lihat venue di katalog</a></div></section>
</aside></div>
@endif
</main>
@endsection