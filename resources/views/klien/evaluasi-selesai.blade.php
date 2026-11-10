@extends('layouts.klien')

@section('title', 'Penilaian acara · Pesta Kita')

@section('content')
<main id="konten" class="content" tabindex="-1"><div class="page-head"><div><div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / <a href="{{ url('/klien/riwayat') }}">Riwayat &amp; evaluasi</a> / Penilaian</div><h1>Penilaian acara</h1><p class="sub">Ulang Tahun Bima ke-5 · Sabtu, 10 Okt 2026</p></div></div><div class="narrow"><div class="ok-box" style="margin-bottom:16px">Penilaian lengkap. Terima kasih!</div><ol class="eval"><li><div class="eh"><div><small>Venue</small><b>Aula Kenanga · Kenanga Hall</b></div><span class="ok-t">✓ 4/5</span></div></li><li><div class="eh"><div><small>Vendor</small><b>Dapur Nusantara</b></div><span class="ok-t">✓ 5/5</span></div></li><li><div class="eh"><div><small>Pesta Kita (Admin)</small><b>Pelayanan tim Pesta Kita</b></div><span class="ok-t">✓ 5/5</span></div></li><li><div class="eh"><div><small>Feedback sistem</small><b>Aplikasi Pesta Kita</b></div><span class="ok-t">✓ Selesai</span></div></li></ol><div style="margin-top:16px"><a class="btn btn-o" href="{{ url('/klien/riwayat') }}">Kembali</a></div></div></main>
@endsection
