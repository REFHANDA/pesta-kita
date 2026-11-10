@extends('layouts.penyedia')

@section('title', 'Registrasi penyedia · Pesta Kita')

@section('content')
<main id="konten">
<div class="auth-layout">
  <section class="auth-intro"><a class="brand" href="{{ url('/penyedia') }}"><span class="logo"></span><span><b>Pesta Kita</b><small>Portal penyedia</small></span></a><div><span class="eyebrow">Penyedia Pesta Kita</span><h2>Mulai hadir di setiap momen istimewa.</h2><p>Daftarkan usaha atau venue Anda untuk menerima peluang acara melalui Pesta Kita.</p><ul class="auth-benefits"><li>Kelola katalog dan ketersediaan</li><li>Pantau pesanan dan pencairan</li><li>Seluruh transaksi dikelola melalui Pesta Kita</li></ul></div><small>© 2026 Pesta Kita</small></section>
  <section class="auth-form">
    <div class="auth-heading"><span class="eyebrow">Pendaftaran penyedia · 01 dari 02</span><h1>Registrasi penyedia</h1><p class="sub">Isi data dasar usaha. Data akan digunakan untuk proses verifikasi akun.</p></div>
    <div class="stepper"><div class="step active"><b>1</b><span>Data usaha</span></div><div class="step"><b>2</b><span>Verifikasi</span></div></div>
    <form class="card" onsubmit="return false"><div class="card-b">
      <div class="form-grid">
        <div class="field full"><label for="role">Jenis penyedia <span class="req">*</span></label><select class="input" id="role" required><option>Vendor layanan acara</option><option selected>Pemilik venue</option><option>Vendor dan pemilik venue</option></select></div>
        <div class="field"><label for="usaha">Nama usaha / venue <span class="req">*</span></label><input class="input" id="usaha" required placeholder="Graha Melati"></div>
        <div class="field"><label for="legal">Bentuk usaha</label><select class="input" id="legal"><option>Perseorangan</option><option>CV</option><option>PT</option><option> lainnya</option></select></div>
        <div class="field"><label for="nama">Nama penanggung jawab <span class="req">*</span></label><input class="input" id="nama" required placeholder="Nama lengkap"></div>
        <div class="field"><label for="telp">Nomor WhatsApp <span class="req">*</span></label><input class="input" id="telp" type="tel" required placeholder="08xxxxxxxxxx"></div>
        <div class="field full"><label for="email">Email aktif <span class="req">*</span></label><input class="input" id="email" type="email" required placeholder="nama@email.com"></div>
        <div class="field"><label for="password">Kata sandi <span class="req">*</span></label><input class="input" id="password" type="password" required placeholder="Minimal 8 karakter"></div>
        <div class="field"><label for="password2">Ulangi kata sandi <span class="req">*</span></label><input class="input" id="password2" type="password" required placeholder="Ulangi kata sandi"></div>
        <div class="field full"><label for="alamat-usaha">Alamat usaha / venue</label><textarea class="input" id="alamat-usaha" rows="2" placeholder="Alamat lengkap, kota, provinsi"></textarea></div>
      </div>
      <label class="check" style="margin-top:18px"><input type="checkbox" required> Saya menyetujui syarat kemitraan dan kebijakan Pesta Kita.</label>
      <div class="form-actions"><button class="btn" type="submit">Buat akun &amp; lanjut verifikasi</button><a class="btn btn-o" href="{{ url('/penyedia') }}">Kembali</a></div>
      <p class="hint">Sudah memiliki akun? <a href="{{ url('/penyedia/verifikasi-penyedia') }}">Lihat status pendaftaran</a></p>
    </div></form>
  </section>
</div>
</main>
@endsection
