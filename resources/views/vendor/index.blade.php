@extends('layouts.vendor')

@section('title', 'Beranda Vendor · Pesta Kita')

@section('content')
@php
$profile = $profile ?? null;
$items = $items ?? collect();
$layananAktifCount = $layananAktifCount ?? 0;
$peralatanAktifCount = $peralatanAktifCount ?? 0;
$status = $profile?->verification_status;
@endphp
<main id="konten" class="content" tabindex="-1"><div class="page-head"><div><div class="crumbs"><a href="{{ url('/vendor') }}">Beranda</a></div><h1>Halo, {{ $profile->business_name ?? auth()->user()->name }}</h1><p class="sub">Anda bermitra dengan Pesta Kita. Pembayaran dan komunikasi dengan klien berjalan lewat perusahaan.</p></div><a class="btn" href="{{ url('/vendor/layanan') }}"><span class="ic i-box" aria-hidden="true"></span>Kelola katalog</a></div>
@if($status === \App\Enums\VerificationStatus::Menunggu)<div class="alert warn"><span class="ic i-info" aria-hidden="true"></span><span>Verifikasi usaha Anda menunggu review Admin. Item katalog tampil di katalog Klien setelah penyedia terverifikasi. <a href="{{ url('/vendor/verifikasi') }}">Lihat status verifikasi</a></span></div>
@elseif($status === \App\Enums\VerificationStatus::Ditolak)<div class="alert warn"><span class="ic i-info" aria-hidden="true"></span><span>Verifikasi usaha ditolak{{ $profile->verification_note ? ': '.$profile->verification_note : '' }}. Perbaiki dokumen lalu ajukan ulang. <a href="{{ url('/vendor/verifikasi') }}">Perbaiki verifikasi</a></span></div>
@elseif($status === \App\Enums\VerificationStatus::Terverifikasi)<div class="alert"><span class="ic i-ok" aria-hidden="true"></span><span>Usaha Anda terverifikasi. Item berstatus aktif tampil di katalog Klien.</span></div>
@elseif($status === \App\Enums\VerificationStatus::Nonaktif)<div class="alert warn"><span class="ic i-info" aria-hidden="true"></span><span>Profil usaha dinonaktifkan. Katalog tidak tampil; hubungi Pesta Kita untuk informasi lebih lanjut.</span></div>
@endif
<div class="grid stats"><div class="card stat"><span class="ic-box"><span class="ic i-tag" aria-hidden="true"></span></span><div><div class="lbl">Layanan aktif</div><div class="val">{{ $layananAktifCount }}</div></div></div><div class="card stat"><span class="ic-box"><span class="ic i-box" aria-hidden="true"></span></span><div><div class="lbl">Peralatan aktif</div><div class="val">{{ $peralatanAktifCount }}</div></div></div><div class="card stat"><span class="ic-box"><span class="ic i-clip" aria-hidden="true"></span></span><div><div class="lbl">Total item katalog</div><div class="val">{{ $items->count() }}</div></div></div><div class="card stat"><span class="ic-box"><span class="ic i-ok" aria-hidden="true"></span></span><div><div class="lbl">Status verifikasi</div><div class="val">@if($status === \App\Enums\VerificationStatus::Terverifikasi)<span class="badge b-ok">Terverifikasi</span>@elseif($status === \App\Enums\VerificationStatus::Menunggu)<span class="badge b-warn">Menunggu</span>@elseif($status === \App\Enums\VerificationStatus::Ditolak)<span class="badge b-warn">Ditolak</span>@else<span class="badge b-mute">Nonaktif</span>@endif</div></div></div></div>
<div class="data-note" role="note"><span class="ic i-info" aria-hidden="true"></span><p><b>Beberapa fitur masih berupa prototipe.</b> Ringkasan katalog, verifikasi, pesanan, keberatan, pencairan, dan ulasan kini mengambil data dari server. Notifikasi dan fitur di luar alur tersebut belum tersedia.</p></div>
<section class="card"><div class="card-h"><h2>Katalog terbaru</h2><div class="actions"><a class="btn btn-o btn-sm" href="{{ route('vendor.katalog.create') }}">Tambah layanan</a><a class="btn btn-o btn-sm" href="{{ route('vendor.peralatan.create') }}">Tambah peralatan</a></div></div><div class="table-wrap" role="region" tabindex="0" aria-label="Katalog terbaru. Gulir horizontal untuk melihat seluruh kolom"><table class="t-wide"><caption class="sr-only">Tabel: Katalog terbaru</caption><thead><tr><th scope="col">Item</th><th scope="col">Jenis</th><th scope="col" class="num">Harga</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead><tbody>
@forelse($items->take(5) as $item)
<tr><td><b>{{ $item->name }}</b></td><td>{{ $item->kind === \App\Enums\CatalogKind::Layanan ? 'Layanan' : 'Peralatan' }}</td><td class="num">@if($item->pricing_mode === \App\Enums\PricingMode::PerluPenawaran)<span class="ptag ptag-quote">Perlu penawaran</span>@else Rp {{ number_format($item->catalog_price, 0, ',', '.') }} @endif</td><td>@if($item->status === \App\Enums\CatalogItemStatus::Aktif)<span class="badge b-ok">Aktif</span>@else<span class="badge b-mute">Nonaktif</span>@endif</td><td><a class="btn btn-o btn-sm" href="{{ $item->kind === \App\Enums\CatalogKind::Layanan ? route('vendor.katalog.edit', $item) : route('vendor.peralatan.edit', $item) }}">Ubah</a></td></tr>
@empty
<tr><td colspan="5">Belum ada item katalog. Tambahkan layanan atau peralatan pertama Anda; item tampil di katalog Klien setelah penyedia terverifikasi.</td></tr>
@endforelse
</tbody></table></div><p class="hint mt-12">Menampilkan {{ min(5, $items->count()) }} item terbaru. <a href="{{ url('/vendor/layanan') }}">Layanan saya</a> · <a href="{{ url('/vendor/peralatan') }}">Peralatan saya</a></p></section></main>
@endsection