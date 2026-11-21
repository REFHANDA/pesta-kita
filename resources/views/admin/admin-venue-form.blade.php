@extends('layouts.admin')

@section('title', 'Kelola venue · Panel Admin Pesta Kita')

@section('content')
@php($venue = $venue ?? null)
<main id="konten" class="content" tabindex="-1"><div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / <a href="{{ url('/admin/katalog') }}">Katalog penyedia</a> / Kelola venue</div><h1>Kelola venue</h1><p class="sub">Admin menetapkan biaya kebersihan dan dapat menonaktifkan venue secara individual. Data lain venue dikelola oleh pemilik venue.</p></div></div>
@if(session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
@if($venue === null)
<section class="card"><div class="card-b"><p>Pilih venue dari katalog penyedia atau dari halaman detail mitra untuk mengelola biaya kebersihan dan status venue.</p><div class="form-actions"><a class="btn" href="{{ url('/admin/katalog') }}">Buka katalog penyedia</a><a class="btn btn-o" href="{{ route('admin.penyedia.index') }}">Antrean verifikasi</a></div></div></section>
@else
<div class="alert"><span class="ic i-info" aria-hidden="true"></span><span>Perubahan biaya kebersihan dan status venue tercatat pada log aktivitas Admin.</span></div>
<section class="card"><div class="card-h"><div><h2>Informasi venue</h2><p class="sub">Dikelola oleh pemilik venue; ditampilkan di sini sebagai konteks.</p></div><span class="badge {{ $venue->verification_status === \App\Enums\VerificationStatus::Terverifikasi ? 'b-ok' : ($venue->verification_status === \App\Enums\VerificationStatus::Nonaktif ? 'b-mute' : 'b-warn') }}">{{ ucfirst($venue->verification_status->value) }}</span></div><div class="card-b"><div class="form-grid">
<div class="field"><label for="nama">Nama venue</label><input class="input" id="nama" value="{{ $venue->name }}" readonly></div>
<div class="field"><label for="tipe">Jenis venue</label><input class="input" id="tipe" value="{{ $venue->kind }}" readonly></div>
<div class="field full"><label for="alamat">Alamat lengkap</label><input class="input" id="alamat" value="{{ $venue->address }}" readonly></div>
<div class="field"><label for="kapasitas">Kapasitas maksimal (orang)</label><input class="input" id="kapasitas" value="{{ $venue->capacity }}" readonly></div>
<div class="field"><label for="sewa">Harga sewa venue (Rp)</label><input class="input" id="sewa" value="{{ number_format($venue->rental_price, 0, ',', '.') }}" readonly></div>
<div class="field"><label for="pemilik">Pemilik / penyedia</label><input class="input" id="pemilik" value="{{ $venue->venueProfile?->business_name }}" readonly></div>
</div></div></section>
<form class="card mt-20" method="POST" action="{{ route('admin.venue.update', $venue) }}">@csrf<div class="card-h"><div><h2>Kewenangan Admin</h2><p class="sub">Biaya kebersihan hanya dapat diubah Admin (keputusan Gate 4B butir 8); status individual untuk penonaktifan (butir 3).</p></div></div><div class="card-b"><div class="form-grid">
<div class="field"><label for="bersih">Biaya kebersihan (Rp)</label><input class="input" id="bersih" name="cleaning_fee" type="number" min="0" value="{{ old('cleaning_fee', $venue->cleaning_fee) }}" @error('cleaning_fee') aria-invalid="true" @enderror><p class="hint">Kosongkan bila belum ditetapkan. Terpisah dari harga sewa (B4).</p>@error('cleaning_fee')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
<div class="field"><label for="status">Status venue <span class="req">*</span></label><select class="input" id="status" name="verification_status" required><option value="terverifikasi" @selected(old('verification_status', $venue->verification_status->value) === 'terverifikasi')>Terverifikasi (tayang bila profil terverifikasi)</option><option value="nonaktif" @selected(old('verification_status', $venue->verification_status->value) === 'nonaktif')>Nonaktif (sembunyikan individual)</option></select><p class="hint">Status menunggu mengikuti verifikasi profil penyedia dan tidak diubah manual di sini.</p>@error('verification_status')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
</div><div class="form-actions"><a class="btn btn-o" href="{{ url('/admin/katalog') }}">Batal</a><span class="spacer"></span><button class="btn" type="submit">Simpan perubahan</button></div></div></form>
@endif
</main>
@endsection