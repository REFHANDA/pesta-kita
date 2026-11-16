@extends('layouts.guest')

@section('title', 'Masuk · Pesta Kita')

@section('content')
<main id="konten" class="guest" tabindex="-1"><div class="box"><a class="brand" href="{{ url('/klien') }}"><span class="logo" aria-hidden="true"></span><span><b>Pesta Kita</b><small>Rencanakan pesta ulang tahun</small></span></a><div class="card"><h1>Masuk ke akun Anda</h1><form class="stack gap-16" method="POST" action="{{ url('/login') }}">@csrf<div class="field"><label for="e">Email</label><input class="input" id="e" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required @error('email') aria-invalid="true" @enderror></div><div class="field"><label for="p">Kata sandi</label><input class="input" id="p" name="password" type="password" value="" autocomplete="current-password" required></div><label class="check" style="font-weight:500"><input type="checkbox" name="remember" value="1"> Ingat saya di perangkat ini</label><button class="btn" type="submit">Masuk</button>@error('email')<p class="field-error" role="alert">{{ $message }}</p>@enderror</form><p class="mt-16">Belum punya akun? <a href="{{ url('/register') }}"><b>Daftar</b></a></p></div></div></main>
@endsection
