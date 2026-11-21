@extends('layouts.admin')

@section('title', 'Katalog penyedia · Admin Pesta Kita')

@php
$items = $items ?? collect();
$venues = $venues ?? collect();
$rupiah = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');

$itemBadge = fn ($status) => match ($status) {
    \App\Enums\CatalogItemStatus::Aktif => ['b-ok', 'Aktif'],
    \App\Enums\CatalogItemStatus::Nonaktif => ['b-mute', 'Nonaktif'],
    default => ['b-warn', 'Menunggu'],
};
$verifBadge = fn ($status) => match ($status) {
    \App\Enums\VerificationStatus::Terverifikasi => ['b-ok', 'Terverifikasi'],
    \App\Enums\VerificationStatus::Menunggu => ['b-warn', 'Menunggu verifikasi'],
    \App\Enums\VerificationStatus::Ditolak => ['b-bad', 'Ditolak'],
    \App\Enums\VerificationStatus::Nonaktif => ['b-mute', 'Nonaktif'],
    default => ['b-mute', '-'],
};
$jenisAktif = (string) request('jenis', '');
$statusAktif = (string) request('status', '');
@endphp

@section('content')
<main class="content" id="konten" tabindex="-1">
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / Penyedia &amp; katalog</div><h1>Katalog penyedia</h1><p class="sub">Tinjauan seluruh item katalog dan venue yang tersimpan. Tidak ada persetujuan per item (keputusan Gate 4B): item dipublik oleh vendornya sendiri dan venue mengikuti verifikasi profil.</p></div></div>
<p class="hint"><b>Halaman ini baca-saja.</b> Status item dikelola penyedia di portalnya masing-masing, perubahan harga lewat antrean pengajuan harga, dan status venue individual lewat formulir venue. Tambah layanan/venue tidak tersedia bagi Admin — penyedia mengisi datanya sendiri.</p>
<x-subnav label="Penyedia & katalog" :items="[
    ['label' => 'Verifikasi penyedia', 'href' => route('admin.penyedia.index'), 'active' => request()->routeIs('admin.penyedia.index')],
    ['label' => 'Katalog', 'href' => route('admin.katalog'), 'active' => request()->routeIs('admin.katalog')],
    ['label' => 'Pengajuan harga', 'href' => route('admin.pengajuan-harga'), 'active' => request()->routeIs('admin.pengajuan-harga')],
    ['label' => 'Pengguna', 'href' => route('admin.pengguna'), 'active' => request()->routeIs('admin.pengguna')],
]" />
<section class="card"><div aria-label="Navigasi jenis dan status katalog" class="tabs" role="navigation">
<a href="{{ url('/admin/katalog') }}" @if($jenisAktif === '' && $statusAktif === '')aria-current="page"@endif>Semua item</a>
<a href="{{ url('/admin/katalog?jenis=layanan') }}" @if($jenisAktif === 'layanan')aria-current="page"@endif>Layanan</a>
<a href="{{ url('/admin/katalog?jenis=peralatan') }}" @if($jenisAktif === 'peralatan')aria-current="page"@endif>Peralatan</a>
<a href="{{ url('/admin/katalog?status=aktif') }}" @if($statusAktif === 'aktif')aria-current="page"@endif>Aktif</a>
<a href="{{ url('/admin/katalog?status=nonaktif') }}" @if($statusAktif === 'nonaktif')aria-current="page"@endif>Nonaktif</a>
</div>
<div aria-label="Katalog penyedia. Gulir horizontal untuk melihat seluruh kolom" class="table-wrap" role="region" tabindex="0"><table class="t-wide"><caption class="sr-only">Tabel: Katalog penyedia</caption><thead><tr><th scope="col">Item</th><th scope="col">Penyedia</th><th scope="col">Jenis</th><th scope="col">Harga aktif</th><th scope="col">Status</th><th class="col-aksi" scope="col">Aksi</th></tr></thead><tbody>
@forelse($items as $item)
    @php([$iClass, $iLabel] = $itemBadge($item->status))
    <tr><td><b>{{ $item->name }}</b><small class="sub-t">{{ $item->category }}</small></td><td>{{ $item->vendorProfile?->business_name ?? '—' }}</td><td>{{ $item->kind->name }}</td><td>{{ $rupiah($item->catalog_price) }}</td><td><span class="badge {{ $iClass }}">{{ $iLabel }}</span></td><td class="col-aksi"><a class="btn btn-sm btn-o" href="{{ url('/admin/katalog-detail/'.$item->id) }}">Detail</a></td></tr>
@empty
    <tr><td colspan="6"><p class="hint" style="padding:16px;margin:0">Belum ada item katalog tersimpan. Item muncul setelah penyedia menambahkannya lewat portal vendor.</p></td></tr>
@endforelse
</tbody></table></div></section>
<section class="card"><div class="card-h"><h2>Venue</h2><p class="meta">Venue dikelola pemilik venue; Admin dapat mengatur status individual dan biaya kebersihan lewat formulir venue.</p></div>
<div aria-label="Daftar venue. Gulir horizontal untuk melihat seluruh kolom" class="table-wrap" role="region" tabindex="0"><table class="t-wide"><caption class="sr-only">Tabel: Venue</caption><thead><tr><th scope="col">Venue</th><th scope="col">Pemilik</th><th scope="col">Kapasitas</th><th scope="col">Harga sewa</th><th scope="col">Verifikasi</th><th class="col-aksi" scope="col">Aksi</th></tr></thead><tbody>
@forelse($venues as $venue)
    @php([$vClass, $vLabel] = $verifBadge($venue->verification_status))
    <tr><td><b>{{ $venue->name }}</b><small class="sub-t">{{ $venue->kind }} · {{ $venue->address }}</small></td><td>{{ $venue->venueProfile?->business_name ?? '—' }}</td><td>{{ $venue->capacity ? $venue->capacity.' orang' : '—' }}</td><td>{{ $rupiah($venue->rental_price) }}</td><td><span class="badge {{ $vClass }}">{{ $vLabel }}</span></td><td class="col-aksi"><a class="btn btn-sm btn-o" href="{{ url('/admin/admin-venue-form/'.$venue->id) }}">Kelola venue</a></td></tr>
@empty
    <tr><td colspan="6"><p class="hint" style="padding:16px;margin:0">Belum ada venue tersimpan. Venue muncul setelah pemilik venue mengisinya lewat portal venue.</p></td></tr>
@endforelse
</tbody></table></div></section>
</main>
@endsection
