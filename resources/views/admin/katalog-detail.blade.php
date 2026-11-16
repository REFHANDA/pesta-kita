@extends('layouts.admin')

@section('title', 'Detail katalog penyedia · Admin Pesta Kita')

@php
$item = $item ?? new \App\Models\CatalogItem();
$profil = $item->vendorProfile;
$rupiah = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');

$itemBadge = fn ($status) => match ($status) {
    \App\Enums\CatalogItemStatus::Aktif => ['b-ok', 'Aktif'],
    \App\Enums\CatalogItemStatus::Nonaktif => ['b-mute', 'Nonaktif'],
    default => ['b-warn', 'Menunggu'],
};
[$iClass, $iLabel] = $itemBadge($item->status);
$verifClass = $profil?->verification_status === \App\Enums\VerificationStatus::Terverifikasi ? 'b-ok' : 'b-warn';
@endphp

@section('content')
<main class="content page-refined detail-page" id="konten" tabindex="-1">
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / <a href="{{ url('/admin/katalog') }}">Katalog penyedia</a> / Detail item</div><h1>{{ $item->name ?: 'Detail katalog penyedia' }}</h1><p class="meta">{{ $profil?->business_name ?? 'Penyedia tidak diketahui' }} · {{ $item->kind?->name ?? '—' }} · kategori {{ $item->category ?: '—' }}</p></div><a class="btn btn-o" href="{{ url('/admin/katalog') }}">Kembali ke katalog</a></div>

<div class="data-note" role="note"><span class="ic i-info" aria-hidden="true"></span><p><b>Halaman ini baca-saja.</b> Tidak ada persetujuan per item (keputusan Gate 4B butir 2). Status publikasi item dikelola vendor di portalnya, dan perubahan harga hanya berlaku lewat antrean pengajuan harga yang disetujui Admin.</p></div>

<div class="detail-grid"><div class="detail-stack">
<section class="card detail-card"><div class="card-h"><div class="split-head"><div><h2>Informasi item</h2><p class="meta">Data yang tersimpan pada katalog penyedia.</p></div><span class="badge {{ $iClass }}">{{ $iLabel }}</span></div></div><div class="card-body"><div class="detail-kv"><div class="kv"><span class="k">Jenis</span><span class="v">{{ $item->kind?->name ?? '—' }}</span></div><div class="kv"><span class="k">Kategori</span><span class="v">{{ $item->category ?: '—' }}</span></div><div class="kv"><span class="k">Harga aktif</span><span class="v">{{ $item->pricing_mode === \App\Enums\PricingMode::PerluPenawaran ? 'Perlu penawaran' : $rupiah($item->catalog_price) }}</span></div>@if($item->max_per_day)<div class="kv"><span class="k">Maksimal per hari</span><span class="v">{{ $item->max_per_day }} unit</span></div>@endif<div class="kv"><span class="k">Dibuat</span><span class="v">{{ $item->created_at?->translatedFormat('d M Y') ?? '—' }}</span></div><div class="kv"><span class="k">Diperbarui</span><span class="v">{{ $item->updated_at?->translatedFormat('d M Y') ?? '—' }}</span></div></div>
@if ($item->description)
<p class="field-label mt-16">Deskripsi</p>
<p class="body-copy">{{ $item->description }}</p>
@endif
@if ($item->policies)
<p class="field-label">Kebijakan penyedia</p>
<p class="body-copy">{{ $item->policies }}</p>
@endif
</div></section>
</div>
<aside class="detail-side">
<section class="card detail-card"><div class="card-h"><h2>Informasi penyedia</h2></div><div class="card-body"><div class="detail-list"><div class="dl-row"><span>Usaha</span><strong>{{ $profil?->business_name ?? '—' }}</strong></div><div class="dl-row"><span>Kategori</span><strong>{{ $profil?->category ?? '—' }}</strong></div><div class="dl-row"><span>Verifikasi</span><strong><span class="badge {{ $verifClass }}">{{ $profil?->verification_status?->name ?? '—' }}</span></strong></div></div>@if($profil)<a class="btn btn-o btn-block" href="{{ url('/admin/penyedia/vendor_profile/'.$profil->id) }}">Lihat detail penyedia</a>@endif</div></section>
<section class="card detail-card"><div class="card-h"><h2>Kelola terkait</h2></div><div class="card-body"><div class="detail-list"><div class="dl-row"><span>Perubahan harga item</span><strong><a href="{{ url('/admin/pengajuan-harga') }}">Antrean pengajuan harga</a></strong></div><div class="dl-row"><span>Status verifikasi penyedia</span><strong><a href="{{ url('/admin/admin-penyedia') }}">Antrean verifikasi</a></strong></div></div><div class="alert mt-16"><span class="ic i-info" aria-hidden="true"></span><p>Vendor menonaktifkan/menyalakan item sendiri di portal vendor; Admin cukup memutus jalur transaksi lewat penonaktifan verifikasi profil bila diperlukan.</p></div></div></section>
</aside></div>
</main>
@endsection
