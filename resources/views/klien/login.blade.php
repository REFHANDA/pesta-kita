@extends('layouts.guest')

@section('title', 'Masuk · Pesta Kita')

@section('content')
<main id="konten" class="guest" tabindex="-1"><div class="box"><a class="brand" href="{{ url('/klien') }}"><span class="logo" aria-hidden="true"></span><span><b>Pesta Kita</b><small>Rencanakan pesta ulang tahun</small></span></a><div class="card"><h1>Masuk ke akun Anda</h1><form class="stack" onsubmit="return false" style="gap:16px"><div class="field"><label for="e">Email</label><input class="input" id="e" type="email" value="" autocomplete="email" required></div><div class="field"><label for="p">Kata sandi</label><input class="input" id="p" type="password" value="" autocomplete="current-password" required></div><label class="check" style="font-weight:500"><input type="checkbox"> Ingat saya di perangkat ini</label><button class="btn">Masuk</button></form><p style="margin:18px 0 0">Belum punya akun? <a href="{{ url('/register') }}"><b>Daftar</b></a></p></div></div></main>
@endsection
