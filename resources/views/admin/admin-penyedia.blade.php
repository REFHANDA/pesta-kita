@extends('layouts.admin')

@section('title', 'Registrasi & verifikasi penyedia · Panel Admin Pesta Kita')

@section('content')
@php
$vendors = $vendors ?? collect();
$venues = $venues ?? collect();
$rows = $vendors->map(fn ($p) => ['profile' => $p, 'type' => 'vendor_profile', 'label' => 'Vendor', 'required' => ['ktp']])
    ->concat($venues->map(fn ($p) => ['profile' => $p, 'type' => 'venue_profile', 'label' => 'Pemilik venue', 'required' => ['ktp', 'bukti_venue']]));
@endphp
<main id="konten" class="content" tabindex="-1"><div class="page-head"><div><div class="crumbs">Manajemen / Penyedia / Registrasi</div><h1>Registrasi & verifikasi penyedia</h1><p class="sub">Tinjau profil usaha, identitas penanggung jawab, dan dokumen sebelum penyedia tampil di katalog. Keputusan verifikasi tidak menonaktifkan akun penyedia.</p></div></div>
@if(session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
@error('verification')<div class="alert warn" role="alert">{{ $message }}</div>@enderror
<div class="grid stats admin-stats"><article class="card stat"><span class="lbl">Menunggu verifikasi</span><span class="val">{{ $rows->count() }}</span><span class="badge b-warn">Perlu ditinjau</span></article><article class="card stat"><span class="lbl">Vendor</span><span class="val">{{ $vendors->count() }}</span><span class="hint">Pengajuan menunggu</span></article><article class="card stat"><span class="lbl">Pemilik venue</span><span class="val">{{ $venues->count() }}</span><span class="hint">Pengajuan menunggu</span></article></div>
<section class="card">
<div class="table-wrap" role="region" tabindex="0" aria-label="Antrean verifikasi penyedia. Gulir horizontal untuk melihat seluruh kolom"><table class="admin-table t-wide"><caption class="sr-only">Tabel: Antrean verifikasi penyedia</caption><thead><tr><th scope="col">Penyedia</th><th scope="col">Jenis</th><th scope="col">Tanggal pengajuan</th><th scope="col">Dokumen wajib</th><th scope="col">Status</th><th class="col-aksi" scope="col">Aksi</th></tr></thead><tbody>
@forelse($rows as $row)
@php
$profile = $row['profile'];
$uploaded = $profile->media()->dokumenVerifikasi()->pluck('doc_type')->map(fn ($t) => $t->value)->all();
$complete = count(array_intersect($row['required'], $uploaded));
@endphp
<tr><td><div class="who"><span class="avatar">{{ strtoupper(substr($profile->business_name ?? '?', 0, 1)) }}</span><div><b>{{ $profile->business_name }}</b><small>{{ $profile->user?->email }}</small></div></div></td><td>{{ $row['label'] }}</td><td>{{ $profile->verification_submitted_at?->format('d M Y H:i') ?? 'Belum diajukan' }}</td><td><span class="badge {{ $complete === count($row['required']) ? 'b-ok' : 'b-warn' }}">{{ $complete }}/{{ count($row['required']) }} lengkap</span></td><td><span class="badge b-warn">Menunggu</span></td><td class="col-aksi"><a class="btn btn-sm" href="{{ route('admin.penyedia.detail', ['type' => $row['type'], 'id' => $profile->id]) }}">Tinjau</a></td></tr>
@empty
<tr><td colspan="6">Tidak ada pengajuan verifikasi yang menunggu.</td></tr>
@endforelse
</tbody></table></div><p class="hint mt-12">Antrean diurutkan berdasarkan waktu pengajuan. Detail dokumen dan keputusan tersedia pada halaman Tinjau.</p></section></main>
@endsection