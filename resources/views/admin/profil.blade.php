@extends('layouts.admin')

@section('title', 'Profil · Panel Admin Pesta Kita')

@section('content')
<main class="content page-refined" id="konten" tabindex="-1">
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / Akun / Profil</div><h1>Profil</h1><p class="sub">Data akun dan keamanan masuk Anda.</p></div></div>
<div class="grid two">
<section aria-labelledby="p1" class="card"><div class="card-h"><h2 id="p1">Data akun</h2></div><div class="card-b"><form class="stack" onsubmit="return false" style="gap:20px">
<div class="field"><label for="nm">Nama</label><input autocomplete="name" class="input" id="nm" type="text" value="Admin Pesta Kita"/></div>
<div class="field"><label for="em">Email</label><input aria-describedby="em-h" class="input" id="em" readonly type="email" value="admin@pestakita.test"/><p class="hint" id="em-h">Email masuk hanya dapat diubah oleh pemilik sistem.</p></div>
<div class="field"><label for="pr">Peran</label><input class="input" id="pr" readonly type="text" value="Admin"/></div>
<div><button class="btn">Simpan perubahan</button></div></form></div></section>
<section aria-labelledby="p2" class="card"><div class="card-h"><h2 id="p2">Ubah kata sandi</h2></div><div class="card-b"><form class="stack" onsubmit="return false" style="gap:20px">
<div class="field"><label for="k0">Kata sandi saat ini <span aria-hidden="true" class="req">*</span></label><input autocomplete="current-password" class="input" id="k0" required type="password"/></div>
<div class="field"><label for="k1">Kata sandi baru <span aria-hidden="true" class="req">*</span></label><input aria-describedby="k1-h" autocomplete="new-password" class="input" id="k1" required type="password"/><p class="hint" id="k1-h">Minimal 8 karakter.</p></div>
<div class="field"><label for="k2">Ulangi kata sandi baru <span aria-hidden="true" class="req">*</span></label><input autocomplete="new-password" class="input" id="k2" required type="password"/></div>
<div><button class="btn">Ubah kata sandi</button></div></form></div></section>
</div></main>
@endsection
