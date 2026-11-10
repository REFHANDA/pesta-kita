@extends('layouts.admin')

@section('title', 'Monitoring rundown · Admin Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / Pemantauan / Monitoring rundown</div><h1>Monitoring rundown</h1><p class="sub">Pemantauan jadwal acara untuk membantu Admin melihat kesiapan koordinasi. Admin hanya memiliki akses lihat.</p></div></div>
<div class="alert"><span aria-hidden="true" class="ic i-alert"></span><span>Rundown dikelola oleh klien dan vendor terkait. Admin dan pemilik venue hanya melihat; bentrok waktu ditampilkan sebagai peringatan dan tidak memblokir penyimpanan.</span></div>
<section class="card"><div class="toolbar"><input class="input" placeholder="Cari ID acara / nama acara"/><select aria-label="Cari" class="input"><option>Semua status</option><option>Terkonfirmasi</option><option>Selesai</option></select><input aria-label="Tanggal" class="input" type="date" value="2026-11-12"/><button class="btn btn-o" type="button">Terapkan</button></div>
<div aria-label="Monitoring rundown. Gulir horizontal untuk melihat seluruh kolom" class="table-wrap" role="region" tabindex="0"><table><caption class="sr-only">Tabel: Monitoring rundown</caption><thead><tr><th scope="col">Waktu</th><th scope="col">Agenda</th><th scope="col">Penanggung jawab</th><th scope="col">Acara</th><th scope="col">Status</th><th scope="col">Catatan</th></tr></thead><tbody>
<tr><td>08.00–09.00</td><td><b>Persiapan venue</b></td><td>Grand Garden Hall</td><td>PK-028 · Pesta Kebun Raka</td><td><span class="badge b-ok">Terjadwal</span></td><td>-</td></tr>
<tr><td>09.00–10.00</td><td><b>Setup dekorasi</b></td><td>Kreasi Pesta</td><td>PK-028 · Pesta Kebun Raka</td><td><span class="badge b-ok">Terjadwal</span></td><td>-</td></tr>
<tr><td>10.00–10.30</td><td><b>Sound check</b></td><td>Sewa Audio Jaya</td><td>PK-028 · Pesta Kebun Raka</td><td><span class="badge b-warn">Potensi bentrok</span></td><td>Beririsan dengan persiapan hiburan</td></tr>
<tr><td>10.30–11.00</td><td><b>Kedatangan tamu</b></td><td>Klien</td><td>PK-028 · Pesta Kebun Raka</td><td><span class="badge b-ok">Terjadwal</span></td><td>-</td></tr>
</tbody></table></div><div class="pager"><span>4 item rundown</span><a href="{{ url('/admin/acara-detail') }}">Lihat detail acara PK-028 →</a></div></section>
</main>
@endsection
