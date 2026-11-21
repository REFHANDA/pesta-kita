@extends('layouts.klien')

@section('title', ($item?->name ?? 'Detail layanan').' · Pesta Kita')
@section('wa_context', 'Saya ingin bertanya tentang '.$item->name.' dari '.($vendor?->business_name ?? 'penyedia').'.')

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
<span class="ptag ptag-fixed">Harga katalog</span><div class="price-xl mt-12">Rp {{ number_format($item->catalog_price + $fee, 0, ',', '.') }} <small>estimasi total / acara</small></div>
<table class="break mt-12"><tr><td>Harga katalog</td><td>Rp {{ number_format($item->catalog_price, 0, ',', '.') }}</td></tr><tr><td>Biaya layanan Pesta Kita (10%)</td><td>Rp {{ number_format($fee, 0, ',', '.') }}</td></tr><tr class="t"><td>Estimasi total Klien</td><td>Rp {{ number_format($item->catalog_price + $fee, 0, ',', '.') }}</td></tr></table>
<p class="hint">Estimasi; harga akhir ditetapkan sistem setelah pesanan disetujui Admin.</p>
</div></section>
<section class="card"><div class="card-h"><h2>Tambahkan ke acara</h2></div><div class="card-b">
@if(isset($draftEvents) && $draftEvents->isNotEmpty())
<form method="POST" action="{{ route('klien.acara.pesanan.store', $draftEvents->first()) }}" id="form-tambah-acara">
    @csrf
    <div class="field mb-12">
        <label for="event_select">Pilih Acara Draft</label>
        <select class="input" id="event_select" onchange="document.getElementById('form-tambah-acara').action = '/klien/acara/' + this.value + '/pesanan';">
            @foreach($draftEvents as $draftEvent)
            <option value="{{ $draftEvent->id }}">{{ $draftEvent->name }} ({{ $draftEvent->event_date?->format('d/m/Y') ?? 'Belum ada tgl' }})</option>
            @endforeach
        </select>
    </div>
    <input type="hidden" name="subject_type" value="catalog_item">
    <input type="hidden" name="subject_id" value="{{ $item->id }}">
    @if($item->kind->value === 'peralatan')
    <div class="field mb-12">
        <label for="qty">Jumlah Unit</label>
        <input class="input" id="qty" type="number" name="quantity" value="1" min="1" max="{{ $item->max_per_day ?? 999 }}">
        @if($item->max_per_day)<p class="hint">Maksimal stok harian: {{ $item->max_per_day }} unit.</p>@endif
    </div>
    @else
    <input type="hidden" name="quantity" value="1">
    @endif
    <button type="submit" class="btn full">Tambah ke Acara</button>
</form>
@else
<p class="hint">Anda belum memiliki konsep acara berstatus draft. Buat konsep acara terlebih dahulu untuk memesan item ini.</p>
<a class="btn btn-o full mt-8" href="{{ route('klien.acara.baru') }}">Buat Konsep Acara</a>
@endif
</div></section>
<section class="card"><div class="card-h"><h2>Status penyedia</h2></div><div class="card-b"><p class="mb-8"><span class="badge b-ok">Terverifikasi</span></p><p class="hint">Status verifikasi mengikuti keputusan Admin Pesta Kita. Kontak pribadi penyedia tidak ditampilkan.</p></div></section>
</aside></div>
@endif
</main>
@endsection