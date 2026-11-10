@extends('layouts.admin')

@section('title', 'Katalog penyedia · Admin Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / Penyedia &amp; katalog</div><h1>Katalog penyedia</h1></div><a class="btn btn-o" href="{{ url('/admin/admin-venue-form') }}">Tambah venue</a></div>
<section class="card"><div aria-label="Navigasi status" class="tabs" role="navigation"><a aria-current="page" href="#">Semua item</a><a href="#">Menunggu persetujuan <span class="n">6</span></a><a href="#">Aktif</a><a href="#">Nonaktif</a></div><form class="toolbar" onsubmit="return false"><input class="input" placeholder="Cari nama item / usaha"/><select aria-label="Cari" class="input"><option>Semua kategori</option><option>Venue</option><option>Layanan</option><option>Peralatan</option></select><select aria-label="Cari" class="input"><option>Semua status</option><option>Menunggu review</option><option>Aktif</option><option>Ditolak</option></select><button class="btn btn-o">Terapkan</button></form><div aria-label="Katalog penyedia. Gulir horizontal untuk melihat seluruh kolom" class="table-wrap" role="region" tabindex="0"><table><caption class="sr-only">Tabel: Katalog penyedia</caption><thead><tr><th scope="col">Item</th><th scope="col">Penyedia</th><th scope="col">Jenis</th><th scope="col">Harga aktif</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead><tbody>
<tr><td><b>Grand Garden Hall</b><small>Kapasitas 300 · Kediri</small></td><td>Venue Sejahtera</td><td>Venue</td><td>Rp 5.000.000</td><td><span class="badge b-warn">Menunggu review</span></td><td><button class="btn btn-sm">Tinjau</button></td></tr>
<tr><td><b>Paket Catering Nusantara</b><small>Minimal 100 pax</small></td><td>Catering Bu Sari</td><td>Layanan</td><td>Rp 85.000/pax</td><td><span class="badge b-ok">Aktif</span></td><td><button class="btn btn-sm btn-o">Detail</button></td></tr>
<tr><td><b>Speaker aktif 15 inch</b><small>Stok 8 unit</small></td><td>Sewa Audio Jaya</td><td>Peralatan</td><td>Rp 350.000/unit</td><td><span class="badge b-ok">Aktif</span></td><td><button class="btn btn-sm btn-o">Detail</button></td></tr>
</tbody></table></div></section>
</main>
@endsection
