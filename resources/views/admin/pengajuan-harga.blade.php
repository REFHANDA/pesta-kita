@extends('layouts.admin')

@section('title', 'Pengajuan harga · Admin Pesta Kita')

@section('content')
<main class="content page-refined" id="konten" tabindex="-1">
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / Penyedia &amp; katalog / Pengajuan harga</div><h1>Pengajuan harga</h1></div></div>
<section class="card workflow-card"><div aria-label="Navigasi status" class="tabs" role="navigation"><a aria-current="page" href="#">Menunggu keputusan <span class="n">2</span></a><a href="#">Disetujui</a><a href="#">Ditolak</a><a href="#">Semua</a></div><div class="item workflow-item"><div class="item-h"><div><h2>Paket Catering Nusantara</h2><p class="meta">Catering Bu Sari · Layanan · diajukan 02 Nov 2026</p></div><span class="badge b-warn">Menunggu keputusan</span></div><div class="price-compare"><div><small>Harga aktif (tetap berlaku)</small><b>Rp 85.000/pax</b></div><span>→</span><div><small>Harga diajukan</small><b>Rp 95.000/pax</b></div></div><p>Alasan: kenaikan harga bahan baku dan biaya operasional.</p><div class="toolbar"><button class="btn">Setujui harga baru</button><button class="btn btn-danger">Tolak pengajuan</button><a class="btn btn-o" href="{{ url('/admin/pengajuan-harga-detail') }}">Lihat detail</a></div></div>
<div class="item workflow-item"><div class="item-h"><div><h3>Grand Garden Hall</h3><p class="meta">Venue Sejahtera · Venue · diajukan 01 Nov 2026</p></div><span class="badge b-warn">Menunggu keputusan</span></div><div class="price-compare"><div><small>Harga aktif</small><b>Rp 5.000.000</b></div><span>→</span><div><small>Harga diajukan</small><b>Rp 5.500.000</b></div></div><p>Alasan: penyesuaian fasilitas dan perawatan venue.</p><div class="toolbar"><button class="btn">Setujui harga baru</button><button class="btn btn-danger">Tolak pengajuan</button></div></div><div class="alert">Harga lama tetap tampil ke klien sampai Admin menyetujui pengajuan. Pesanan yang sudah disetujui tidak berubah.</div></section>
</main>
@endsection
