@extends('layouts.guest')

@section('title', 'Masuk · Pesta Kita')

@section('content')
<main class="guest" id="konten" tabindex="-1"><div class="box"><a class="brand" href="{{ url('/login') }}"><span aria-hidden="true" class="logo"></span><span><b>Pesta Kita</b><small>Rencanakan pesta ulang tahun</small></span></a><div class="card"><h1>Masuk ke akun penyedia</h1><form class="stack" onsubmit="return false" style="gap:16px"><div class="field"><label for="e">Email</label><input autocomplete="email" class="input" id="e" required type="email"/></div><div class="field"><label for="p">Kata sandi</label><input autocomplete="current-password" class="input" id="p" required type="password"/></div><label class="check" style="font-weight:500"><input type="checkbox"/> Ingat saya di perangkat ini</label><button class="btn">Masuk</button></form><p style="margin:18px 0 0">Belum punya akun? <a href="{{ url('/penyedia/registrasi-penyedia') }}"><b>Daftar sebagai penyedia</b></a></p></div></div></main>
@endsection
