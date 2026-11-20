@extends('layouts.admin')

@section('title', 'Beranda Admin · Admin Pesta Kita')

@section('content')
@php
$klienCount = $klienCount ?? 0;
$penyediaTerverifikasiCount = $penyediaTerverifikasiCount ?? 0;
$acaraCount = $acaraCount ?? 0;
$verifikasiMenungguCount = $verifikasiMenungguCount ?? 0;
$pesananMenungguReviewCount = $pesananMenungguReviewCount ?? 0;
$keberatanTerbukaCount = $keberatanTerbukaCount ?? 0;
$pengajuanHargaMenungguCount = $pengajuanHargaMenungguCount ?? 0;
$tagihanMenungguValidasiCount = $tagihanMenungguValidasiCount ?? 0;
$perluTindakanTotal = $perluTindakanTotal ?? ($verifikasiMenungguCount + $pesananMenungguReviewCount + $keberatanTerbukaCount + $pengajuanHargaMenungguCount + $tagihanMenungguValidasiCount);
@endphp
<main class="content" id="konten" tabindex="-1">
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / Beranda</div><h1>Beranda Admin</h1><p class="sub">Ringkasan operasional dan antrean tindakan.</p></div><a class="btn" href="{{ route('admin.pesanan') }}"><span class="ic i-clip" aria-hidden="true"></span>Review pesanan</a></div>
<div class="grid stats"><div class="card stat"><span class="ic-box"><span aria-hidden="true" class="ic i-users"></span></span><div><div class="lbl">Klien</div><div class="val">{{ $klienCount }}</div></div></div><div class="card stat"><span class="ic-box"><span aria-hidden="true" class="ic i-bank"></span></span><div><div class="lbl">Penyedia terverifikasi</div><div class="val">{{ $penyediaTerverifikasiCount }}</div></div></div><div class="card stat"><span class="ic-box"><span aria-hidden="true" class="ic i-cal"></span></span><div><div class="lbl">Acara</div><div class="val">{{ $acaraCount }}</div></div></div><div class="card stat"><span class="ic-box"><span aria-hidden="true" class="ic i-clip"></span></span><div><div class="lbl">Pesanan menunggu review</div><div class="val">{{ $pesananMenungguReviewCount }}</div></div></div></div>
<div class="grid two"><section aria-labelledby="t1" class="card"><div class="card-h"><h2 id="t1">Perlu tindakan</h2><span class="chip">{{ $perluTindakanTotal }} menunggu</span></div>
<a class="row-link" href="{{ route('admin.pesanan') }}"><span class="ic-box"><span aria-hidden="true" class="ic i-clip"></span></span><span class="grow">Pesanan menunggu review</span><span class="count {{ $pesananMenungguReviewCount > 0 ? 'hot' : '' }}"><span class="sr-only">{{ $pesananMenungguReviewCount }} item</span><span aria-hidden="true">{{ $pesananMenungguReviewCount }}</span></span><span aria-hidden="true" class="ic i-chev chev"></span></a>
<a class="row-link" href="{{ route('admin.pesanan') }}"><span class="ic-box"><span aria-hidden="true" class="ic i-alert"></span></span><span class="grow">Keberatan penyedia terbuka</span><span class="count {{ $keberatanTerbukaCount > 0 ? 'hot' : '' }}"><span class="sr-only">{{ $keberatanTerbukaCount }} item</span><span aria-hidden="true">{{ $keberatanTerbukaCount }}</span></span><span aria-hidden="true" class="ic i-chev chev"></span></a>
<a class="row-link" href="{{ route('admin.penyedia.index') }}"><span class="ic-box"><span aria-hidden="true" class="ic i-users"></span></span><span class="grow">Penyedia menunggu verifikasi</span><span class="count {{ $verifikasiMenungguCount > 0 ? 'hot' : '' }}"><span class="sr-only">{{ $verifikasiMenungguCount }} item</span><span aria-hidden="true">{{ $verifikasiMenungguCount }}</span></span><span aria-hidden="true" class="ic i-chev chev"></span></a>
<a class="row-link" href="{{ route('admin.pengajuan-harga') }}"><span class="ic-box"><span aria-hidden="true" class="ic i-tag"></span></span><span class="grow">Pengajuan harga menunggu review</span><span class="count {{ $pengajuanHargaMenungguCount > 0 ? 'hot' : '' }}"><span class="sr-only">{{ $pengajuanHargaMenungguCount }} item</span><span aria-hidden="true">{{ $pengajuanHargaMenungguCount }}</span></span><span aria-hidden="true" class="ic i-chev chev"></span></a>
<a class="row-link" href="{{ url('/admin/pembayaran') }}"><span class="ic-box"><span aria-hidden="true" class="ic i-bill"></span></span><span class="grow">Pembayaran menunggu validasi</span><span class="count {{ $tagihanMenungguValidasiCount > 0 ? 'hot' : '' }}"><span class="sr-only">{{ $tagihanMenungguValidasiCount }} item</span><span aria-hidden="true">{{ $tagihanMenungguValidasiCount }}</span></span><span aria-hidden="true" class="ic i-chev chev"></span></a>
</section>
<div class="stack">
<section aria-labelledby="t2" class="card card-b">
    <h2 class="fs-16" id="t2">Pendapatan Perusahaan</h2>
    <p class="meta">Komisi (10%) + biaya kebersihan dari tagihan lunas</p>
    <div style="margin-top:12px;">
        <div style="font-size:24px;font-weight:800;color:#166534;">
            Rp {{ number_format($totalPendapatanPerusahaan ?? 0, 0, ',', '.') }}
        </div>
        <p class="hint" style="margin-top:4px;">
            Komisi platform: Rp {{ number_format($totalKomisi ?? 0, 0, ',', '.') }} · Kebersihan venue: Rp {{ number_format($totalKebersihan ?? 0, 0, ',', '.') }}
        </p>
    </div>
</section>
<section aria-labelledby="t3" class="card card-b"><h2 class="fs-16" id="t3">Rating Admin</h2><p class="meta">Penilaian dari klien</p><div class="data-note mt-12" role="note"><span class="ic i-info" aria-hidden="true"></span><p>Rating Admin tersedia setelah backend ulasan (BE-7) terhubung.</p></div></section></div></div>
</main>
@endsection