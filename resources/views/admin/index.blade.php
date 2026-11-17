@extends('layouts.admin')

@section('title', 'Beranda Admin · Admin Pesta Kita')

@section('content')
@php
$klienCount = $klienCount ?? 0;
$penyediaTerverifikasiCount = $penyediaTerverifikasiCount ?? 0;
$acaraCount = $acaraCount ?? 0;
$verifikasiMenungguCount = $verifikasiMenungguCount ?? 0;
$commissionRevenue = $commissionRevenue ?? 0;
$averageRating = $averageRating ?? null;
$reviewCount = $reviewCount ?? 0;
@endphp
<main class="content" id="konten" tabindex="-1">
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / Beranda</div><h1>Beranda Admin</h1><p class="sub">Ringkasan operasional dan antrean tindakan.</p></div><a class="btn" href="{{ route('admin.penyedia.index') }}"><span class="ic i-users" aria-hidden="true"></span>Tinjau verifikasi mitra</a></div>
<div class="data-note" role="note"><span class="ic i-info" aria-hidden="true"></span><p>Ringkasan pendapatan menampilkan komisi dari invoice lunas. Total transaksi dan pencairan penyedia dicatat terpisah.</p></div>
<div class="grid stats"><div class="card stat"><span class="ic-box"><span aria-hidden="true" class="ic i-users"></span></span><div><div class="lbl">Klien</div><div class="val">{{ $klienCount }}</div></div></div><div class="card stat"><span class="ic-box"><span aria-hidden="true" class="ic i-bank"></span></span><div><div class="lbl">Penyedia terverifikasi</div><div class="val">{{ $penyediaTerverifikasiCount }}</div></div></div><div class="card stat"><span class="ic-box"><span aria-hidden="true" class="ic i-cal"></span></span><div><div class="lbl">Acara</div><div class="val">{{ $acaraCount }}</div></div></div><div class="card stat"><span class="ic-box"><span aria-hidden="true" class="ic i-ok"></span></span><div><div class="lbl">Verifikasi menunggu</div><div class="val">{{ $verifikasiMenungguCount }}</div></div></div></div>
<div class="grid two"><section aria-labelledby="t1" class="card"><div class="card-h"><h2 id="t1">Perlu tindakan</h2><span class="chip">{{ $verifikasiMenungguCount }} menunggu</span></div>
<a class="row-link" href="{{ route('admin.penyedia.index') }}"><span class="ic-box"><span aria-hidden="true" class="ic i-users"></span></span><span class="grow">Penyedia menunggu verifikasi</span><span class="count {{ $verifikasiMenungguCount > 0 ? 'hot' : '' }}"><span class="sr-only">{{ $verifikasiMenungguCount }} item</span><span aria-hidden="true">{{ $verifikasiMenungguCount }}</span></span><span aria-hidden="true" class="ic i-chev chev"></span></a>
<div class="card-b"><div class="data-note" role="note"><span class="ic i-info" aria-hidden="true"></span><p>Alur pesanan, keberatan, pembayaran, permohonan refund, pencairan, dan pemantauan acara tersedia pada halaman operasional terkait.</p></div></div></section>
<div class="stack"><section aria-labelledby="t2" class="card card-b"><h2 class="fs-16" id="t2">Pendapatan komisi</h2><p class="meta">Komisi dari invoice berstatus lunas</p><div class="val mt-12">Rp {{ number_format($commissionRevenue, 0, ',', '.') }}</div><p class="hint mt-12">Nilai ini terpisah dari total transaksi dan payout penyedia.</p></section>
<section aria-labelledby="t3" class="card card-b"><h2 class="fs-16" id="t3">Rating penyedia</h2><p class="meta">Agregat ulasan klien</p>@if($averageRating !== null)<div class="val mt-12">{{ number_format((float) $averageRating, 1, ',', '.') }} / 5</div><p class="hint mt-12">{{ number_format($reviewCount, 0, ',', '.') }} ulasan</p>@else<p class="hint mt-12">Belum ada ulasan.</p>@endif</section></div></div>
</main>
@endsection