@extends('layouts.admin')

@section('title', 'Detail notifikasi admin · Admin Pesta Kita')

@section('content')
<main class="content detail-page page-refined" id="konten" tabindex="-1">
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / <a href="{{ url('/admin/notifikasi') }}">Notifikasi</a> / Detail</div><h1>Detail notifikasi</h1><p class="sub">Rincian pemberitahuan dan tindakan yang perlu ditindaklanjuti.</p></div><a class="btn btn-o" href="{{ url('/admin/notifikasi') }}">Kembali ke semua notifikasi</a></div>
<section class="card detail-card"><div class="card-h"><div><span class="badge b-warn">Prioritas tinggi</span><h2 style="margin-top:10px">Bukti transfer menunggu validasi</h2><p class="meta">Notifikasi #NTF-026 · 10 November 2026, 10:15 WIB</p></div><span class="badge b-info">Belum dibaca</span></div><div class="card-body"><div class="detail-kv"><p><b>Kategori</b><span>Pembayaran</span></p><p><b>Objek terkait</b><span>Tagihan PK/2026/11/0026</span></p><p><b>Nominal tagihan</b><span>Rp18.750.000</span></p><p><b>Waktu diterima</b><span>10 November 2026 · 10:15 WIB</span></p><p><b>Status notifikasi</b><span>Belum dibaca</span></p></div><div class="alert" style="margin-top:18px">Klien telah mengunggah bukti transfer. Admin perlu memeriksa nominal, rekening tujuan, dan bukti pembayaran sebelum mengonfirmasi tagihan.</div></div><div class="card-b actions"><a class="btn btn-p" href="{{ url('/admin/pembayaran-detail') }}">Buka validasi pembayaran</a><a class="btn btn-o" href="{{ url('/admin/notifikasi') }}">Kembali ke daftar notifikasi</a></div></section>
<section class="card detail-card"><div class="card-h"><h2>Riwayat notifikasi</h2></div><div class="card-body"><div class="timeline"><div class="timeline-item"><b>Bukti pembayaran diunggah klien</b><small>10 Nov 2026 · 10:12 WIB</small></div><div class="timeline-item"><b>Notifikasi dikirim ke Admin</b><small>10 Nov 2026 · 10:15 WIB</small></div><div class="timeline-item"><b>Menunggu validasi pembayaran</b><small>Status saat ini</small></div></div></div></section>
</main>
@endsection
