@extends('layouts.klien')

@section('title', 'Profil '.($vendor?->business_name ?? 'penyedia').' · Pesta Kita')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/katalog.css') }}">
@endpush

@section('content')
@php
$vendor = $vendor ?? null;
$items = $items ?? collect();
$photos = $photos ?? collect();
@endphp
<main id="konten" class="content" tabindex="-1">
@if($vendor === null)
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / <a href="{{ route('klien.katalog.vendor') }}">Vendor &amp; venue</a> / Profil penyedia</div><h1>Profil penyedia</h1></div></div>
<section class="card empty-state"><span class="ic i-search" aria-hidden="true"></span><h2>Penyedia tidak tersedia</h2><p>Pilih penyedia dari katalog untuk melihat profilnya.</p><a class="btn btn-o" href="{{ route('klien.katalog.vendor') }}">Kembali ke katalog</a></section>
@else
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / <a href="{{ route('klien.katalog.vendor') }}">Vendor &amp; venue</a> / Profil penyedia</div><h1>{{ $vendor->business_name }}</h1><p class="vendor-sub">{{ $vendor->category }} · Profil penyedia <span class="badge b-ok">Terverifikasi</span></p></div><div class="actions"><a class="btn btn-o" href="{{ route('klien.katalog.vendor') }}">Kembali ke katalog</a></div></div>
<div class="grid two two-wide"><div class="stack">
<section class="card"><div class="card-b">
@if($photos->isNotEmpty())<div class="hero-ph"><img src="{{ route('media.show', $photos->first()) }}" alt="{{ $vendor->business_name }}" style="width:100%;height:100%;object-fit:cover"></div>@else<div class="hero-ph"><span class="ic i-users" aria-hidden="true"></span></div>@endif
<dl class="dl"><div><dt>Kategori layanan</dt><dd>{{ $vendor->category }}</dd></div>@if($vendor->service_area)<div><dt>Area layanan</dt><dd>{{ $vendor->service_area }}</dd></div>@endif</dl>
@if($vendor->description)<p class="lead mt-16">{{ $vendor->description }}</p>@endif
</div></section>
<section class="card"><div class="card-h"><h2>Layanan dari penyedia ini</h2></div><div class="card-b">
@if($items->isNotEmpty())
<ul class="pkg-list">@foreach($items as $item)
<li class="pkg"><div class="pkg-h"><h3><a href="{{ route('klien.katalog.vendor.detail', $item) }}">{{ $item->name }}</a></h3><span class="pr">Rp {{ number_format($item->catalog_price, 0, ',', '.') }}</span></div><ul><li>{{ $item->category }}@if($item->description) — {{ \Illuminate\Support\Str::limit($item->description ?? '', 100) }}@endif</li></ul></li>
@endforeach</ul>
<p class="hint mt-12">Harga katalog belum termasuk biaya layanan Pesta Kita; rincian estimasi ada di halaman detail item.</p>
@else
<p class="hint">Penyedia ini belum memiliki item katalog yang tayang.</p>
@endif
</div></section>
<section class="card"><div class="card-h"><h2>Portofolio foto</h2></div><div class="card-b">
@if($photos->isNotEmpty())<div class="gallery-ph mb-16">@foreach($photos as $photo)<div class="tile"><img src="{{ route('media.show', $photo) }}" alt="{{ $photo->original_name ?? 'Foto '.$vendor->business_name }}" style="width:100%;height:100%;object-fit:cover"></div>@endforeach</div>@else<p class="hint">Belum ada foto portofolio yang diunggah penyedia.</p>@endif
</div></section>
</div><aside class="stack">
<section class="card"><div class="card-h"><h2>Status verifikasi</h2></div><div class="card-b"><p class="mb-8"><span class="badge b-ok">Terverifikasi</span></p><p class="hint">Status verifikasi mengikuti keputusan Admin Pesta Kita.</p></div></section>
<section class="card"><div class="card-h"><h2>Kontak penyedia</h2></div><div class="card-b"><p class="hint">Kontak pribadi penyedia tidak ditampilkan. Seluruh komunikasi, pesanan, dan pembayaran diproses melalui Pesta Kita dalam satu tagihan gabungan.</p></div></section>
<section class="card"><div class="card-h"><h2>Ajukan kebutuhan acara</h2></div><div class="card-b"><p class="hint">Pilih layanan penyedia ini dari katalog; penambahan ke acara draft tersedia pada tahap berikutnya (BE-4/BE-5).</p><a class="btn" href="{{ route('klien.katalog.vendor') }}">Lihat layanan di katalog</a></div></section>
</aside></div>
@endif
</main>
@endsection