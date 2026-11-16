@extends('layouts.admin')

@section('title', 'Profil · Panel Admin Pesta Kita')

@section('content')
<main class="content page-refined" id="konten" tabindex="-1">
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / Akun / Profil</div><h1>Profil</h1><p class="sub">Data akun dan keamanan masuk Anda.</p></div></div>
@if(session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
<div class="grid two">
<section aria-labelledby="p1" class="card"><div class="card-h"><h2 id="p1">Data akun</h2></div><div class="card-b"><form class="stack" method="POST" action="{{ url('/profil') }}">@csrf
<div class="field"><label for="nm">Nama</label><input autocomplete="name" class="input" id="nm" name="name" type="text" value="{{ old('name', auth()->user()->name) }}" @error('name') aria-invalid="true" @enderror/>@error('name')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
<div class="field"><label for="em">Email</label><input aria-describedby="em-h" class="input" id="em" readonly type="email" value="{{ auth()->user()->email }}"/><p class="hint" id="em-h">Email masuk hanya dapat diubah oleh pemilik sistem.</p></div>
<div class="field"><label for="pr">Peran</label><input class="input" id="pr" readonly type="text" value="{{ auth()->user()->role->label() }}"/></div>
<div><button class="btn" type="submit">Simpan perubahan</button></div></form></div></section>
<section aria-labelledby="p2" class="card"><div class="card-h"><h2 id="p2">Ubah kata sandi</h2></div><div class="card-b"><form class="stack" method="POST" action="{{ url('/profil/sandi') }}">@csrf
<div class="field"><label for="k0">Kata sandi saat ini <span aria-hidden="true" class="req">*</span></label><input autocomplete="current-password" class="input" id="k0" name="current_password" required type="password" @error('current_password') aria-invalid="true" @enderror/>@error('current_password')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
<div class="field"><label for="k1">Kata sandi baru <span aria-hidden="true" class="req">*</span></label><input aria-describedby="k1-h" autocomplete="new-password" class="input" id="k1" name="password" required type="password" @error('password') aria-invalid="true" @enderror/><p class="hint" id="k1-h">Minimal 8 karakter.</p>@error('password')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
<div class="field"><label for="k2">Ulangi kata sandi baru <span aria-hidden="true" class="req">*</span></label><input autocomplete="new-password" class="input" id="k2" name="password_confirmation" required type="password"/></div>
<div><button class="btn" type="submit">Ubah kata sandi</button></div></form></div></section>
</div>
<section aria-labelledby="p3" class="card mt-20"><div class="card-h"><h2 id="p3">Keluar</h2></div><div class="card-b"><p class="hint" style="margin-top:0">Akhiri sesi masuk Anda di perangkat ini.</p><form method="POST" action="{{ url('/logout') }}">@csrf<button class="btn" type="submit">Keluar dari akun</button></form></div></section>
</main>
@endsection
