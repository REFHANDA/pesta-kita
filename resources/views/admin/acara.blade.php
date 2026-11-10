@extends('layouts.admin')

@section('title', 'Pantau acara · Admin Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / Pemantauan / Pantau acara</div><h1>Pantau acara</h1></div></div>
<section class="card"><div aria-label="Navigasi status" class="tabs" role="navigation"><a aria-current="page" href="#">Semua acara</a><a href="#">Diajukan</a><a href="#">Menunggu pembayaran</a><a href="#">Terkonfirmasi</a><a href="#">Siap selesai</a><a href="#">Selesai</a><a href="#">Dibatalkan</a></div><form class="toolbar" onsubmit="return false"><input class="input" placeholder="Cari ID / nama acara"/><select aria-label="Cari" class="input"><option>Semua status</option><option>Diajukan</option><option>Menunggu pembayaran</option><option>Terkonfirmasi</option><option>Selesai</option><option>Dibatalkan</option></select><input aria-label="Tanggal" class="input" type="date"/><button class="btn btn-o">Filter</button></form><div aria-label="Pantau acara. Gulir horizontal untuk melihat seluruh kolom" class="table-wrap" role="region" tabindex="0"><table><caption class="sr-only">Tabel: Pantau acara</caption><thead><tr><th scope="col">Acara</th><th scope="col">Tanggal</th><th scope="col">Tamu</th><th scope="col">Pesanan</th><th scope="col">Tagihan</th><th scope="col">Status acara</th><th scope="col">Aksi</th></tr></thead><tbody>
<tr><td><b>PK-028 · Pesta Kebun Raka</b><small>Klien: Raka P.</small></td><td>12 Nov 2026</td><td>120</td><td>4 / 4 diputuskan</td><td>Menunggu validasi</td><td><span class="badge b-warn">Menunggu pembayaran</span></td><td><a href="{{ url('/admin/acara-detail') }}">Pantau</a></td></tr>
<tr><td><b>PK-026 · Garden Party Dimas</b><small>Klien: Dimas A.</small></td><td>20 Nov 2026</td><td>150</td><td>5 / 5 disetujui</td><td>Lunas</td><td><span class="badge b-ok">Terkonfirmasi</span></td><td><a href="{{ url('/admin/acara-detail') }}">Pantau</a></td></tr>
<tr><td><b>PK-021 · Gathering Kantor</b><small>Klien: PT Maju</small></td><td>01 Nov 2026</td><td>90</td><td>3 / 3 selesai</td><td>Lunas</td><td><span class="badge b-ok">Siap ditandai selesai</span></td><td><a href="{{ url('/admin/acara-detail') }}">Pantau</a></td></tr>
</tbody></table></div></section>
</main>
@endsection
