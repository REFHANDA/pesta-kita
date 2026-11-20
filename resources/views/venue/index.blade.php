@extends('layouts.venue')

@section('title', 'Beranda Pemilik Venue · Pesta Kita')

@section('content')
@php
$profile = $profile ?? null;
$venues = $venues ?? collect();
$orders = $orders ?? collect();
$tayangCount = $tayangCount ?? 0;
$ruanganCount = $ruanganCount ?? 0;
$pesananMenungguCount = $pesananMenungguCount ?? 0;
$pesananAktifCount = $pesananAktifCount ?? 0;
$status = $profile?->verification_status;
@endphp
<main id="konten" class="content" tabindex="-1"><div class="page-head"><div><div class="crumbs"><a href="{{ url('/venue') }}">Beranda</a></div><h1>Halo, {{ $profile->business_name ?? auth()->user()->name }}</h1><p class="sub">Anda bermitra dengan Pesta Kita. Pembayaran dan komunikasi dengan klien berjalan lewat perusahaan.</p></div><a class="btn" href="{{ url('/venue/data') }}"><span class="ic i-box" aria-hidden="true"></span>Kelola data venue</a></div>
@if($status === \App\Enums\VerificationStatus::Menunggu)<div class="alert warn"><span class="ic i-info" aria-hidden="true"></span><span>Verifikasi usaha Anda menunggu review Admin. Venue tampil di katalog Klien setelah penyedia terverifikasi. <a href="{{ url('/venue/verifikasi') }}">Lihat status verifikasi</a></span></div>
@elseif($status === \App\Enums\VerificationStatus::Ditolak)<div class="alert warn"><span class="ic i-info" aria-hidden="true"></span><span>Verifikasi usaha ditolak{{ $profile->verification_note ? ': '.$profile->verification_note : '' }}. Perbaiki dokumen lalu ajukan ulang. <a href="{{ url('/venue/verifikasi') }}">Perbaiki verifikasi</a></span></div>
@elseif($status === \App\Enums\VerificationStatus::Terverifikasi)<div class="alert"><span class="ic i-ok" aria-hidden="true"></span><span>Usaha Anda terverifikasi. Venue berstatus terverifikasi tampil di katalog Klien.</span></div>
@elseif($status === \App\Enums\VerificationStatus::Nonaktif)<div class="alert warn"><span class="ic i-info" aria-hidden="true"></span><span>Profil usaha dinonaktifkan. Katalog tidak tampil; hubungi Pesta Kita untuk informasi lebih lanjut.</span></div>
@endif
<div class="grid stats"><div class="card stat"><span class="ic-box"><span class="ic i-ok" aria-hidden="true"></span></span><div><div class="lbl">Venue tayang</div><div class="val">{{ $tayangCount }}</div></div></div><div class="card stat"><span class="ic-box"><span class="ic i-bank" aria-hidden="true"></span></span><div><div class="lbl">Total ruangan</div><div class="val">{{ $ruanganCount }}</div></div></div><div class="card stat"><span class="ic-box"><span class="ic i-clip" aria-hidden="true"></span></span><div><div class="lbl">Pesanan menunggu</div><div class="val">{{ $pesananMenungguCount }}</div></div></div><div class="card stat"><span class="ic-box"><span class="ic i-users" aria-hidden="true"></span></span><div><div class="lbl">Pesanan aktif</div><div class="val">{{ $pesananAktifCount }}</div></div></div></div>

<div class="grid two gap-16">
<section class="card"><div class="card-h"><h2>Pesanan venue terbaru</h2><div class="actions"><a class="btn btn-o btn-sm" href="{{ route('venue.pesanan') }}">Lihat semua</a></div></div>
<div class="table-wrap" role="region" tabindex="0" aria-label="Pesanan venue terbaru. Gulir horizontal untuk melihat seluruh kolom">
<table class="t-wide"><caption class="sr-only">Tabel: Pesanan venue terbaru</caption>
<thead><tr><th scope="col">Acara</th><th scope="col">Ruangan / Venue</th><th scope="col">Tanggal</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
<tbody>
@forelse($orders->take(5) as $order)
@php
$badgeClass = match($order->status->value) {
    'menunggu_review' => 'badge b-warn',
    'disetujui', 'terkonfirmasi', 'selesai' => 'badge b-ok',
    'ditolak', 'dibatalkan' => 'badge b-bad',
    'dialihkan' => 'badge b-info',
    default => 'badge',
};
$dateStr = $order->usage_date ? $order->usage_date->format('d/m/Y') : ($order->event?->event_date?->format('d/m/Y') ?? '-');
@endphp
<tr>
<td><b>Acara #{{ $order->event_id }}</b></td>
<td>{{ $order->subject?->name ?? 'Venue' }}</td>
<td>{{ $dateStr }}</td>
<td><span class="{{ $badgeClass }}">{{ str_replace('_', ' ', ucfirst($order->status->value)) }}</span></td>
<td><a class="btn btn-o btn-sm" href="{{ route('venue.pesanan.detail', $order) }}">Lihat</a></td>
</tr>
@empty
<tr><td colspan="5">Belum ada pesanan venue masuk. Pesanan akan tampil setelah diajukan oleh klien.</td></tr>
@endforelse
</tbody></table></div>
</section>

<section class="card"><div class="card-h"><h2>Venue terbaru</h2><div class="actions"><a class="btn btn-o btn-sm" href="{{ route('venue.form.create') }}">Tambah venue</a></div></div><div class="table-wrap" role="region" tabindex="0" aria-label="Venue terbaru. Gulir horizontal untuk melihat seluruh kolom"><table class="t-wide"><caption class="sr-only">Tabel: Venue terbaru</caption><thead><tr><th scope="col">Venue</th><th scope="col">Jenis</th><th scope="col" class="num">Kapasitas</th><th scope="col" class="num">Ruangan</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead><tbody>
@forelse($venues->take(5) as $venue)
<tr><td><b>{{ $venue->name }}</b></td><td>{{ $venue->kind }}</td><td class="num">{{ number_format($venue->capacity, 0, ',', '.') }}</td><td class="num">{{ $venue->rooms_count }}</td><td>@if($venue->verification_status === \App\Enums\VerificationStatus::Terverifikasi)<span class="badge b-ok">Terverifikasi</span>@elseif($venue->verification_status === \App\Enums\VerificationStatus::Nonaktif)<span class="badge b-mute">Nonaktif</span>@else<span class="badge b-warn">Menunggu</span>@endif</td><td><a class="btn btn-o btn-sm" href="{{ route('venue.form.edit', $venue) }}">Ubah</a></td></tr>
@empty
<tr><td colspan="6">Belum ada venue. Tambahkan venue pertama Anda beserta ruangan dan fasilitas; venue tampil di katalog Klien setelah penyedia terverifikasi.</td></tr>
@endforelse
</tbody></table></div><p class="hint mt-12">Menampilkan {{ min(5, $venues->count()) }} venue terbaru. <a href="{{ url('/venue/data') }}">Lihat semua data venue</a></p></section>
</div>
</main>
@endsection