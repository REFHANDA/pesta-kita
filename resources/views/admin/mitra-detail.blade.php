@extends('layouts.admin')

@section('title', 'Detail penyedia · Admin Pesta Kita')

@section('content')
@php
$profile = $profile ?? null;
$type = $type ?? null;
$documents = $documents ?? collect();
$subjects = $subjects ?? collect();
$isVendor = $type === 'vendor_profile';
$status = $profile?->verification_status;
$badge = match ($status) {
    \App\Enums\VerificationStatus::Terverifikasi => 'b-ok',
    \App\Enums\VerificationStatus::Ditolak => 'b-bad',
    \App\Enums\VerificationStatus::Nonaktif => 'b-mute',
    default => 'b-warn',
};
$docTypes = $isVendor
    ? [\App\Enums\VerificationDocType::Ktp, \App\Enums\VerificationDocType::Nib, \App\Enums\VerificationDocType::Npwp]
    : [\App\Enums\VerificationDocType::Ktp, \App\Enums\VerificationDocType::BuktiVenue, \App\Enums\VerificationDocType::Npwp];
@endphp
<main class="content page-refined" id="konten" tabindex="-1">
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / <a href="{{ route('admin.penyedia.index') }}">Verifikasi penyedia</a> / Detail penyedia</div><h1>Detail penyedia</h1></div><a class="btn btn-o" href="{{ route('admin.penyedia.index') }}">Kembali ke antrean</a></div>
@if(session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
@error('verification')<div class="alert warn" role="alert">{{ $message }}</div>@enderror
@if($profile === null)
<section class="card detail-card"><div class="card-body"><p>Pilih penyedia dari antrean verifikasi untuk meninjau dokumen dan memutus pengajuan.</p><a class="btn" href="{{ route('admin.penyedia.index') }}">Buka antrean verifikasi</a></div></section>
@else
<div class="detail-grid"><div class="detail-stack">
<section class="card detail-card"><div class="card-body"><div class="profile-head"><span class="avatar">{{ strtoupper(substr($profile->business_name ?? '?', 0, 1)) }}</span><div><h2>{{ $profile->business_name }}</h2><p>{{ $isVendor ? 'Vendor' : 'Pemilik venue' }} · {{ $profile->category }} · ID profil: {{ $profile->id }}</p><div class="mt-8"><span class="badge {{ $badge }}">{{ ucfirst($status->value) }}</span></div></div></div></div></section>
<section class="card detail-card"><div class="card-h"><h2>Informasi usaha</h2><p class="meta">Identitas dan kontak penyedia (terlihat hanya oleh Admin).</p></div><div class="card-body"><div class="detail-kv"><div class="kv"><span class="k">Nama usaha</span><span class="v">{{ $profile->business_name }}</span></div><div class="kv"><span class="k">Penanggung jawab</span><span class="v">{{ $profile->pic_name ?? '—' }}</span></div><div class="kv"><span class="k">Email</span><span class="v">{{ $profile->user?->email }}</span></div><div class="kv"><span class="k">Nomor telepon</span><span class="v">{{ $profile->user?->phone ?? '—' }}</span></div><div class="kv"><span class="k">Kategori</span><span class="v">{{ $profile->category ?? '—' }}</span></div><div class="kv"><span class="k">Area layanan</span><span class="v">{{ $profile->service_area ?? '—' }}</span></div><div class="kv"><span class="k">Alamat usaha</span><span class="v">{{ $profile->address ?? '—' }}</span></div><div class="kv"><span class="k">Waktu pengajuan</span><span class="v">{{ $profile->verification_submitted_at?->format('d M Y H:i') ?? 'Belum diajukan' }}</span></div></div></div></section>
<section class="card detail-card"><div class="card-h"><h2>Dokumen verifikasi</h2><p class="meta">Dokumen privat; hanya Admin dan pemilik yang dapat membuka. Bukti rekening bukan persyaratan verifikasi.</p></div><div class="card-body"><div class="detail-list">
@foreach($docTypes as $docType)
@php($doc = $documents[$docType->value] ?? null)
<div class="dl-row"><span>{{ $docType->label() }}</span>@if($doc)<strong><a href="{{ route('media.show', $doc) }}">{{ $doc->original_name ?? 'Lihat dokumen' }}</a></strong>@else<strong class="sub-t">Belum diunggah</strong>@endif</div>
@endforeach
</div><div class="note-box detail-note"><span class="ic i-info"></span><p>Keputusan verifikasi tercatat pada log aktivitas beserta reviewer dan alasan.</p></div></div></section>
<section class="card detail-card"><div class="card-h"><h2>{{ $isVendor ? 'Katalog item' : 'Venue penyedia' }}</h2><p class="meta">Subjek usaha yang mengikuti status verifikasi profil.</p></div><div aria-label="Daftar subjek usaha. Gulir horizontal untuk melihat seluruh kolom" class="table-wrap" role="region" tabindex="0"><table class="detail-table"><caption class="sr-only">Tabel: Subjek usaha penyedia</caption><thead><tr><th scope="col">Nama</th><th scope="col" class="num">Harga</th><th scope="col">Status</th>@unless($isVendor)<th scope="col">Aksi</th>@endunless</tr></thead><tbody>
@forelse($subjects as $subject)
<tr><td><b>{{ $subject->name }}</b></td><td class="num">Rp {{ number_format($isVendor ? $subject->catalog_price : $subject->rental_price, 0, ',', '.') }}</td><td><span class="badge {{ ($isVendor ? $subject->status->value : $subject->verification_status->value) === 'aktif' || ($isVendor ? $subject->status->value : $subject->verification_status->value) === 'terverifikasi' ? 'b-ok' : 'b-mute' }}">{{ $isVendor ? $subject->status->value : $subject->verification_status->value }}</span></td>@unless($isVendor)<td><a class="btn btn-o btn-sm" href="{{ route('admin.venue.edit', $subject) }}">Kelola</a></td>@endunless</tr>
@empty
<tr><td colspan="{{ $isVendor ? 3 : 4 }}">Belum ada {{ $isVendor ? 'item katalog' : 'venue' }}.</td></tr>
@endforelse
</tbody></table></div></section>
</div><aside class="detail-side">
<section class="card detail-card"><div class="card-h"><h2>Keputusan verifikasi</h2></div><div class="card-body">
@if($status === \App\Enums\VerificationStatus::Menunggu)
<form method="POST" action="{{ route('admin.penyedia.verifikasi.approve', ['type' => $type, 'id' => $profile->id]) }}" onsubmit="return confirm('Setujui penyedia ini? Foto menjadi publik dan katalog tayang.')">@csrf<button class="btn" type="submit">Setujui penyedia</button></form>
<form class="mt-12" method="POST" action="{{ route('admin.penyedia.verifikasi.reject', ['type' => $type, 'id' => $profile->id]) }}">@csrf<div class="field"><label for="alasan-tolak">Alasan penolakan <span class="req">*</span></label><textarea class="input" id="alasan-tolak" name="reason" rows="3" required placeholder="Tulis alasan agar penyedia dapat memperbaiki">{{ old('reason') }}</textarea>@error('reason')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div><button class="btn btn-o" type="submit">Tolak pengajuan</button></form>
<p class="hint mt-12">Penolakan tidak menonaktifkan akun; penyedia dapat memperbaiki dan mengajukan ulang. Approve mempublikasikan foto dan menayangkan {{ $isVendor ? 'item aktif' : 'venue (kecuali yang dinonaktifkan individual)' }}.</p>
@else
<div class="detail-list"><div class="dl-row"><span>Status</span><strong>{{ ucfirst($status->value) }}</strong></div><div class="dl-row"><span>Reviewer</span><strong>{{ $profile->reviewer?->name ?? '—' }}</strong></div><div class="dl-row"><span>Waktu keputusan</span><strong>{{ $profile->verified_at?->format('d M Y H:i') ?? '—' }}</strong></div>@if($profile->verification_note)<div class="dl-row"><span>Catatan</span><strong>{{ $profile->verification_note }}</strong></div>@endif</div>
<p class="hint mt-12">Hanya pengajuan berstatus menunggu yang dapat diputuskan.</p>
@endif
</div></section>
<section class="card detail-card"><div class="card-h"><h2>Catatan lingkup</h2></div><div class="card-body"><p class="hint">Data rekening pencairan dan ringkasan performa berada di luar lingkup BE-3 dan belum ditampilkan dari server.</p></div></section>
</aside></div>
@endif
</main>
@endsection