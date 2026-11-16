@extends('layouts.vendor')

@section('title', ($item ? 'Ubah peralatan' : 'Tambah peralatan').' · Panel Vendor Pesta Kita')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/profil-media.css') }}">
@endpush

@section('content')
@php($isEdit = $item !== null)
<main id="konten" class="content" tabindex="-1"><div class="page-head"><div><div class="crumbs"><a href="{{ url('/vendor') }}">Beranda</a> / <a href="{{ route('vendor.peralatan') }}">Peralatan sewa</a> / {{ $isEdit ? 'Ubah peralatan' : 'Tambah peralatan' }}</div><h1>{{ $isEdit ? 'Ubah peralatan' : 'Tambah peralatan' }}</h1></div></div>
@if(session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
<form class="card w-860" method="POST" action="{{ $isEdit ? route('vendor.katalog.update', $item) : route('vendor.katalog.store') }}">@csrf @if($isEdit) @method('PUT') @else<input type="hidden" name="kind" value="peralatan">@endif
<div class="card-h"><h2>Informasi peralatan</h2></div><div class="card-b">
<div class="form-grid"><div class="field full"><label for="nama-peralatan">Nama peralatan <span class="req">*</span></label><input class="input" id="nama-peralatan" name="name" type="text" value="{{ old('name', $item?->name) }}" required @error('name') aria-invalid="true" @enderror>@error('name')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
<div class="field"><label for="kategori-peralatan">Kategori <span class="req">*</span></label><input class="input" id="kategori-peralatan" name="category" type="text" value="{{ old('category', $item?->category) }}" required @error('category') aria-invalid="true" @enderror><p class="hint">Contoh: Furniture, Audio, Pencahayaan.</p>@error('category')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
<div class="field"><label for="mode-harga">Mode harga <span class="req">*</span></label><select class="input" id="mode-harga" name="pricing_mode" required><option value="tetap" @selected(old('pricing_mode', $item?->pricing_mode?->value ?? 'tetap') === 'tetap')>Harga tetap</option><option value="perlu_penawaran" @selected(old('pricing_mode', $item?->pricing_mode?->value) === 'perlu_penawaran')>Perlu penawaran</option></select>@error('pricing_mode')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
<div class="field"><label for="harga-sewa">Harga sewa (Rp) <span class="req">*</span></label><input class="input" id="harga-sewa" name="catalog_price" type="number" min="0" value="{{ old('catalog_price', $item?->catalog_price ?? 0) }}" required @error('catalog_price') aria-invalid="true" @enderror><p class="hint">Isi 0 bila mode harga perlu penawaran. Perubahan harga langsung tersimpan; alur pengajuan harga ke Admin menyusul pada tahap berikutnya.</p>@error('catalog_price')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
<div class="field full"><label for="deskripsi-peralatan">Deskripsi</label><textarea class="input" id="deskripsi-peralatan" name="description" rows="4">{{ old('description', $item?->description) }}</textarea>@error('description')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
<div class="field full"><label for="kebijakan">Ketentuan sewa</label><textarea class="input" id="kebijakan" name="policies" rows="3">{{ old('policies', $item?->policies) }}</textarea><p class="hint">Contoh: minimal sewa, area antar, ketentuan kerusakan.</p>@error('policies')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div></div>
<p class="hint mt-12">Peralatan baru langsung berstatus aktif dan tampil di katalog Klien setelah penyedia terverifikasi — tanpa persetujuan Admin per item. Pengelolaan stok per tanggal acara tersedia pada tahap berikutnya.</p>
<div class="form-actions"><button class="btn" type="submit">{{ $isEdit ? 'Simpan perubahan' : 'Simpan peralatan' }}</button><a class="btn btn-o" href="{{ route('vendor.peralatan') }}">Batal</a></div></div></form>
@if($isEdit)
<section class="card w-860 mt-20"><div class="card-h"><h2>Foto peralatan</h2></div><div class="card-b">
<form method="POST" action="{{ route('vendor.katalog.foto.store', $item) }}" enctype="multipart/form-data"><div class="upload-box"><span class="ic i-image" aria-hidden="true"></span><div class="grow"><label for="foto-peralatan"><b>Pilih foto peralatan</b></label><small class="sub-t">JPG, PNG, atau WebP · maksimal 5 MB · maksimal 10 foto per item.</small></div><input id="foto-peralatan" name="photo" type="file" accept=".jpg,.jpeg,.png,.webp" required></div>@error('photo')<p class="field-error" role="alert">{{ $message }}</p>@enderror<div class="form-actions"><button class="btn" type="submit">Unggah foto</button></div></form>
@php($photos = $item->media()->foto()->orderBy('sort_order')->get())
@if($photos->isNotEmpty())<ul class="media-list mt-12">@foreach($photos as $photo)<li><img src="{{ route('media.show', $photo) }}" alt="{{ $photo->original_name ?? 'Foto peralatan' }}" width="96" height="96"><div class="grow"><b>{{ $photo->original_name ?? 'Foto' }}</b><small class="sub-t">{{ $photo->visibility === \App\Enums\MediaVisibility::Publik ? 'Publik' : 'Privat — tampil setelah penyedia terverifikasi' }}</small></div><form method="POST" action="{{ route('media.destroy', $photo) }}" onsubmit="return confirm('Hapus foto ini?')">@csrf @method('DELETE')<button class="btn btn-o btn-sm" type="submit">Hapus</button></form></li>@endforeach</ul>@else<p class="hint mt-12">Belum ada foto. Foto pertama menjadi foto utama item.</p>@endif
</div></section>
@else
<p class="hint mt-12">Foto peralatan dapat diunggah setelah item disimpan.</p>
@endif
</main>
@endsection