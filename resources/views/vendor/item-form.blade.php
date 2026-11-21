@extends('layouts.vendor')

@section('title', ($item ? 'Ubah layanan' : 'Tambah layanan').' · Panel Vendor Pesta Kita')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/profil-media.css') }}">
@endpush

@section('content')
@php($isEdit = $item !== null)
<main id="konten" class="content" tabindex="-1"><div class="page-head"><div><div class="crumbs"><a href="{{ url('/vendor') }}">Beranda</a> / <a href="{{ route('vendor.layanan') }}">Layanan saya</a> / {{ $isEdit ? 'Ubah layanan' : 'Tambah layanan' }}</div><h1>{{ $isEdit ? 'Ubah layanan' : 'Tambah layanan' }}</h1></div></div>
@if(session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
<form class="card w-860" method="POST" action="{{ $isEdit ? route('vendor.katalog.update', $item) : route('vendor.katalog.store') }}">@csrf @if($isEdit) @method('PUT') @else<input type="hidden" name="kind" value="layanan">@endif
<div class="card-h"><h2>Informasi layanan</h2></div><div class="card-b">
<div class="form-grid"><div class="field full"><label for="nama">Nama layanan <span class="req">*</span></label><input class="input" id="nama" name="name" type="text" value="{{ old('name', $item?->name) }}" required @error('name') aria-invalid="true" @enderror>@error('name')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
<div class="field"><label for="kat">Kategori <span class="req">*</span></label><input class="input" id="kat" name="category" type="text" value="{{ old('category', $item?->category) }}" required @error('category') aria-invalid="true" @enderror><p class="hint">Contoh: Katering, Dekorasi, Hiburan, Fotografi.</p>@error('category')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
<div class="field"><label for="kuota">Maksimal acara per hari</label><input class="input" id="kuota" name="max_per_day" type="number" min="1" value="{{ old('max_per_day', $item?->max_per_day) }}" @error('max_per_day') aria-invalid="true" @enderror><p class="hint">Berapa acara berbeda yang bisa Anda layani pada tanggal yang sama.</p>@error('max_per_day')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
<div class="field full"><label for="desk">Deskripsi</label><textarea class="input" id="desk" name="description" rows="3">{{ old('description', $item?->description) }}</textarea>@error('description')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
<input type="hidden" name="pricing_mode" value="tetap">
<div class="field"><label for="harga">Harga katalog (Rp) <span class="req">*</span></label><input class="input" id="harga" name="catalog_price" type="number" min="0" value="{{ old('catalog_price', $item?->catalog_price ?? '') }}" required @error('catalog_price') aria-invalid="true" @enderror><p class="hint">Harga tetap yang aktif pada katalog.</p>@error('catalog_price')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
<div class="field full"><label for="kebijakan">Kebijakan dan kebutuhan khusus</label><textarea class="input" id="kebijakan" name="policies" rows="3">{{ old('policies', $item?->policies) }}</textarea><p class="hint">Contoh: minimal pemesanan, area layanan, kebutuhan khusus.</p>@error('policies')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div></div>
<p class="hint mt-12">Item baru langsung berstatus aktif dan tampil di katalog Klien setelah penyedia terverifikasi — tanpa persetujuan Admin per item.</p>
<div class="form-actions"><button class="btn" type="submit">{{ $isEdit ? 'Simpan perubahan' : 'Simpan layanan' }}</button><a class="btn btn-o" href="{{ route('vendor.layanan') }}">Batal</a></div></div></form>
@if($isEdit)
<section class="card w-860 mt-20"><div class="card-h"><h2>Foto layanan</h2></div><div class="card-b">
<form method="POST" action="{{ route('vendor.katalog.foto.store', $item) }}" enctype="multipart/form-data"><div class="upload-box"><span class="ic i-image" aria-hidden="true"></span><div class="grow"><label for="foto-item"><b>Pilih foto layanan</b></label><small class="sub-t">JPG, PNG, atau WebP · maksimal 5 MB · maksimal 10 foto per item.</small></div><input id="foto-item" name="photo" type="file" accept=".jpg,.jpeg,.png,.webp" required></div>@error('photo')<p class="field-error" role="alert">{{ $message }}</p>@enderror<div class="form-actions"><button class="btn" type="submit">Unggah foto</button></div></form>
@php($photos = $item->media()->foto()->orderBy('sort_order')->get())
@if($photos->isNotEmpty())<ul class="media-list mt-12">@foreach($photos as $photo)<li><img src="{{ route('media.show', $photo) }}" alt="{{ $photo->original_name ?? 'Foto layanan' }}" width="96" height="96"><div class="grow"><b>{{ $photo->original_name ?? 'Foto' }}</b><small class="sub-t">{{ $photo->visibility === \App\Enums\MediaVisibility::Publik ? 'Publik' : 'Privat — tampil setelah penyedia terverifikasi' }}</small></div><form method="POST" action="{{ route('media.destroy', $photo) }}" onsubmit="return confirm('Hapus foto ini?')">@csrf @method('DELETE')<button class="btn btn-o btn-sm" type="submit">Hapus</button></form></li>@endforeach</ul>@else<p class="hint mt-12">Belum ada foto. Foto pertama menjadi foto utama item.</p>@endif
</div></section>
@else
<p class="hint mt-12">Foto layanan dapat diunggah setelah item disimpan.</p>
@endif
</main>
@endsection