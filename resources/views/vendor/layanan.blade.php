@extends('layouts.vendor')

@section('title', 'Layanan saya · Panel Vendor Pesta Kita')

@section('content')
<main id="konten" class="content" tabindex="-1"><div class="page-head"><div><div class="crumbs"><a href="{{ url('/vendor') }}">Beranda</a> / Layanan saya</div><h1>Layanan saya</h1><p class="sub">Item tampil di katalog selama penyedia terverifikasi dan item berstatus aktif. Tidak ada persetujuan Admin per item; perubahan harga langsung tersimpan.</p></div><a class="btn" href="{{ route('vendor.katalog.create') }}"><span class="ic i-plus" aria-hidden="true"></span>Tambah layanan</a></div>
@if(session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
<section class="card"><div class="table-wrap" role="region" tabindex="0" aria-label="Daftar layanan. Gulir horizontal untuk melihat seluruh kolom"><table class="t-wide"><caption class="sr-only">Tabel: Daftar layanan</caption><thead><tr><th scope="col">Layanan</th><th scope="col">Kategori</th><th scope="col" class="num">Harga per acara</th><th scope="col">Kuota harian</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead><tbody>
@forelse($items as $item)
<tr><td><b>{{ $item->name }}</b></td><td>{{ $item->category }}</td><td class="num">Rp {{ number_format($item->catalog_price, 0, ',', '.') }}</td><td>{{ $item->max_per_day ? $item->max_per_day.'/hari' : '—' }}</td><td>@if($item->status === \App\Enums\CatalogItemStatus::Aktif)<span class="badge b-ok">Aktif</span>@elseif($item->status === \App\Enums\CatalogItemStatus::Nonaktif)<span class="badge b-mute">Nonaktif</span>@else<span class="badge b-warn">{{ $item->status->value }}</span>@endif</td><td><div class="actions"><a class="btn btn-o btn-sm" href="{{ route('vendor.katalog.edit', $item) }}">Ubah</a><form method="POST" action="{{ route('vendor.katalog.status', $item) }}">@csrf<input type="hidden" name="status" value="{{ $item->status === \App\Enums\CatalogItemStatus::Aktif ? 'nonaktif' : 'aktif' }}"><button class="btn btn-o btn-sm" type="submit">{{ $item->status === \App\Enums\CatalogItemStatus::Aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button></form><form method="POST" action="{{ route('vendor.katalog.destroy', $item) }}" onsubmit="return confirm('Hapus layanan ini? Item yang sudah punya pesanan tetap tersimpan sebagai riwayat.')">@csrf @method('DELETE')<button class="btn btn-o btn-sm" type="submit">Hapus</button></form></div></td></tr>
@empty
<tr><td colspan="6">Belum ada layanan. Tambahkan layanan pertama Anda untuk mulai tampil di katalog setelah penyedia terverifikasi.</td></tr>
@endforelse
</tbody></table></div><p class="hint mt-12">Menampilkan {{ $items->count() }} item. Item nonaktif tidak tampil di katalog Klien; riwayat pesanan tetap tersimpan.</p></section></main>
@endsection