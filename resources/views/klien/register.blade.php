@extends('layouts.guest')

@section('title', 'Daftar · Pesta Kita')

@section('content')
<main id="konten" class="guest" tabindex="-1"><div class="box"><a class="brand" href="{{ url('/klien') }}"><span class="logo" aria-hidden="true"></span><span><b>Pesta Kita</b><small>Rencanakan pesta ulang tahun</small></span></a><div class="card"><h1>Buat akun baru</h1><div class="data-note" role="note"><span class="ic i-info" aria-hidden="true"></span><p>Formulir pendaftaran di halaman ini sudah tidak dipakai. Pendaftaran akun klien, vendor, dan pemilik venue memakai satu formulir resmi; pilih peran saat mendaftar. Akun penyedia aktif setelah diverifikasi Pesta Kita.</p></div><div class="form-actions mt-16"><a class="btn" href="{{ url('/register') }}">Buka formulir pendaftaran</a></div><p class="mt-16">Sudah punya akun? <a href="{{ url('/klien/login') }}"><b>Masuk</b></a></p></div></div></main>
@endsection