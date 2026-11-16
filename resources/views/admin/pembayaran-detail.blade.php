@extends('layouts.admin')

@section('title', 'Validasi pembayaran · Admin Pesta Kita')

@section('content')
<main class="content page-refined" id="konten" tabindex="-1">
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / Tagihan &amp; pembayaran / Detail</div><h1>Validasi pembayaran</h1></div></div>
<div class="data-note" role="note"><span class="ic i-info" aria-hidden="true"></span><p><b>Halaman ini masih berupa prototipe frontend.</b> Ringkasan tagihan, bukti transfer, dan nominal di bawah adalah contoh statis. Validasi pembayaran hanya menampilkan pratinjau dan tidak mengubah status tagihan di server sampai backend transaksi tersedia.</p></div>
<div class="grid payment-layout">
<section class="card payment-summary refined-card">
<div class="card-h"><div><h2>Ringkasan tagihan</h2><p class="meta">PK/2026/11/0028 · Versi 1</p></div><span class="badge b-warn">Menunggu validasi</span></div>
<div class="card-b">
<div class="payment-total"><span>Total yang harus dibayar</span><strong>Rp 11.500.427</strong></div>
<div class="detail-section-title">Informasi acara</div>
<div class="detail-list payment-details"><div><span>Nama acara</span><b>Pesta Kebun Raka</b></div><div><span>Tanggal acara</span><b>12 November 2026</b></div></div>
<div class="detail-section-title">Rincian pembayaran</div>
<div class="detail-list payment-details"><div><span>Subtotal harga penyedia</span><b>Rp 10.000.000</b></div><div><span>Komisi platform (10%)</span><b>Rp 1.000.000</b></div><div><span>Biaya kebersihan</span><b>Rp 500.000</b></div><div><span>Kode unik transfer</span><b>Rp 427</b></div><div class="detail-total"><span>Total transfer</span><b>Rp 11.500.427</b></div></div>
</div>
</section>
<section class="card payment-proof-card refined-card">
<div class="card-h"><div><h2>Bukti pembayaran</h2><p class="meta">Periksa bukti sebelum melakukan validasi</p></div></div>
<div class="card-b">
<div class="proof"><span class="proof-icon">▧</span><b>Bukti transfer</b><small>transfer-raka.jpg</small></div>
<div class="detail-section-title">Informasi transfer</div>
<div class="detail-list payment-details"><div><span>Nama pengirim</span><b>Raka Pratama</b></div><div><span>Tanggal transfer</span><b>03 Nov 2026</b></div><div><span>Rekening tujuan</span><b>BCA ···· 1208</b></div><div><span>Jumlah diunggah</span><b>Rp 11.500.427</b></div></div>
<div class="alert"><span aria-hidden="true" class="ic i-info"></span><span>Pastikan nominal sesuai total tagihan beserta kode unik dan rekening tujuan tercatat dengan benar.</span></div>
<div class="payment-actions"><button class="btn" type="button" data-confirm="Konfirmasi pembayaran valid?" data-text="Pastikan jumlah transfer sama dengan total ditambah kode unik. Tagihan menjadi lunas, pesanan terkonfirmasi, dan nota lunas terbit." data-label="Konfirmasi valid" data-done="Pembayaran valid. Nota lunas diterbitkan dan dicatat di log aktivitas.">Konfirmasi valid</button><button class="btn btn-danger" type="button" data-confirm="Tolak bukti pembayaran?" data-text="Klien diminta mengunggah ulang bukti beserta catatan Anda." data-label="Tolak bukti" data-tone="bad" data-ask="Catatan untuk klien" data-done="Bukti ditolak. Klien diminta mengunggah ulang.">Tolak bukti</button></div>
</div>
</section>
</div>
</main>
@endsection
