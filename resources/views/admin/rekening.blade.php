@extends('layouts.admin')

@section('title', 'Rekening perusahaan · Admin Pesta Kita')

@section('content')
<main class="content page-refined" id="konten" tabindex="-1">
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / Penyedia &amp; katalog / Rekening perusahaan</div><h1>Rekening perusahaan</h1></div></div>
<div class="page-head account-toolbar"><div><p class="sub">Kelola rekening tujuan pembayaran klien. Hanya rekening aktif yang ditampilkan saat klien membayar.</p></div><button class="btn">＋ Tambah rekening</button></div><div class="grid two account-grid">
<section class="card account-card"><div class="card-h"><h2>BCA</h2><span class="badge b-ok">Aktif · Urutan 1</span></div><p class="big-num">•••• •••• 1208</p><p>PT Pesta Kita Indonesia</p><div class="toolbar"><button class="btn btn-o">Edit</button><button class="btn btn-danger">Nonaktifkan</button></div></section>
<section class="card account-card"><div class="card-h"><h2>Bank Mandiri</h2><span class="badge b-ok">Aktif · Urutan 2</span></div><p class="big-num">•••• •••• 4401</p><p>PT Pesta Kita Indonesia</p><div class="toolbar"><button class="btn btn-o">Edit</button><button class="btn btn-danger">Nonaktifkan</button></div></section>
<section class="card account-card"><div class="card-h"><h2>BRI</h2><span class="badge">Nonaktif</span></div><p class="big-num">•••• •••• 7710</p><p>PT Pesta Kita Indonesia</p><div class="toolbar"><button class="btn btn-o">Edit</button><button class="btn">Aktifkan</button></div></section></div><div class="alert">Rekening yang pernah dipakai transaksi sebaiknya dinonaktifkan, bukan dihapus, agar riwayat pembayaran tetap utuh.</div>
</main>
@endsection
