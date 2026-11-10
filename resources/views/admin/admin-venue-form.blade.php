@extends('layouts.admin')

@section('title', 'Tambah / edit venue · Panel Admin Pesta Kita')

@section('content')
<main id="konten" class="content" tabindex="-1"><div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin/admin-penyedia') }}">Manajemen</a> / <a href="{{ url('/admin/admin-penyedia') }}">Venue</a> / Form venue</div><h1>Tambah / edit venue</h1><p class="sub">Kelola informasi venue yang akan ditampilkan pada katalog Pesta Kita.</p></div><span class="badge b-info">Mode edit · </span></div>
<div class="alert"><span class="ic i-info" aria-hidden="true"></span><span>Periksa kembali alamat, kapasitas, dan rincian biaya. Perubahan data venue akan tersimpan sebagai pembaruan katalog setelah Admin menyimpan.</span></div>
<form class="stack" onsubmit="return false">
<section class="card"><div class="card-h"><div><h2>Informasi venue</h2><p class="sub">Identitas dan lokasi tempat acara.</p></div><span class="badge b-ok">Terverifikasi</span></div><div class="card-b"><div class="form-grid">
<div class="field"><label for="nama">Nama venue *</label><input class="input" id="nama" value="Graha Melati Ballroom" required></div>
<div class="field"><label for="tipe">Jenis venue *</label><select class="input" id="tipe"><option>Ballroom</option><option>Gedung serbaguna</option><option>Outdoor / taman</option><option>Meeting room</option></select></div>
<div class="field full"><label for="alamat">Alamat lengkap *</label><input class="input" id="alamat" value="Jl. Soekarno-Hatta No. 21, Lowokwaru, Malang" required><p class="hint">Masukkan alamat yang dapat digunakan pelanggan untuk menemukan lokasi.</p></div>
<div class="field"><label for="kota">Kota / Kabupaten *</label><input class="input" id="kota" value="Kota Malang"></div>
<div class="field"><label for="area">Area / Kecamatan</label><input class="input" id="area" value="Lowokwaru"></div>
<div class="field full"><label for="deskripsi">Deskripsi venue</label><textarea class="input" id="deskripsi" rows="4">Ballroom untuk resepsi, seminar, dan acara keluarga dengan area parkir luas.</textarea></div>
</div></div></section>
<section class="card"><div class="card-h"><div><h2>Kapasitas & fasilitas</h2><p class="sub">Informasi teknis untuk membantu pelanggan memilih venue.</p></div></div><div class="card-b"><div class="form-grid">
<div class="field"><label for="kapasitas">Kapasitas maksimal (orang) *</label><input class="input" id="kapasitas" type="number" value="250" min="1"></div>
<div class="field"><label for="parkir">Kapasitas parkir (kendaraan)</label><input class="input" id="parkir" type="number" value="80" min="0"></div>
<div class="field full"><label>Fasilitas tersedia</label><div class="check-grid"><label class="check"><input type="checkbox" checked> Area parkir</label><label class="check"><input type="checkbox" checked> AC</label><label class="check"><input type="checkbox" checked> Mushola</label><label class="check"><input type="checkbox"> Ruang rias</label><label class="check"><input type="checkbox" checked> Sound system</label><label class="check"><input type="checkbox"> Area outdoor</label></div></div>
<div class="field full"><label for="foto">Foto venue</label><div class="upload-box"><span class="ic i-out" aria-hidden="true"></span><div><b>Unggah foto venue</b><p class="hint">JPG atau PNG · maksimal 5 MB per foto (simulasi unggah)</p></div><input id="foto" type="file" accept="image/png,image/jpeg" multiple></div></div>
</div></div></section>
<section class="card"><div class="card-h"><div><h2>Harga & status publikasi</h2><p class="sub">Rincian harga yang berlaku pada katalog.</p></div></div><div class="card-b"><div class="form-grid">
<div class="field"><label for="sewa">Harga sewa venue (Rp) *</label><input class="input" id="sewa" type="number" value="7500000" min="0"></div>
<div class="field"><label for="bersih">Biaya kebersihan (Rp)</label><input class="input" id="bersih" type="number" value="350000" min="0"></div>
<div class="field"><label for="status">Status venue</label><select class="input" id="status"><option>Aktif</option><option>Nonaktif</option><option>Menunggu persetujuan</option></select></div>
<div class="field"><label for="pemilik">Pemilik / penyedia</label><select class="input" id="pemilik"><option>Graha Melati Management</option><option>Belum ditetapkan</option></select></div>
</div><div class="note">Harga pada formulir ini merupakan harga yang dikelola Admin. Catat alasan perubahan pada log aktivitas saat fitur backend diimplementasikan.</div>
<div class="form-actions"><a class="btn btn-o" href="{{ url('/admin/admin-penyedia') }}">Batal</a><span class="spacer"></span><button class="btn" type="button">Simpan perubahan</button></div></div></section></form></main>
@endsection
