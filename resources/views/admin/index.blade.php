@extends('layouts.admin')

@section('title', 'Beranda Admin · Admin Pesta Kita')

@section('content')
@php
$klienCount = $klienCount ?? 0;
$penyediaTerverifikasiCount = $penyediaTerverifikasiCount ?? 0;
$acaraCount = $acaraCount ?? 0;
$verifikasiMenungguCount = $verifikasiMenungguCount ?? 0;
@endphp
<main class="content" id="konten" tabindex="-1">
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / Beranda</div><h1>Beranda Admin</h1><p class="sub">Ringkasan operasional dan antrean tindakan.</p></div><a class="btn" href="{{ route('admin.penyedia.index') }}"><span class="ic i-users" aria-hidden="true"></span>Tinjau verifikasi mitra</a></div>
<div class="data-note" role="note"><span class="ic i-info" aria-hidden="true"></span><p><b>Sebagian ringkasan belum terhubung ke backend.</b> Kartu ringkasan dan antrean verifikasi mitra di halaman ini bersumber dari database. Antrean pesanan, pembayaran, keberatan, pengajuan harga, pembatalan, refund, pencairan, pendapatan, dan rating menyusul setelah backend terkait (BE-3b–BE-7) tersedia.</p></div>
<div class="grid stats"><div class="card stat"><span class="ic-box"><span aria-hidden="true" class="ic i-users"></span></span><div><div class="lbl">Klien</div><div class="val">{{ $klienCount }}</div></div></div><div class="card stat"><span class="ic-box"><span aria-hidden="true" class="ic i-bank"></span></span><div><div class="lbl">Penyedia terverifikasi</div><div class="val">{{ $penyediaTerverifikasiCount }}</div></div></div><div class="card stat"><span class="ic-box"><span aria-hidden="true" class="ic i-cal"></span></span><div><div class="lbl">Acara</div><div class="val">{{ $acaraCount }}</div></div></div><div class="card stat"><span class="ic-box"><span aria-hidden="true" class="ic i-ok"></span></span><div><div class="lbl">Verifikasi menunggu</div><div class="val">{{ $verifikasiMenungguCount }}</div></div></div></div>
<div class="grid two"><section aria-labelledby="t1" class="card"><div class="card-h"><h2 id="t1">Perlu tindakan</h2><span class="chip">{{ $verifikasiMenungguCount }} menunggu</span></div>
<a class="row-link" href="{{ route('admin.penyedia.index') }}"><span class="ic-box"><span aria-hidden="true" class="ic i-users"></span></span><span class="grow">Penyedia menunggu verifikasi</span><span class="count {{ $verifikasiMenungguCount > 0 ? 'hot' : '' }}"><span class="sr-only">{{ $verifikasiMenungguCount }} item</span><span aria-hidden="true">{{ $verifikasiMenungguCount }}</span></span><span aria-hidden="true" class="ic i-chev chev"></span></a>
<div class="card-b"><div class="data-note" role="note"><span class="ic i-info" aria-hidden="true"></span><p>Antrean lain belum tersedia di backend: review pesanan dan keberatan (BE-4/BE-5), validasi bukti pembayaran, pembatalan, refund, dan pencairan (BE-6), pengajuan harga (BE-3b), serta penandaan acara selesai (BE-5). Tidak ada angka contoh untuk antrean tersebut.</p></div></div></section>
<div class="stack"><section aria-labelledby="t2" class="card card-b"><h2 class="fs-16" id="t2">Pendapatan</h2><p class="meta">Komisi + biaya kebersihan</p><div class="data-note mt-12" role="note"><span class="ic i-info" aria-hidden="true"></span><p>Rekap pendapatan tersedia setelah backend tagihan dan pembayaran (BE-6) terhubung. Komisi 10% dibebankan ke klien, bukan dipotong dari pendapatan penyedia.</p></div></section>
<section aria-labelledby="t3" class="card card-b"><h2 class="fs-16" id="t3">Rating Admin</h2><p class="meta">Penilaian dari klien</p><div class="data-note mt-12" role="note"><span class="ic i-info" aria-hidden="true"></span><p>Rating Admin tersedia setelah backend ulasan (BE-7) terhubung.</p></div></section></div></div>
</main>
@endsection