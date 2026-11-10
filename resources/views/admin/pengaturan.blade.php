@extends('layouts.admin')

@section('title', 'Pengaturan sistem · Admin Pesta Kita')

@section('content')
<main class="content page-refined" id="konten" tabindex="-1">
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / Pemantauan / Pengaturan</div><h1>Pengaturan sistem</h1></div></div>
<div class="grid two settings-grid"><section class="card settings-card"><div class="settings-card-head"><h2>Parameter operasional</h2></div><label>Komisi platform (%)<input class="input" type="number" value="10"/></label><label>Batas pembayaran (jam)<input class="input" type="number" value="48"/></label><label>Masa keberatan penyedia (jam)<input class="input" type="number" value="24"/></label><label>SLA review Admin (jam)<input class="input" type="number" value="24"/></label><label><input checked="" type="checkbox"/> Aktifkan kode unik 3 digit</label><button class="btn">Simpan parameter</button></section>
<section class="card settings-card"><div class="settings-card-head"><h2>Tier refund</h2></div><p class="meta">Persentase refund dari grand total dibayar (kode unik dikembalikan penuh terpisah).</p><div aria-label="Tier refund. Gulir horizontal untuk melihat seluruh kolom" class="table-wrap" role="region" tabindex="0"><table><caption class="sr-only">Tabel: Tier refund</caption><thead><tr><th scope="col">Sisa hari ke acara</th><th scope="col">Refund</th></tr></thead><tbody><tr><td>30 hari atau lebih</td><td><b>90%</b></td></tr><tr><td>14–29 hari</td><td><b>70%</b></td></tr><tr><td>7–13 hari</td><td><b>50%</b></td></tr><tr><td>3–6 hari</td><td><b>25%</b></td></tr><tr><td>Kurang dari 3 hari</td><td><b>0%</b></td></tr></tbody></table></div></section>
<section class="card settings-card"><div class="settings-card-head"><h2>Alokasi budget rekomendasi</h2></div><div class="detail-list"><div><span>Venue</span><b>35%</b></div><div><span>Catering</span><b>30%</b></div><div><span>Dekorasi</span><b>15%</b></div><div><span>Hiburan</span><b>10%</b></div><div><span>Peralatan</span><b>5%</b></div><div><span>Cadangan</span><b>5%</b></div></div><button class="btn btn-o">Ubah alokasi</button></section>
<section class="card settings-card"><div class="settings-card-head"><h2>Aturan sistem</h2></div><ul class="clean-list"><li>Pembayaran penuh 100% di awal; tidak ada DP/cicilan.</li><li>Tagihan terbit setelah seluruh pesanan diputuskan dan masa keberatan selesai.</li><li>Harga lama berlaku sampai pengajuan harga disetujui.</li><li>Pencairan penyedia hanya setelah acara ditandai selesai.</li></ul></section></div>
</main>
@endsection
