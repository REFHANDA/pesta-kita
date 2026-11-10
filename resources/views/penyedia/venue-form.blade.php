@extends('layouts.penyedia')

@section('title', 'Tambah / ubah venue · Panel Pemilik Venue Pesta Kita')

@section('content')
<main id="konten" class="content" tabindex="-1">
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/penyedia/venue') }}">Beranda</a> / <a href="{{ url('/penyedia/venue-data') }}">Data venue</a> / Form</div><h1>Tambah / ubah venue</h1><p class="sub">Kelola identitas, kapasitas, harga, fasilitas, dan foto venue dalam satu formulir.</p></div></div>

<form class="card form-card" onsubmit="return false">
  <div class="card-h"><div><h2>Informasi venue</h2><p class="sub">Lengkapi informasi utama yang akan dilihat calon klien.</p></div><span class="badge b-info">Draf perubahan</span></div>
  <div class="card-b">
    <div class="form-grid">
      <div class="field full"><label for="nama-venue">Nama venue <span class="req">*</span></label><input class="input" id="nama-venue" required value="Graha Melati Ballroom"><p class="hint">Gunakan nama yang dikenal oleh calon penyewa.</p></div>
      <div class="field"><label for="jenis">Jenis venue <span class="req">*</span></label><select class="input" id="jenis" required><option>Ballroom</option><option>Gedung serbaguna</option><option>Garden / outdoor</option><option>Restoran</option><option>Rumah / private venue</option><option>Lainnya</option></select></div>
      <div class="field"><label for="kapasitas">Kapasitas maksimal (orang) <span class="req">*</span></label><input class="input" id="kapasitas" type="number" min="1" value="250" required><p class="hint">Sesuaikan dengan tata letak acara yang paling umum.</p></div>
      <div class="field full"><label for="alamat">Alamat lengkap <span class="req">*</span></label><textarea class="input" id="alamat" rows="2" required>Jl. Soekarno-Hatta No. 21, Malang, Jawa Timur</textarea></div>
      <div class="field full"><label for="deskripsi">Deskripsi venue <span class="req">*</span></label><textarea class="input" id="deskripsi" rows="4" required>Ballroom dengan area penerima tamu, ruang utama berpendingin udara, dan akses parkir yang luas. Cocok untuk pernikahan dan acara keluarga.</textarea><p class="hint">Jelaskan suasana, keunggulan, dan jenis acara yang sesuai.</p></div>
    </div>
  </div>
  <div class="card-h"><div><h2>Harga dan fasilitas</h2><p class="sub">Harga awal ditampilkan setelah disetujui Admin.</p></div></div>
  <div class="card-b">
    <div class="form-grid">
      <div class="field"><label for="harga">Harga sewa venue (Rp) <span class="req">*</span></label><input class="input" id="harga" type="number" min="0" value="7500000" required></div>
      <div class="field"><label for="kebersihan">Biaya kebersihan (Rp)</label><input class="input" id="kebersihan" type="number" min="0" value="350000"></div>
      <div class="field"><label for="durasi">Durasi sewa (jam)</label><input class="input" id="durasi" type="number" min="1" value="8"><p class="hint">Cantumkan durasi yang termasuk dalam harga.</p></div>
      <div class="field"><label for="deposit">Deposit (Rp)</label><input class="input" id="deposit" type="number" min="0" value="1000000"></div>
      <div class="field full"><label>Fasilitas tersedia</label><div class="check-grid"><label class="check"><input type="checkbox" checked> Area parkir</label><label class="check"><input type="checkbox" checked> AC</label><label class="check"><input type="checkbox" checked> Toilet</label><label class="check"><input type="checkbox"> Ruang rias</label><label class="check"><input type="checkbox" checked> Sound system</label><label class="check"><input type="checkbox"> Area outdoor</label></div></div>
      <div class="field full"><label for="catatan">Ketentuan tambahan</label><textarea class="input" id="catatan" rows="3" placeholder="larangan confetti, batas waktu acara, ketentuan dekorasi..."></textarea></div>
    </div>
  </div>
  <div class="card-h"><div><h2>Foto venue</h2><p class="sub">Unggah foto yang menggambarkan kondisi venue saat ini.</p></div></div>
  <div class="card-b">
    <div class="upload-box"><span class="ic i-image" aria-hidden="true"></span><div class="grow"><b>Tambahkan foto venue</b><small class="sub-t">Format JPG/PNG, maksimal 5 MB per foto. Pilih beberapa foto.</small></div><input type="file" accept="image/png,image/jpeg" multiple aria-label="Unggah foto venue"></div>
    <div class="photo-placeholder"><div><span class="ic i-image"></span><b>Foto utama</b><small>Belum ada foto</small></div><div><span class="ic i-image"></span><b>Foto area</b><small>Belum ada foto</small></div><div><span class="ic i-image"></span><b>Foto fasilitas</b><small>Belum ada foto</small></div></div>
    <label class="check" style="margin-top:20px"><input type="checkbox" checked> Aktif dan dapat menerima permintaan pemesanan</label>
    <p class="hint">Venue baru atau perubahan informasi penting akan ditinjau Admin sebelum ditampilkan.</p>
    <div class="form-actions"><button class="btn" type="submit">Simpan perubahan</button><a class="btn btn-o" href="{{ url('/penyedia/venue-data') }}">Batal</a></div>
  </div>
</form>

</main>
@endsection
