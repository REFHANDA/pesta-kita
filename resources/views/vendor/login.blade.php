@extends('layouts.guest')

@section('title', 'Masuk · Pesta Kita')

@section('content')
<main class="guest" id="konten" tabindex="-1"><div class="box"><a class="brand" href="{{ url('/vendor/login') }}"><span aria-hidden="true" class="logo"></span><span><b>Pesta Kita</b><small>Rencanakan pesta ulang tahun</small></span></a><div class="card"><h1>Masuk ke akun penyedia</h1><form class="stack gap-16" method="POST" action="{{ url('/login') }}">@csrf<div class="field"><label for="e">Email</label><input autocomplete="email" class="input" id="e" name="email" required type="email" value="{{ old('email') }}" @error('email') aria-invalid="true" @enderror/></div><div class="field"><label for="p">Kata sandi</label><input autocomplete="current-password" class="input" id="p" name="password" required type="password"/></div><label class="check fw-5"><input type="checkbox" name="remember" value="1"/> Ingat saya di perangkat ini</label><button class="btn" type="submit">Masuk</button>@error('email')<p class="field-error" role="alert">{{ $message }}</p>@enderror</form><p class="mt-16">Belum punya akun? <a href="{{ url('/register') }}"><b>Daftar sebagai penyedia</b></a></p></div></div></main>
@endsection
