@extends('layouts.klien')

@section('title', ($item?->name ?? 'Detail layanan').' · Pesta Kita')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/katalog.css') }}">
@endpush

@section('content')
@php
$item = $item ?? null;
$vendor = $vendor ?? null;
$photos = $photos ?? collect();
$fee = $item ? intdiv($item->catalog_price * 10, 100) : 0;
@endphp
<main id="konten" class="content" tabindex="-1">
@if($item === null)
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / <a href="{{ route('klien.katalog.vendor') }}">Vendor &amp; venue</a> / Detail layanan</div><h1>Detail layanan</h1></div></div>
<section class="card empty-state"><span class="ic i-search" aria-hidden="true"></span><h2>Layanan tidak tersedia</h2><p>Pilih layanan dari katalog untuk melihat detailnya.</p><a class="btn btn-o" href="{{ route('klien.katalog.vendor') }}">Kembali ke katalog</a></section>
@else
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / <a href="{{ route('klien.katalog.vendor') }}">Vendor &amp; venue</a> / Detail layanan</div><h1>{{ $item->name }}</h1><p class="vendor-sub">{{ $vendor?->business_name }} · {{ $item->category }} <span class="badge b-ok">Terverifikasi</span></p></div><div class="actions"><a class="btn btn-o" href="{{ route('klien.penyedia.vendor', $vendor) }}">Lihat profil penyedia</a><a class="btn btn-o" href="{{ route('klien.katalog.vendor') }}">Kembali ke katalog</a></div></div>
<div class="grid two two-wide"><div class="stack">
<section class="card"><div class="card-b">
@if($photos->isNotEmpty())<div class="hero-ph"><img src="{{ route('media.show', $photos->first()) }}" alt="{{ $item->name }}" style="width:100%;height:100%;object-fit:cover"></div>@else<div class="hero-ph"><span class="ic i-users" aria-hidden="true"></span></div>@endif
<dl class="dl"><div><dt>Kategori</dt><dd>{{ $item->category }}</dd></div><div><dt>Penyedia</dt><dd>{{ $vendor?->business_name }}</dd></div>@if($vendor?->service_area)<div><dt>Area layanan</dt><dd>{{ $vendor->service_area }}</dd></div>@endif @if($item->max_per_day)<div><dt>Kuota harian</dt><dd>{{ $item->max_per_day }} acara/hari</dd></div>@endif</dl>
@if($item->description)<p class="lead mt-16">{{ $item->description }}</p>@endif
</div></section>
<section class="card"><div class="card-h"><h2>Kebijakan dan kebutuhan khusus</h2></div><div class="card-b">
@if($item->policies)<p class="lead">{{ $item->policies }}</p>@endif
<ul class="policy"><li><span class="ic i-clip" aria-hidden="true"></span><span>Pesanan dikonfirmasi hanya setelah disetujui Admin Pesta Kita.</span></li><li><span class="ic i-cal" aria-hidden="true"></span><span>Tanggal acara wajib dicek ketersediaannya sebelum pesanan disahkan.</span></li><li><span class="ic i-alert" aria-hidden="true"></span><span>Kebutuhan khusus disampaikan pada catatan permintaan.</span></li></ul>
</div></section>
<section class="card"><div class="card-h"><h2>Foto dan portofolio</h2></div><div class="card-b">
@if($photos->isNotEmpty())<div class="gallery-ph mb-16">@foreach($photos as $photo)<div class="tile"><img src="{{ route('media.show', $photo) }}" alt="{{ $photo->original_name ?? 'Foto '.$item->name }}" style="width:100%;height:100%;object-fit:cover"></div>@endforeach</div>@else<p class="hint">Belum ada foto portofolio yang diunggah penyedia.</p>@endif
</div></section>
</div><aside class="stack">
<section class="card"><div class="card-h"><h2>Harga</h2></div><div class="card-b">
@if($item->pricing_mode === \App\Enums\PricingMode::PerluPenawaran)
<span class="ptag ptag-quote">Perlu penawaran</span><p class="lead mt-12">Harga disusun berdasarkan penawaran penyedia untuk kebutuhan acara Anda, lalu disahkan Admin.</p>
@else
<span class="ptag ptag-fixed">Harga katalog</span><div class="price-xl mt-12">Rp {{ number_format($item->catalog_price + $fee, 0, ',', '.') }} <small>estimasi total / acara</small></div>
<table class="break mt-12"><tr><td>Harga katalog</td><td>Rp {{ number_format($item->catalog_price, 0, ',', '.') }}</td></tr><tr><td>Biaya layanan Pesta Kita (10%)</td><td>Rp {{ number_format($fee, 0, ',', '.') }}</td></tr><tr class="t"><td>Estimasi total Klien</td><td>Rp {{ number_format($item->catalog_price + $fee, 0, ',', '.') }}</td></tr></table>
@endif
<p class="hint">Estimasi; harga akhir ditetapkan sistem setelah pesanan disetujui Admin.</p>
</div></section>
@if($item->pricing_mode === \App\Enums\PricingMode::PerluPenawaran)
<section class="card"><div class="card-h"><h2>Ajukan permintaan penawaran</h2></div><div class="card-b"><p class="hint">Pilih kebutuhan acara dan kirim permintaan kepada penyedia melalui Pesta Kita.</p><a class="btn" href="{{ route('klien.penawaran', ['subject' => 'catalog_item:'.$item->id]) }}">Ajukan penawaran untuk layanan ini</a></div></section>
@else
<section class="card"><div class="card-h"><h2>Pemesanan layanan</h2></div><div class="card-b"><p class="hint">Layanan ini memakai harga katalog tetap. Alur pemesanan tersedia pada tahap backend berikutnya.</p></div></section>
@endif
<section class="card"><div class="card-h"><h2>Status penyedia</h2></div><div class="card-b"><p class="mb-8"><span class="badge b-ok">Terverifikasi</span></p><p class="hint">Status verifikasi mengikuti keputusan Admin Pesta Kita. Kontak pribadi penyedia tidak ditampilkan.</p></div></section>
</aside></div>
@endif
</main>
@endsection