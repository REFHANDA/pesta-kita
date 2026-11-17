@extends('layouts.klien')

@section('title', ($venue?->name ?? 'Detail venue').' · Pesta Kita')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/katalog.css') }}">
@endpush

@section('content')
@php
$venue = $venue ?? null;
$photos = $photos ?? collect();
$floorPlan = $floorPlan ?? null;
$profile = $venue?->venueProfile;
$cleaning = $venue?->cleaning_fee;
$fee = $venue ? intdiv($venue->rental_price * 10, 100) : 0;
$total = $venue ? $venue->rental_price + ($cleaning ?? 0) + $fee : 0;
@endphp
<main id="konten" class="content" tabindex="-1">
@if($venue === null)
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / <a href="{{ route('klien.katalog.venue') }}">Vendor &amp; venue</a> / Detail venue</div><h1>Detail venue</h1></div></div>
<section class="card empty-state"><span class="ic i-search" aria-hidden="true"></span><h2>Venue tidak tersedia</h2><p>Pilih venue dari katalog untuk melihat detailnya.</p><a class="btn btn-o" href="{{ route('klien.katalog.venue') }}">Kembali ke katalog</a></section>
@else
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / <a href="{{ route('klien.katalog.venue') }}">Vendor &amp; venue</a> / Detail venue</div><h1>{{ $venue->name }}</h1><p class="vendor-sub">{{ $profile?->business_name }} · {{ $venue->kind }} <span class="badge b-ok">Terverifikasi</span></p></div><div class="actions"><a class="btn btn-o" href="{{ route('klien.penyedia.venue', $profile) }}">Lihat profil penyedia</a><a class="btn btn-o" href="{{ route('klien.katalog.venue') }}">Kembali ke katalog</a></div></div>
<div class="grid two two-wide"><div class="stack">
<section class="card"><div class="card-b">
@if($photos->isNotEmpty())<div class="hero-ph"><img src="{{ route('media.show', $photos->first()) }}" alt="{{ $venue->name }}" style="width:100%;height:100%;object-fit:cover"></div>@else<div class="hero-ph"><span class="ic i-home" aria-hidden="true"></span></div>@endif
<dl class="dl"><div><dt>Jenis</dt><dd>{{ $venue->kind }}</dd></div><div><dt>Penyedia</dt><dd>{{ $profile?->business_name }}</dd></div><div><dt>Lokasi</dt><dd>{{ $venue->address }}</dd></div><div><dt>Kapasitas</dt><dd>{{ number_format($venue->capacity, 0, ',', '.') }} orang</dd></div>@if($venue->duration_hours)<div><dt>Durasi sewa</dt><dd>{{ $venue->duration_hours }} jam</dd></div>@endif</dl>
@if($venue->description)<p class="lead mt-16">{{ $venue->description }}</p>@endif
</div></section>
<section class="card"><div class="card-h"><h2>Rincian biaya</h2></div><div class="card-b">
<table class="break"><tr><td>Harga sewa venue</td><td>Rp {{ number_format($venue->rental_price, 0, ',', '.') }}</td></tr><tr><td>Biaya kebersihan</td><td>{{ $cleaning !== null ? 'Rp '.number_format($cleaning, 0, ',', '.') : 'Belum ditentukan' }}</td></tr><tr><td>Biaya layanan Pesta Kita (10%)</td><td>Rp {{ number_format($fee, 0, ',', '.') }}</td></tr><tr class="t"><td>Estimasi total</td><td>Rp {{ number_format($total, 0, ',', '.') }}</td></tr></table>
@if($venue->deposit_amount)<p class="hint mt-12">Deposit Rp {{ number_format($venue->deposit_amount, 0, ',', '.') }} dapat dikenakan sesuai ketentuan venue; pengembaliannya mengikuti kebijakan pembatalan.</p>@endif
<p class="hint">Ini estimasi, bukan tagihan final. Komisi layanan 10% ditambahkan ke tagihan Klien dan tidak dipotong dari harga venue. Total akhir ditetapkan sistem setelah pesanan disetujui Admin.</p>
</div></section>
@if($venue->facilities->isNotEmpty())
<section class="card"><div class="card-h"><h2>Fasilitas</h2></div><div class="card-b">
<ul class="policy">@foreach($venue->facilities as $facility)<li><span class="ic i-ok" aria-hidden="true"></span><span>{{ $facility->name }}</span></li>@endforeach</ul>
</div></section>
@endif
@if($venue->rooms->isNotEmpty())
<section class="card"><div class="card-h"><h2>Ruangan</h2></div><div class="card-b">
<ul class="policy">@foreach($venue->rooms as $room)<li><span class="ic i-home" aria-hidden="true"></span><span>{{ $room->name }} — kapasitas {{ number_format($room->capacity, 0, ',', '.') }} orang</span></li>@endforeach</ul>
<p class="hint">Ketersediaan dipilih per ruangan; kalender ketersediaan tersedia pada tahap berikutnya (BE-3b).</p>
</div></section>
@endif
@if($floorPlan)
<section class="card"><div class="card-h"><h2>Denah venue</h2></div><div class="card-b">
<p>Denah tersedia untuk membantu Anda meninjau tata ruang.</p><a class="btn btn-o" href="{{ route('media.show', $floorPlan) }}" target="_blank" rel="noopener">Lihat denah venue</a>
</div></section>
@endif
<section class="card"><div class="card-h"><h2>Aturan penggunaan ruangan</h2></div><div class="card-b">
@if($venue->rules)<p class="lead">{{ $venue->rules }}</p>@else<p class="hint">Penyedia belum menuliskan aturan penggunaan ruangan.</p>@endif
</div></section>
<section class="card"><div class="card-h"><h2>Kebijakan pembatalan</h2></div><div class="card-b">
@if($venue->cancellation_policy)<p class="lead">{{ $venue->cancellation_policy }}</p>@else<p class="hint">Informasi kebijakan pembatalan belum tersedia.</p>@endif
<p class="hint">Nominal refund ditetapkan backend sesuai ketentuan venue dan persetujuan sistem Pesta Kita, bukan oleh halaman ini.</p>
</div></section>
<section class="card"><div class="card-h"><h2>Foto dan portofolio</h2></div><div class="card-b">
@if($photos->isNotEmpty())<div class="gallery-ph mb-16">@foreach($photos as $photo)<div class="tile"><img src="{{ route('media.show', $photo) }}" alt="{{ $photo->original_name ?? 'Foto '.$venue->name }}" style="width:100%;height:100%;object-fit:cover"></div>@endforeach</div>@else<p class="hint">Belum ada foto venue yang diunggah penyedia.</p>@endif
</div></section>
</div><aside class="stack">
<section class="card"><div class="card-h"><h2>Estimasi biaya</h2></div><div class="card-b">
<span class="ptag ptag-fixed">Harga sewa</span><div class="price-xl mt-12">Rp {{ number_format($total, 0, ',', '.') }} <small>estimasi total / acara</small></div>
<table class="break mt-12"><tr><td>Harga sewa venue</td><td>Rp {{ number_format($venue->rental_price, 0, ',', '.') }}</td></tr><tr><td>Biaya kebersihan</td><td>{{ $cleaning !== null ? 'Rp '.number_format($cleaning, 0, ',', '.') : 'Belum ditentukan' }}</td></tr><tr><td>Biaya layanan Pesta Kita (10%)</td><td>Rp {{ number_format($fee, 0, ',', '.') }}</td></tr><tr class="t"><td>Estimasi total</td><td>Rp {{ number_format($total, 0, ',', '.') }}</td></tr></table>
<p class="hint">Estimasi, bukan tagihan final; total ditetapkan sistem. Alur pengajuan harga khusus menyusul pada tahap berikutnya (BE-3b).</p>
</div></section>
<section class="card"><div class="card-h"><h2>Ketersediaan</h2></div><div class="card-b">
<p class="mb-8"><span class="badge b-warn">Perlu konfirmasi</span></p>
<div class="warn-box mt-12">Ketersediaan venue perlu dikonfirmasi sebelum pesanan dapat diproses. Kalender ketersediaan akan tersedia pada tahap berikutnya (BE-3b).</div>
</div></section>
<section class="card"><div class="card-h"><h2>Ajukan permintaan penawaran</h2></div><div class="card-b">
@if($venue->rooms->isNotEmpty())
<p class="hint">Pilih ruangan yang ingin Anda tanyakan. Permintaan belum menjadi pemesanan dan ketersediaan tetap perlu dikonfirmasi.</p>
@foreach($venue->rooms as $room)
<a class="btn btn-o btn-block mt-12" href="{{ route('klien.penawaran', ['subject' => 'venue_room:'.$room->id]) }}">Ajukan untuk {{ $room->name }}</a>
@endforeach
@else
<p class="hint">Venue ini belum memiliki ruangan yang dapat dipilih untuk permintaan penawaran.</p>
@endif
</div></section>
<section class="card"><div class="card-h"><h2>Status penyedia</h2></div><div class="card-b"><p class="mb-8"><span class="badge b-ok">Terverifikasi</span></p><p class="hint">Status verifikasi mengikuti keputusan Admin Pesta Kita. Kontak pribadi penyedia tidak ditampilkan.</p></div></section>
</aside></div>
@endif
</main>
@endsection