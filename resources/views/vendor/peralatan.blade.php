@extends('layouts.vendor')

@section('title', 'Peralatan sewa · Panel Vendor Pesta Kita')

@section('content')
<main id="konten" class="content" tabindex="-1"><div class="page-head"><div><div class="crumbs"><a href="{{ url('/vendor') }}">Beranda</a> / Peralatan sewa</div><h1>Peralatan sewa</h1><p class="sub">Kelola harga sewa peralatan. Item tampil di katalog selama penyedia terverifikasi dan item berstatus aktif; tidak ada persetujuan Admin per item.</p></div><a class="btn" href="{{ route('vendor.peralatan.create') }}">＋ Tambah peralatan</a></div>
<x-subnav label="Katalog saya" :items="[
    ['label' => 'Layanan', 'href' => route('vendor.layanan'), 'active' => request()->routeIs('vendor.layanan')],
    ['label' => 'Peralatan', 'href' => route('vendor.peralatan'), 'active' => request()->routeIs('vendor.peralatan')],
    ['label' => 'Pengajuan harga', 'href' => route('vendor.pengajuan-harga'), 'active' => request()->routeIs('vendor.pengajuan-harga')],
]" />
@if(session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
<section class="card"><div class="table-wrap" role="region" tabindex="0" aria-label="Daftar peralatan. Gulir horizontal untuk melihat seluruh kolom"><table class="t-wide"><caption class="sr-only">Tabel: Daftar peralatan</caption><thead><tr><th scope="col">Nama peralatan</th><th scope="col">Kategori</th><th scope="col" class="num">Harga sewa</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead><tbody>
@forelse($items as $item)
<tr><td><b>{{ $item->name }}</b></td><td>{{ $item->category }}</td><td class="num">Rp {{ number_format($item->catalog_price, 0, ',', '.') }}</td><td>@if($item->status === \App\Enums\CatalogItemStatus::Aktif)<span class="badge b-ok">Aktif</span>@elseif($item->status === \App\Enums\CatalogItemStatus::Nonaktif)<span class="badge b-mute">Nonaktif</span>@else<span class="badge b-warn">{{ $item->status->value }}</span>@endif</td><td><div class="actions"><a class="btn btn-o btn-sm" href="{{ route('vendor.peralatan.edit', $item) }}">Ubah</a><form method="POST" action="{{ route('vendor.katalog.status', $item) }}">@csrf<input type="hidden" name="status" value="{{ $item->status === \App\Enums\CatalogItemStatus::Aktif ? 'nonaktif' : 'aktif' }}"><button class="btn btn-o btn-sm" type="submit">{{ $item->status === \App\Enums\CatalogItemStatus::Aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button></form></div></td></tr>
@empty
<tr><td colspan="5">Belum ada peralatan. Tambahkan peralatan pertama Anda untuk mulai tampil di katalog setelah penyedia terverifikasi.</td></tr>
@endforelse
</tbody></table></div><p class="hint mt-12">Menampilkan {{ $items->count() }} item. Pengelolaan stok per tanggal acara tersedia pada tahap berikutnya.</p></section></main>
@endsection