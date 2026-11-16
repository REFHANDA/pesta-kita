@extends('layouts.guest')

@section('title', 'Registrasi penyedia · Pesta Kita')

@section('content')
<main id="konten">
<div class="auth-layout">
  <section class="auth-intro"><a class="brand" href="{{ url('/vendor') }}"><span class="logo"></span><span><b>Pesta Kita</b><small>Portal penyedia</small></span></a><div><span class="eyebrow">Penyedia Pesta Kita</span><p class="auth-title">Mulai hadir di setiap momen istimewa.</p><p>Daftarkan usaha atau venue Anda untuk menerima peluang acara melalui Pesta Kita.</p><ul class="auth-benefits"><li>Kelola katalog dan ketersediaan</li><li>Pantau pesanan dan pencairan</li><li>Seluruh transaksi dikelola melalui Pesta Kita</li></ul></div><small>© 2026 Pesta Kita</small></section>
  <section class="auth-form">
    <div class="auth-heading"><span class="eyebrow">Pendaftaran penyedia</span><h1>Registrasi penyedia</h1><p class="sub">Pendaftaran akun vendor dan pemilik venue memakai satu formulir pendaftaran resmi. Setelah akun dibuat, lanjutkan dengan unggah dokumen verifikasi usaha.</p></div>
    <div class="card"><div class="card-b">
      <div class="data-note" role="note"><span class="ic i-info" aria-hidden="true"></span><p>Formulir pendaftaran ganda di halaman ini sudah tidak dipakai. Satu akun hanya memiliki satu peran (vendor atau pemilik venue); pilih peran saat mendaftar di halaman resmi.</p></div>
      <div class="form-actions mt-16"><a class="btn" href="{{ url('/register') }}">Buat akun penyedia</a><a class="btn btn-o" href="{{ url('/vendor/verifikasi') }}">Lihat status verifikasi</a></div>
      <p class="hint mt-16">Sudah memiliki akun? <a href="{{ url('/vendor/login') }}"><b>Masuk ke portal vendor</b></a></p>
    </div></div>
  </section>
</div>
</main>
@endsection