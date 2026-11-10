@extends('layouts.admin')

@section('title', 'Pembatalan &amp; refund · Admin Pesta Kita')

@section('content')
<main class="content page-refined" id="konten" tabindex="-1">
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / Pembatalan &amp; refund</div><h1>Pembatalan &amp; refund</h1></div></div>
<section class="card workflow-card"><div aria-label="Navigasi status" class="tabs" role="navigation"><a aria-current="page" href="#">Pengajuan terbuka <span class="n">2</span></a><a href="#">Refund diproses</a><a href="#">Selesai / ditolak</a><a href="#">Semua</a></div><div class="alert">Sebelum pembayaran divalidasi, pembatalan otomatis tanpa biaya. Setelah lunas, pembatalan acara diproses Admin dengan refund berjenjang.</div>
<div class="item urgent workflow-item"><div class="item-h"><div><h2>Acara #PK-026 · Garden Party Dimas</h2><p class="meta">Acara 20 Nov 2026 · diajukan klien 03 Nov 2026</p></div><span class="badge b-warn">Diajukan</span></div><p>Alasan: perubahan kondisi keluarga. Sistem menghitung refund awal berdasarkan sisa 17 hari.</p><div class="detail-list"><div><span>Persentase refund awal</span><b>70%</b></div><div><span>Grand total dibayar</span><b>Rp 14.200.000</b></div><div><span>Estimasi refund</span><b>Rp 9.940.000 + kode unik</b></div><div><span>Estimasi kompensasi penyedia</span><b>Rp 3.220.000</b></div></div><div class="toolbar"><a class="btn btn-o" href="{{ url('/admin/pembatalan-detail') }}">Tinjau pengajuan</a></div></div>
<div class="item workflow-item"><div class="item-h"><div><h3>Acara #PK-024 · Ulang Tahun Naya</h3><p class="meta">Acara 15 Nov 2026 · diajukan klien 02 Nov 2026</p></div><span class="badge b-warn">Diajukan</span></div><p>Alasan: perubahan lokasi acara.</p><div class="toolbar"><a class="btn btn-o" href="{{ url('/admin/pembatalan-detail') }}">Tinjau pengajuan</a></div></div></section>
</main>
@endsection
