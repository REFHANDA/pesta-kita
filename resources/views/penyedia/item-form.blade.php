@extends('layouts.penyedia')

@section('title', 'Ubah layanan · Panel Vendor Pesta Kita')

@section('content')
<main id="konten" class="content" tabindex="-1"><div class="page-head"><div><div class="crumbs"><a href="{{ url('/penyedia') }}">Beranda</a> / <a href="{{ url('/penyedia/layanan') }}">Layanan saya</a> / Ubah</div><h1>Ubah layanan</h1></div></div>
<form class="card" style="max-width:860px" onsubmit="return false"><div class="card-b"><div class="alert warn"><span class="ic i-info" aria-hidden="true"></span><span>Ada pengajuan harga Rp 2.900.000 yang menunggu persetujuan. Pengajuan baru bisa dibuat setelah diputuskan.</span></div>
<div class="form-grid"><div class="field full"><label for="nama">Nama layanan</label><input class="input " id="nama" type="text" value="Katering prasmanan" ></div>
<div class="field"><label for="kat">Kategori</label><select class="input" id="kat"><option>Katering</option><option>Dekorasi</option><option>Kue &amp; Dessert</option></select></div><div class="field "><label for="kuota">Maksimal acara per hari</label><input class="input " id="kuota" type="number" value="2" ><p class="hint">Berapa acara berbeda yang bisa Anda layani pada tanggal yang sama.</p></div>
<div class="field full"><label for="desk">Deskripsi</label><textarea class="input" id="desk" rows="3">Prasmanan 8 menu, termasuk peralatan saji dan 2 petugas.</textarea></div>
<div class="field "><label for="harga">Harga (Rp)</label><input class="input " id="harga" type="number" value="2720000" ><p class="hint">Harga lama tetap berlaku sampai perubahan disetujui Admin.</p></div><div class="field "><label for="alasan">Alasan perubahan harga</label><textarea class="input" id="alasan" rows="3"></textarea><p class="hint">Isi jika harga diubah.</p></div></div>
<label class="check" style="margin-top:20px"><input type="checkbox" checked> Aktif (menerima pesanan)</label>
<div class="form-actions"><button class="btn">Simpan</button><a class="btn btn-o" href="{{ url('/penyedia/layanan') }}">Batal</a></div></div></form></main>
@endsection
