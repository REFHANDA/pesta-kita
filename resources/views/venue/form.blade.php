@extends('layouts.venue')

@section('title', ($venue ? 'Ubah venue' : 'Tambah venue').' · Panel Pemilik Venue Pesta Kita')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/profil-media.css') }}">
@endpush

@section('content')
@php
$venue = $venue ?? null;
$isEdit = $venue !== null;
$kindOptions = ['Ballroom', 'Gedung serbaguna', 'Aula', 'Garden / outdoor', 'Restoran', 'Rumah / private venue', 'Lainnya'];
if ($venue && ! in_array($venue->kind, $kindOptions, true)) { array_unshift($kindOptions, $venue->kind); }
$facilityOptions = ['Area parkir', 'AC', 'Toilet', 'Ruang rias', 'Sound system', 'Area outdoor', 'Mushola', 'Panggung'];
$selectedFacilities = old('facilities', $venue?->facilities?->pluck('name')->all() ?? []);
foreach ($selectedFacilities as $facility) { if (! in_array($facility, $facilityOptions, true)) { $facilityOptions[] = $facility; } }
@endphp
<main id="konten" class="content" tabindex="-1">
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/venue') }}">Beranda</a> / <a href="{{ route('venue.data') }}">Data venue</a> / {{ $isEdit ? 'Ubah venue' : 'Tambah venue' }}</div><h1>{{ $isEdit ? 'Ubah venue' : 'Tambah venue' }}</h1><p class="sub">Kelola identitas, kapasitas, harga, fasilitas, dan foto venue. Venue tayang mengikuti verifikasi profil penyedia.</p></div></div>
@if(session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif

<form class="card form-card" method="POST" action="{{ $isEdit ? route('venue.data.update', $venue) : route('venue.data.store') }}">@csrf @if($isEdit) @method('PUT') @endif
  <div class="card-h"><div><h2>Informasi venue</h2><p class="sub">Lengkapi informasi utama yang akan dilihat calon klien.</p></div></div>
  <div class="card-b">
    <div class="form-grid">
      <div class="field full"><label for="nama-venue">Nama venue <span class="req">*</span></label><input class="input" id="nama-venue" name="name" value="{{ old('name', $venue?->name) }}" required @error('name') aria-invalid="true" @enderror>@error('name')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
      <div class="field"><label for="jenis">Jenis venue <span class="req">*</span></label><select class="input" id="jenis" name="kind" required>@foreach($kindOptions as $option)<option value="{{ $option }}" @selected(old('kind', $venue?->kind) === $option)>{{ $option }}</option>@endforeach</select>@error('kind')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
      <div class="field"><label for="kapasitas">Kapasitas maksimal (orang) <span class="req">*</span></label><input class="input" id="kapasitas" name="capacity" type="number" min="1" value="{{ old('capacity', $venue?->capacity) }}" required @error('capacity') aria-invalid="true" @enderror>@error('capacity')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
      <div class="field full"><label for="alamat">Alamat lengkap <span class="req">*</span></label><textarea class="input" id="alamat" name="address" rows="2" required @error('address') aria-invalid="true" @enderror>{{ old('address', $venue?->address) }}</textarea>@error('address')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
      <div class="field full"><label for="deskripsi">Deskripsi venue</label><textarea class="input" id="deskripsi" name="description" rows="4">{{ old('description', $venue?->description) }}</textarea><p class="hint">Jelaskan suasana, keunggulan, dan jenis acara yang sesuai.</p>@error('description')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
    </div>
  </div>
  <div class="card-h"><div><h2>Harga dan fasilitas</h2><p class="sub">Biaya kebersihan ditetapkan dan diubah hanya oleh Admin.</p></div></div>
  <div class="card-b">
    <div class="form-grid">
      <div class="field"><label for="harga">Harga sewa venue (Rp) <span class="req">*</span></label><input class="input" id="harga" name="rental_price" type="number" min="0" value="{{ old('rental_price', $venue?->rental_price) }}" required @error('rental_price') aria-invalid="true" @enderror><p class="hint">Perubahan harga langsung tersimpan; alur pengajuan harga ke Admin menyusul pada tahap berikutnya.</p>@error('rental_price')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
      <div class="field"><label for="kebersihan">Biaya kebersihan (Rp)</label><input class="input" id="kebersihan" type="text" value="{{ $venue?->cleaning_fee !== null ? 'Rp '.number_format($venue->cleaning_fee, 0, ',', '.') : 'Belum ditetapkan' }}" readonly aria-describedby="kb-h"><p class="hint" id="kb-h">Biaya kebersihan hanya dapat diubah Admin.</p></div>
      <div class="field"><label for="durasi">Durasi sewa (jam)</label><input class="input" id="durasi" name="duration_hours" type="number" min="1" value="{{ old('duration_hours', $venue?->duration_hours) }}" @error('duration_hours') aria-invalid="true" @enderror>@error('duration_hours')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
      <div class="field"><label for="deposit">Deposit (Rp)</label><input class="input" id="deposit" name="deposit_amount" type="number" min="0" value="{{ old('deposit_amount', $venue?->deposit_amount) }}" @error('deposit_amount') aria-invalid="true" @enderror><p class="hint">Opsional; belum ada logika pembayaran deposit.</p>@error('deposit_amount')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
      <div class="field full"><label>Fasilitas tersedia</label><div class="check-grid">@foreach($facilityOptions as $facility)<label class="check"><input type="checkbox" name="facilities[]" value="{{ $facility }}" @checked(in_array($facility, $selectedFacilities, true))> {{ $facility }}</label>@endforeach</div>@error('facilities')<p class="field-error" role="alert">{{ $message }}</p>@enderror @error('facilities.*')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
      <div class="field full"><label for="catatan">Ketentuan tambahan / aturan venue</label><textarea class="input" id="catatan" name="rules" rows="3" placeholder="Tuliskan aturan venue yang perlu diketahui klien">{{ old('rules', $venue?->rules) }}</textarea>@error('rules')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
      <div class="field full"><label for="batal">Kebijakan pembatalan</label><textarea class="input" id="batal" name="cancellation_policy" rows="3" placeholder="Jelaskan ketentuan pembatalan dan pengembalian dana">{{ old('cancellation_policy', $venue?->cancellation_policy) }}</textarea>@error('cancellation_policy')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
    </div>
  </div>
  @unless($isEdit)
  <div class="card-h"><div><h2>Ruangan pertama <span class="req">*</span></h2><p class="sub">Minimal satu ruangan wajib diisi; ketersediaan dikelola per ruangan.</p></div></div>
  <div class="card-b">
    <div class="form-grid">
      <div class="field"><label for="ruang-0">Nama ruangan <span class="req">*</span></label><input class="input" id="ruang-0" name="rooms[0][name]" value="{{ old('rooms.0.name') }}" required @error('rooms.0.name') aria-invalid="true" @enderror>@error('rooms.0.name')<p class="field-error" role="alert">{{ $message }}</p>@enderror @error('rooms')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
      <div class="field"><label for="ruang-kap-0">Kapasitas ruangan (orang)</label><input class="input" id="ruang-kap-0" name="rooms[0][capacity]" type="number" min="1" value="{{ old('rooms.0.capacity') }}" @error('rooms.0.capacity') aria-invalid="true" @enderror>@error('rooms.0.capacity')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
    </div>
  </div>
  @endunless
  <div class="card-b">
    <p class="hint">Venue baru mengikuti status verifikasi profil penyedia: bila profil sudah terverifikasi, venue langsung tayang; bila belum, venue menunggu keputusan Admin.</p>
    <div class="form-actions"><button class="btn" type="submit">{{ $isEdit ? 'Simpan perubahan' : 'Simpan venue' }}</button><a class="btn btn-o" href="{{ route('venue.data') }}">Batal</a></div>
  </div>
</form>

@if($isEdit)
<section class="card mt-20"><div class="card-h"><div><h2>Ruangan</h2><p class="sub">Nama ruangan unik per venue. Ruangan yang sudah dipakai pesanan tidak dapat dihapus.</p></div></div><div class="card-b">
@forelse($venue->rooms as $room)
<form class="form-grid mb-12" method="POST" action="{{ route('venue.ruangan.update', $room) }}">@csrf @method('PUT')<div class="field"><label for="ruang-{{ $room->id }}">Nama ruangan</label><input class="input" id="ruang-{{ $room->id }}" name="name" value="{{ old('name', $room->name) }}" required></div><div class="field"><label for="ruang-kap-{{ $room->id }}">Kapasitas (orang)</label><input class="input" id="ruang-kap-{{ $room->id }}" name="capacity" type="number" min="1" value="{{ old('capacity', $room->capacity) }}"></div><div class="field"><label>&nbsp;</label><div class="actions"><button class="btn btn-o" type="submit">Simpan ruangan</button></div></div></form>
<form method="POST" action="{{ route('venue.ruangan.destroy', $room) }}" onsubmit="return confirm('Hapus ruangan ini? Ruangan yang sudah dipakai pesanan tidak dapat dihapus.')">@csrf @method('DELETE')<button class="btn btn-o btn-sm" type="submit">Hapus ruangan {{ $room->name }}</button></form>
@empty
<p class="hint">Belum ada ruangan.</p>
@endforelse
<form class="form-grid mt-12" method="POST" action="{{ route('venue.ruangan.store', $venue) }}">@csrf<div class="field"><label for="ruang-baru">Nama ruangan baru</label><input class="input" id="ruang-baru" name="name" value="{{ old('name') }}" required>@error('name')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div><div class="field"><label for="ruang-baru-kap">Kapasitas (orang)</label><input class="input" id="ruang-baru-kap" name="capacity" type="number" min="1" value="{{ old('capacity') }}"></div><div class="field"><label>&nbsp;</label><button class="btn" type="submit">Tambah ruangan</button></div></form>
</div></section>

<section class="card mt-20"><div class="card-h"><h2>Foto venue</h2></div><div class="card-b">
<form method="POST" action="{{ route('venue.data.foto.store', $venue) }}" enctype="multipart/form-data">@csrf<div class="upload-box"><span class="ic i-image" aria-hidden="true"></span><div class="grow"><label for="foto-venue"><b>Pilih foto venue</b></label><small class="sub-t">JPG, PNG, atau WebP · maksimal 5 MB · maksimal 10 foto per venue.</small></div><input id="foto-venue" name="photo" type="file" accept=".jpg,.jpeg,.png,.webp" required></div>@error('photo')<p class="field-error" role="alert">{{ $message }}</p>@enderror<div class="form-actions"><button class="btn" type="submit">Unggah foto</button></div></form>
@php($photos = $venue->media()->foto()->orderBy('sort_order')->get())
@if($photos->isNotEmpty())<ul class="media-list mt-12">@foreach($photos as $photo)<li><img src="{{ route('media.show', $photo) }}" alt="{{ $photo->original_name ?? 'Foto venue' }}" width="96" height="96"><div class="grow"><b>{{ $photo->original_name ?? 'Foto' }}</b><small class="sub-t">{{ $photo->visibility === \App\Enums\MediaVisibility::Publik ? 'Publik' : 'Privat — tampil setelah penyedia terverifikasi' }}</small></div><form method="POST" action="{{ route('media.destroy', $photo) }}" onsubmit="return confirm('Hapus foto ini?')">@csrf @method('DELETE')<button class="btn btn-o btn-sm" type="submit">Hapus</button></form></li>@endforeach</ul>@else<p class="hint mt-12">Belum ada foto venue.</p>@endif
</div></section>
<section class="card mt-20"><div class="card-h"><h2>Denah venue</h2></div><div class="card-b">
<form method="POST" action="{{ route('venue.data.denah.store', $venue) }}" enctype="multipart/form-data">@csrf
<div class="upload-box"><span class="ic i-image" aria-hidden="true"></span><div class="grow"><label for="denah-venue"><b>Unggah denah venue</b></label><small class="sub-t">PDF, JPG, PNG, atau WebP · maksimal 20 MB. File privat dan hanya dapat diakses pihak yang berwenang.</small></div><input id="denah-venue" name="file" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" required></div>
@error('file')<p class="field-error" role="alert">{{ $message }}</p>@enderror<div class="form-actions"><button class="btn" type="submit">Simpan denah</button></div></form>
@if($floorPlan)<p class="mt-12"><b>Denah saat ini:</b> <a href="{{ route('media.show', $floorPlan) }}" target="_blank" rel="noopener">{{ $floorPlan->original_name ?? 'Lihat denah' }}</a></p>@else<p class="hint mt-12">Belum ada denah venue.</p>@endif
</div></section>
@else
<p class="hint mt-12">Ruangan tambahan dan foto venue dapat dikelola setelah venue disimpan.</p>
@endif
</main>
@endsection