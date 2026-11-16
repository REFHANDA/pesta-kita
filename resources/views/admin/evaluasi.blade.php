@extends('layouts.admin')

@section('title', 'Evaluasi &amp; rating · Admin Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / Pemantauan / Evaluasi &amp; rating</div><h1>Evaluasi &amp; rating</h1><p class="sub">Pantau penilaian venue, vendor, layanan Admin, dan feedback sistem setelah acara selesai.</p></div></div>
<div class="grid stats">
<div class="card stat"><span class="ic-box"><span class="ic i-users"></span></span><div><div class="lbl">Evaluasi masuk</div><div class="val">48</div></div></div>
<div class="card stat"><span class="ic-box"><span class="ic i-cal"></span></span><div><div class="lbl">Menunggu evaluasi</div><div class="val">7</div></div></div>
<div class="card stat"><span class="ic-box"><span class="ic i-ok"></span></span><div><div class="lbl">Rata-rata vendor</div><div class="val">4,7</div></div></div>
<div class="card stat"><span class="ic-box"><span class="ic i-log"></span></span><div><div class="lbl">Rata-rata venue</div><div class="val">4,8</div></div></div>
</div>
<div class="grid two">
<section class="card"><div class="card-h"><h2>Rekap rating penyedia</h2></div><div aria-label="Rekap rating penyedia. Gulir horizontal untuk melihat seluruh kolom" class="table-wrap" role="region" tabindex="0"><table class="t-wide"><caption class="sr-only">Tabel: Rekap rating penyedia</caption><thead><tr><th scope="col">Penyedia</th><th scope="col">Jenis</th><th scope="col">Jumlah ulasan</th><th scope="col">Rata-rata</th><th scope="col">Status</th></tr></thead><tbody>
<tr><td><b>Grand Garden Hall</b><small class="sub-t">Kediri</small></td><td>Venue</td><td>18</td><td><strong>4,9 / 5</strong></td><td><span class="badge b-ok">Aktif</span></td></tr>
<tr><td><b>Dapur Nusantara</b><small class="sub-t">Katering</small></td><td>Vendor</td><td>24</td><td><strong>4,8 / 5</strong></td><td><span class="badge b-ok">Aktif</span></td></tr>
<tr><td><b>Kreasi Pesta</b><small class="sub-t">Dekorasi</small></td><td>Vendor</td><td>12</td><td><strong>4,5 / 5</strong></td><td><span class="badge b-ok">Aktif</span></td></tr>
</tbody></table></div></section>
<section class="card"><div class="card-h"><h2>Alur evaluasi</h2></div><div class="card-b">
<div class="alert">Evaluasi hanya tersedia setelah Admin menandai acara selesai. Identitas klien tidak ditampilkan kepada penyedia.</div>
<ol><li>Venue</li><li>Setiap vendor yang terlibat</li><li>Admin (internal)</li><li>Feedback sistem (internal)</li></ol>
<p class="sub">Rating venue dan vendor menjadi rata-rata publik pada profil penyedia. Rating Admin dan feedback sistem hanya dapat dilihat internal.</p>
</div></section></div>
<section class="card mt-20"><div class="card-h"><h2>Riwayat evaluasi</h2><span class="badge b-info">Terbaru</span></div>
<div class="toolbar"><input class="input" aria-label="Cari acara atau penyedia" placeholder="Cari acara atau penyedia"/><select aria-label="Filter jenis target evaluasi" class="input"><option>Semua target</option><option>Venue</option><option>Vendor</option><option>Admin</option><option>Feedback sistem</option></select><select aria-label="Filter rating" class="input"><option>Semua rating</option><option>5 bintang</option><option>4 bintang ke bawah</option></select><button class="btn btn-o" type="button">Terapkan</button></div>
<div aria-label="Riwayat evaluasi. Gulir horizontal untuk melihat seluruh kolom" class="table-wrap" role="region" tabindex="0"><table class="t-wide"><caption class="sr-only">Tabel: Riwayat evaluasi</caption><thead><tr><th scope="col">Acara</th><th scope="col">Target</th><th scope="col">Rating</th><th scope="col">Komentar</th><th scope="col">Tanggal</th></tr></thead><tbody>
<tr><td><b>PK-026 · Garden Party Dimas</b></td><td>Grand Garden Hall</td><td><strong>5,0 / 5</strong></td><td>Venue bersih dan penataan rapi.</td><td>02 Nov 2026<br/><a href="{{ url('/admin/evaluasi-detail') }}">Lihat detail</a></td></tr>
<tr><td><b>PK-024 · Ulang Tahun Nadia</b></td><td>Dapur Nusantara</td><td><strong>4,8 / 5</strong></td><td>Makanan sesuai pesanan.</td><td>31 Okt 2026</td></tr>
<tr><td><b>PK-021 · Gathering Kantor</b></td><td>Admin Pesta Kita</td><td><strong>4,0 / 5</strong></td><td>Koordinasi cukup baik.</td><td>30 Okt 2026</td></tr>
</tbody></table></div></section>
</main>
@endsection
