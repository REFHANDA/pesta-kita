@extends('layouts.admin')

@section('title', 'Log aktivitas · Admin Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / Pemantauan / Log aktivitas</div><h1>Log aktivitas</h1></div></div>
<section class="card"><div class="alert">Log audit mencatat tindakan penting Admin, termasuk persetujuan pesanan, validasi pembayaran, keputusan harga, refund, dan pencairan.</div><form class="toolbar" onsubmit="return false"><input class="input" aria-label="Cari aktor, objek, atau aksi" placeholder="Cari aktor / objek / aksi"/><select aria-label="Filter jenis aktivitas" class="input"><option>Semua aktivitas</option><option>Persetujuan pesanan</option><option>Pembayaran</option><option>Refund</option><option>Pengajuan harga</option><option>Pencairan</option></select><input aria-label="Filter tanggal aktivitas" class="input" type="date"/><button class="btn btn-o">Filter</button></form><div aria-label="Log aktivitas. Gulir horizontal untuk melihat seluruh kolom" class="table-wrap" role="region" tabindex="0"><table class="t-wide"><caption class="sr-only">Tabel: Log aktivitas</caption><thead><tr><th scope="col">Waktu</th><th scope="col">Aktor</th><th scope="col">Aksi</th><th scope="col">Objek</th><th scope="col">Detail</th></tr></thead><tbody>
<tr><td>03 Nov 2026<br/><small>10:42:18</small></td><td>Admin Utama</td><td><span class="badge b-ok">Pembayaran valid</span></td><td>PK/2026/11/0026</td><td>Nominal sesuai mutasi, rekening BRI<br/><a href="{{ url('/admin/aktivitas-detail') }}">Lihat detail</a></td></tr>
<tr><td>03 Nov 2026<br/><small>09:35:02</small></td><td>Admin Utama</td><td>Pesanan disetujui</td><td>PK-028 / #ORD-91</td><td>Venue dan 3 vendor disetujui</td></tr>
<tr><td>02 Nov 2026<br/><small>16:20:11</small></td><td>Admin Operasional</td><td>Pengajuan harga ditolak</td><td>#PH-018</td><td>Alasan: rincian perubahan belum lengkap</td></tr>
<tr><td>02 Nov 2026<br/><small>14:05:44</small></td><td>Admin Keuangan</td><td>Refund dicatat</td><td>PK-019</td><td>Bukti transfer refund tersimpan</td></tr>
</tbody></table></div></section>
</main>
@endsection
