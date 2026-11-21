@extends('layouts.venue')

@section('title', 'Verifikasi penyedia · Pesta Kita')

@section('content')
@php
$profile = $profile ?? auth()->user()->venueProfile;
$documents = $documents ?? collect();
$status = $profile?->verification_status;
$badge = match ($status) {
    \App\Enums\VerificationStatus::Terverifikasi => 'b-ok',
    \App\Enums\VerificationStatus::Ditolak => 'b-bad',
    \App\Enums\VerificationStatus::Nonaktif => 'b-mute',
    default => 'b-warn',
};
$statusLabel = match ($status) {
    \App\Enums\VerificationStatus::Terverifikasi => 'Terverifikasi',
    \App\Enums\VerificationStatus::Ditolak => 'Ditolak — perbaiki dan ajukan ulang',
    \App\Enums\VerificationStatus::Nonaktif => 'Nonaktif',
    default => 'Menunggu verifikasi',
};
$docTypes = [
    ['type' => \App\Enums\VerificationDocType::Ktp, 'required' => true, 'hint' => 'KTP pemilik / penanggung jawab venue. JPG, PNG, atau PDF · maks. 5 MB.'],
    ['type' => \App\Enums\VerificationDocType::BuktiVenue, 'required' => true, 'hint' => 'Sertifikat, perjanjian sewa, atau surat kuasa pengelolaan venue. JPG, PNG, atau PDF · maks. 5 MB.'],
    ['type' => \App\Enums\VerificationDocType::Npwp, 'required' => false, 'hint' => 'NPWP pemilik atau usaha (opsional). PDF atau gambar · maks. 5 MB.'],
];
@endphp
<main id="konten">
<div class="auth-layout">
 <section class="auth-intro"><a class="brand" href="{{ url('/venue') }}"><span class="logo"></span><span><b>Pesta Kita</b><small>Portal penyedia</small></span></a><div><span class="eyebrow">Pemeriksaan penyedia</span><p class="auth-title">Keamanan dan kepercayaan dimulai dari verifikasi.</p><p>Tim Pesta Kita memeriksa data usaha dan dokumen sebelum venue tampil di katalog Klien.</p><ul class="auth-benefits"><li>Identitas pemilik / penanggung jawab (KTP)</li><li>Bukti kepemilikan / hak pengelolaan venue</li><li>NPWP opsional</li></ul></div><small>© 2026 Pesta Kita</small></section>
 <section class="auth-form">
  <div class="auth-heading"><span class="eyebrow">Verifikasi penyedia</span><h1>Verifikasi penyedia</h1><p class="sub">Lengkapi dokumen dan pantau keputusan Admin.</p></div>
  <x-subnav label="Profil & verifikasi" :items="[
    ['label' => 'Profil usaha', 'href' => route('venue.profil'), 'active' => request()->routeIs('venue.profil')],
    ['label' => 'Verifikasi dokumen', 'href' => route('venue.verifikasi'), 'active' => request()->routeIs('venue.verifikasi')],
]" />
@if(session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
  @error('verification')<div class="alert warn" role="alert">{{ $message }}</div>@enderror
  <section class="card"><div class="card-h"><div><h2>Status pemeriksaan</h2><p class="sub">{{ $profile?->business_name ?? 'Profil usaha' }} · Pemilik venue</p></div><span class="badge {{ $badge }}">{{ $statusLabel }}</span></div><div class="card-b">
   <ul class="timeline"><li class="done"><b>Registrasi diterima</b><small>Akun aktif; lengkapi dokumen untuk memulai pemeriksaan.</small></li><li class="{{ $status === \App\Enums\VerificationStatus::Terverifikasi ? 'done' : 'pending' }}"><b>Pemeriksaan dokumen</b><small>@if($profile?->verification_submitted_at) Diajukan {{ $profile->verification_submitted_at->format('d M Y · H:i') }} WIB @else Belum diajukan — unggah dokumen wajib lalu kirim pengajuan. @endif</small></li><li class="{{ $status === \App\Enums\VerificationStatus::Terverifikasi ? 'done' : '' }}"><b>Keputusan Admin</b><small>Setelah terverifikasi, venue Anda tayang di katalog Klien kecuali dinonaktifkan individual oleh Admin.</small></li></ul>
   @if($status === \App\Enums\VerificationStatus::Ditolak && $profile?->verification_note)<div class="note mt-12"><b>Catatan Admin:</b> {{ $profile->verification_note }}</div>@endif
  </div></section>
  <section class="card mt-20"><div class="card-h"><div><h2>Dokumen verifikasi</h2><p class="sub">Unggah dokumen yang jelas dan masih berlaku. Satu dokumen aktif per jenis; unggahan baru menggantikan dokumen lama.</p></div></div><div class="card-b">
   <div class="form-grid">
    @foreach($docTypes as $doc)
    @php($existing = $documents[$doc['type']->value] ?? null)
    <div class="field full"><label for="dok-{{ $doc['type']->value }}">{{ $doc['type']->label() }} @if($doc['required'])<span class="req">*</span>@else<span class="sub-t">(opsional)</span>@endif</label>
     @if($existing)<p class="hint">Dokumen aktif: <a href="{{ route('media.show', $existing) }}">{{ $existing->original_name ?? 'Lihat dokumen' }}</a>. Unggah baru untuk menggantikan.</p>@endif
     <form method="POST" action="{{ route('verifikasi.dokumen.store') }}" enctype="multipart/form-data">@csrf<input type="hidden" name="doc_type" value="{{ $doc['type']->value }}"><div class="upload-box"><span class="ic i-file" aria-hidden="true"></span><div class="grow"><b>{{ $existing ? 'Ganti dokumen' : 'Unggah dokumen' }}</b><small class="sub-t">{{ $doc['hint'] }}</small></div><input id="dok-{{ $doc['type']->value }}" name="file" type="file" accept=".jpg,.jpeg,.png,.pdf" required></div>@error('file')<p class="field-error" role="alert">{{ $message }}</p>@enderror @error('doc_type')<p class="field-error" role="alert">{{ $message }}</p>@enderror<div class="form-actions"><button class="btn btn-o" type="submit">{{ $existing ? 'Ganti dokumen' : 'Unggah' }}</button></div></form>
    </div>
    @endforeach
   </div>
   <div class="note"><b>Catatan:</b> Dokumen wajib harus lengkap sebelum pengajuan dikirim. Bukti rekening bukan persyaratan verifikasi. Admin akan memberi catatan jika diperlukan perbaikan.</div>
  </div></section>
  <section class="card mt-20"><div class="card-h"><h2>Pengajuan verifikasi</h2></div><div class="card-b">
   @if(in_array($status, [\App\Enums\VerificationStatus::Menunggu, \App\Enums\VerificationStatus::Ditolak], true))
   <form method="POST" action="{{ route('verifikasi.ajukan') }}">@csrf<div class="form-actions"><button class="btn" type="submit">{{ $status === \App\Enums\VerificationStatus::Ditolak ? 'Ajukan ulang verifikasi' : 'Kirim dokumen untuk diperiksa' }}</button><a class="btn btn-o" href="{{ url('/venue/profil') }}">Ubah data profil</a></div></form>
   <p class="hint mt-12">Pengajuan dapat dikirim selama status menunggu atau ditolak. Penolakan tidak menonaktifkan akun Anda.</p>
   @elseif($status === \App\Enums\VerificationStatus::Terverifikasi)
   <p class="hint">Penyedia Anda sudah terverifikasi. Venue tayang di katalog Klien kecuali dinonaktifkan individual oleh Admin.</p>
   @else
   <p class="hint">Status penyedia nonaktif. Hubungi Admin Pesta Kita untuk informasi lebih lanjut.</p>
   @endif
  </div></section>
 </section>
</div>
</main>
@endsection