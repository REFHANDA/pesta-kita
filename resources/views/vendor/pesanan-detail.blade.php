@extends('layouts.vendor')

@section('title', 'Katering prasmanan · Panel Vendor Pesta Kita')

@section('content')
<main id="konten" class="content" tabindex="-1"><div class="page-head"><div><div class="crumbs"><a href="{{ url('/vendor') }}">Beranda</a> / <a href="{{ url('/vendor/pesanan') }}">Pesanan</a> / PK-0412</div><h1>Katering prasmanan</h1></div></div>
<div class="grid two"><div class="stack">
<section class="card"><div class="card-h"><h2>Detail pesanan</h2><span class="badge b-ok">Disetujui</span></div><div class="card-b"><p class="deadline"><span class="chip">Sisa 14 jam</span> Masa keberatan berakhir <time datetime="2026-11-04T14:20+07:00">04/11/2026 14:20 WIB</time></p><p class="hint">Setelah masa keberatan berakhir dan tagihan lunas, pengajuan berubah menjadi keberatan darurat dengan bukti wajib.</p><dl class="dl"><div><dt>Acara</dt><dd>PK-0412</dd></div><div><dt>Tanggal</dt><dd>Sabtu, 14 November 2026</dd></div><div><dt>Jumlah tamu</dt><dd>45</dd></div><div><dt>Tema</dt><dd>Kebun dongeng</dd></div><div><dt>Layanan</dt><dd>Katering prasmanan</dd></div><div><dt>Hak Anda (harga penuh)</dt><dd>Rp 2.720.000</dd></div></dl>
<div class="note"><b>Catatan klien (diteruskan Pesta Kita):</b> Dua anak alergi kacang, mohon menu bebas kacang.</div></div></section>
<section class="card"><div class="card-h"><h2>Ajukan keberatan</h2></div><div class="card-b"><p class="sub" style="margin:0 0 16px">Masa keberatan sampai <b>04/11/2026 14:20 WIB</b>. Keberatan hanya catatan untuk Admin; status pesanan tidak berubah sampai Admin memutuskan.</p>
<form class="form-grid" onsubmit="return false"><div class="field"><label for="k">Kategori</label><select class="input" id="k"><option>Alat rusak</option><option>Jadwal bentrok</option><option>Kapasitas</option><option>Lainnya</option></select></div><div class="field "><label for="b">Bukti (opsional)</label><input class="input " id="b" type="file" value="" ></div><div class="field full"><label for="a">Alasan, jelaskan masalahnya</label><textarea class="input" id="a" rows="3"></textarea></div><div class="full"><button class="btn btn-bad">Kirim keberatan ke Admin</button></div></form></div></section></div>
<div class="stack"><section class="card"><div class="card-h"><h2>Pencairan</h2></div><div class="card-b"><p class="sub" style="margin:0">Dicairkan setelah acara ditandai selesai oleh Pesta Kita.</p><div class="big-num" style="margin-top:8px">Rp 2.720.000</div></div></section>
<section class="card"><div class="card-h"><h2>Butuh bantuan?</h2></div><div class="card-b"><p style="margin:0">Seluruh komunikasi dengan klien dilakukan lewat Pesta Kita.</p><p style="margin:8px 0 0;font-weight:700">0341-555-0123</p></div></section></div></div></main>
@endsection
