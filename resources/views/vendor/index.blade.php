@extends('layouts.vendor')

@section('title', 'Beranda Vendor · Pesta Kita')

@section('content')
@php
$profile = $profile ?? null;
$items = $items ?? collect();
$orders = $orders ?? collect();
$layananAktifCount = $layananAktifCount ?? 0;
$peralatanAktifCount = $peralatanAktifCount ?? 0;
$pesananMenungguCount = $pesananMenungguCount ?? 0;
$pesananAktifCount = $pesananAktifCount ?? 0;
$status = $profile?->verification_status;
@endphp
<main id="konten" class="content" tabindex="-1"><div class="page-head"><div><div class="crumbs"><a href="{{ url('/vendor') }}">Beranda</a></div><h1>Halo, {{ $profile->business_name ?? auth()->user()->name }}</h1><p class="sub">Anda bermitra dengan Pesta Kita. Pembayaran dan komunikasi dengan klien berjalan lewat perusahaan.</p></div><a class="btn" href="{{ url('/vendor/layanan') }}"><span class="ic i-box" aria-hidden="true"></span>Kelola katalog</a></div>
@if($status === \App\Enums\VerificationStatus::Menunggu)<div class="alert warn"><span class="ic i-info" aria-hidden="true"></span><span>Verifikasi usaha Anda menunggu review Admin. Item katalog tampil di katalog Klien setelah penyedia terverifikasi. <a href="{{ url('/vendor/verifikasi') }}">Lihat status verifikasi</a></span></div>
@elseif($status === \App\Enums\VerificationStatus::Ditolak)<div class="alert warn"><span class="ic i-info" aria-hidden="true"></span><span>Verifikasi usaha ditolak{{ $profile->verification_note ? ': '.$profile->verification_note : '' }}. Perbaiki dokumen lalu ajukan ulang. <a href="{{ url('/vendor/verifikasi') }}">Perbaiki verifikasi</a></span></div>
@elseif($status === \App\Enums\VerificationStatus::Terverifikasi)<div class="alert"><span class="ic i-ok" aria-hidden="true"></span><span>Usaha Anda terverifikasi. Item berstatus aktif tampil di katalog Klien.</span></div>
@elseif($status === \App\Enums\VerificationStatus::Nonaktif)<div class="alert warn"><span class="ic i-info" aria-hidden="true"></span><span>Profil usaha dinonaktifkan. Katalog tidak tampil; hubungi Pesta Kita untuk informasi lebih lanjut.</span></div>
@endif
@include('partials.rekening-pencairan-banner', ['portal' => 'vendor'])
<div class="grid stats"><div class="card stat"><span class="ic-box"><span class="ic i-tag" aria-hidden="true"></span></span><div><div class="lbl">Layanan aktif</div><div class="val">{{ $layananAktifCount }}</div></div></div><div class="card stat"><span class="ic-box"><span class="ic i-box" aria-hidden="true"></span></span><div><div class="lbl">Peralatan aktif</div><div class="val">{{ $peralatanAktifCount }}</div></div></div><div class="card stat"><span class="ic-box"><span class="ic i-clip" aria-hidden="true"></span></span><div><div class="lbl">Pesanan menunggu</div><div class="val">{{ $pesananMenungguCount }}</div></div></div><div class="card stat"><span class="ic-box"><span class="ic i-ok" aria-hidden="true"></span></span><div><div class="lbl">Pesanan aktif</div><div class="val">{{ $pesananAktifCount }}</div></div></div><a class="card stat link-plain" href="{{ route('vendor.pencairan') }}"><span class="ic-box"><span class="ic i-wallet" aria-hidden="true"></span></span><div><div class="lbl">Belum cair</div><div class="val">Rp {{ number_format($belumCairTotal ?? 0, 0, ',', '.') }}</div></div></a><div class="card stat"><span class="ic-box"><span class="ic i-ok" aria-hidden="true"></span></span><div><div class="lbl">Rating vendor</div><div class="val">@if(($rating['count'] ?? 0) > 0){{ number_format($rating['average'], 1, ',', '.') }} <small class="hint">/5 · {{ $rating['count'] }} penilaian</small>@else<span class="hint">Belum ada penilaian</span>@endif</div></div></div></div>

<section class="card"><div class="card-h"><h2>Penilaian terbaru</h2><p class="meta">Penilaian klien untuk usaha Anda. Nama klien tidak ditampilkan.</p></div><div class="card-b">
@if(($recentRatings ?? collect())->isEmpty())
<p class="hint" style="margin:0">Belum ada penilaian. Penilaian muncul setelah klien mengisi evaluasi acara.</p>
@else
<div class="table-wrap" role="region" tabindex="0" aria-label="Penilaian terbaru"><table class="t-wide"><caption class="sr-only">Tabel: Penilaian terbaru</caption><thead><tr><th scope="col">Acara</th><th scope="col">Rating</th><th scope="col">Komentar</th><th scope="col">Tanggal</th></tr></thead><tbody>
@foreach($recentRatings as $ratingItem)
<tr><td><b>Acara #{{ $ratingItem->evaluation?->event_id ?? '—' }}</b></td><td><span aria-hidden="true">{{ str_repeat('★', (int) $ratingItem->rating).str_repeat('☆', max(0, 5 - (int) $ratingItem->rating)) }}</span> <span class="hint">{{ $ratingItem->rating }}/5</span></td><td>{{ $ratingItem->comment ?: '—' }}</td><td>{{ $ratingItem->created_at?->translatedFormat('d M Y') }}</td></tr>
@endforeach
</tbody></table></div>
@endif
</div></section>

<div class="grid two gap-16">
<section class="card"><div class="card-h"><h2>Pesanan masuk terbaru</h2><div class="actions"><a class="btn btn-o btn-sm" href="{{ route('vendor.pesanan') }}">Lihat semua</a></div></div>
<div class="table-wrap" role="region" tabindex="0" aria-label="Pesanan masuk terbaru. Gulir horizontal untuk melihat seluruh kolom">
<table class="t-wide"><caption class="sr-only">Tabel: Pesanan masuk terbaru</caption>
<thead><tr><th scope="col">Acara</th><th scope="col">Item</th><th scope="col">Tanggal</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
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
<td>{{ $order->subject?->name ?? 'Layanan' }}</td>
<td>{{ $dateStr }}</td>
<td><span class="{{ $badgeClass }}">{{ str_replace('_', ' ', ucfirst($order->status->value)) }}</span></td>
<td><a class="btn btn-o btn-sm" href="{{ route('vendor.pesanan.detail', $order) }}">Lihat</a></td>
</tr>
@empty
<tr><td colspan="5">Belum ada pesanan masuk. Pesanan akan tampil setelah diajukan oleh klien.</td></tr>
@endforelse
</tbody></table></div>
</section>

<section class="card"><div class="card-h"><h2>Katalog terbaru</h2><div class="actions"><a class="btn btn-o btn-sm" href="{{ route('vendor.katalog.create') }}">Tambah layanan</a><a class="btn btn-o btn-sm" href="{{ route('vendor.peralatan.create') }}">Tambah peralatan</a></div></div><div class="table-wrap" role="region" tabindex="0" aria-label="Katalog terbaru. Gulir horizontal untuk melihat seluruh kolom"><table class="t-wide"><caption class="sr-only">Tabel: Katalog terbaru</caption><thead><tr><th scope="col">Item</th><th scope="col">Jenis</th><th scope="col" class="num">Harga</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead><tbody>
@forelse($items->take(5) as $item)
<tr><td><b>{{ $item->name }}</b></td><td>{{ $item->kind === \App\Enums\CatalogKind::Layanan ? 'Layanan' : 'Peralatan' }}</td><td class="num">Rp {{ number_format($item->catalog_price, 0, ',', '.') }}</td><td>@if($item->status === \App\Enums\CatalogItemStatus::Aktif)<span class="badge b-ok">Aktif</span>@else<span class="badge b-mute">Nonaktif</span>@endif</td><td><a class="btn btn-o btn-sm" href="{{ $item->kind === \App\Enums\CatalogKind::Layanan ? route('vendor.katalog.edit', $item) : route('vendor.peralatan.edit', $item) }}">Ubah</a></td></tr>
@empty
<tr><td colspan="5">Belum ada item katalog. Tambahkan layanan atau peralatan pertama Anda; item tampil di katalog Klien setelah penyedia terverifikasi.</td></tr>
@endforelse
</tbody></table></div><p class="hint mt-12">Menampilkan {{ min(5, $items->count()) }} item terbaru. <a href="{{ url('/vendor/layanan') }}">Layanan saya</a> · <a href="{{ url('/vendor/peralatan') }}">Peralatan saya</a></p></section>
</div>
</main>
@endsection