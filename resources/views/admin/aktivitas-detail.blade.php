@extends('layouts.admin')

@section('title', 'Detail log aktivitas · Admin Pesta Kita')

@php
// Halaman ini dipakai dua jalur: rute eksplisit /admin/aktivitas/{auditLog}
// mengirim $log, sedangkan tanpa entri
// tidak mengirim data — karena itu seluruh tampilan diberi null guard.
$log = $log ?? null;
$actionLabels = \App\Http\Controllers\Admin\ActivityLogController::ACTION_LABELS;

$subjectModules = [
    'vendor_profile' => 'Verifikasi vendor',
    'venue_profile' => 'Verifikasi pemilik venue',
    'venue' => 'Katalog venue',
    'catalog_item' => 'Katalog vendor',
    'price_change_request' => 'Pengajuan harga',
];

$badge = fn ($action) => match (true) {
    str_ends_with((string) $action, '_rejected') => 'b-bad',
    str_ends_with((string) $action, '_approved') => 'b-ok',
    default => 'b-mute',
};

$module = $log === null
    ? '—'
    : ($subjectModules[$log->subject_type] ?? str_replace('_', ' ', (string) $log->subject_type));

$subjectName = $log?->subject?->name ?? $log?->subject?->business_name;
@endphp

@section('content')
<main class="content detail-page page-refined" id="konten" tabindex="-1">
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / Pemantauan / <a href="{{ url('/admin/aktivitas') }}">Log aktivitas</a> / Detail aktivitas</div><h1>Detail log aktivitas</h1><p class="sub">Aktor, objek yang berubah, waktu, dan alasan keputusan sebagaimana tercatat di basis data.</p></div><a class="btn btn-o" href="{{ url('/admin/aktivitas') }}">Kembali</a></div>

@if($log === null)
    <p class="hint"><b>Belum ada entri yang dipilih.</b> Detail aktivitas dibaca per entri log. Buka daftar <a href="{{ url('/admin/aktivitas') }}">Log aktivitas</a> lalu pilih entri yang ingin diperiksa.</p>
@else
<div class="grid two"><section class="card"><div class="card-h"><h2>Informasi aktivitas</h2><span class="badge {{ $badge($log->action) }}">{{ $actionLabels[$log->action] ?? ucwords(str_replace('_', ' ', (string) $log->action)) }}</span></div><div class="card-b detail-kv"><p><span class="lbl">ID log</span><b>#{{ $log->id }}</b></p><p><span class="lbl">Waktu</span><b>{{ $log->created_at?->translatedFormat('d M Y H:i:s') ?? '—' }}</b></p><p><span class="lbl">Aktor</span><b>{{ $log->actor?->name ?? 'Sistem (tanpa akun)' }}</b>@if($log->actor)<small class="sub-t">{{ $log->actor->email }}</small>@endif</p><p><span class="lbl">Modul</span><b>{{ $module }}</b></p><p><span class="lbl">Objek</span><b>{{ $subjectName ?? $module.' #'.$log->subject_id }}</b>@if($subjectName)<small class="sub-t">{{ $module }} #{{ $log->subject_id }}</small>@endif</p></div></section>
<section class="card"><div class="card-h"><h2>Ringkasan perubahan</h2></div><div class="card-b"><div class="detail-kv"><p><span class="lbl">Status sebelum</span><b>{{ $log->from_status ? str_replace('_', ' ', $log->from_status) : '—' }}</b></p><p><span class="lbl">Status sesudah</span><b>{{ $log->to_status ? str_replace('_', ' ', $log->to_status) : '—' }}</b></p></div>@if($log->reason)<div class="alert">Alasan tercatat: {{ $log->reason }}</div>@else<div class="alert">Aksi ini dicatat tanpa alasan. Hanya penolakan yang mewajibkan alasan.</div>@endif</div></section></div>

<section class="card mt-20"><div class="card-h"><h2>Subjek terkait</h2></div><div class="card-b">
    @if($log->subject)
        <div class="detail-kv"><p><span class="lbl">Tipe entri</span><b>{{ $module }}</b></p><p><span class="lbl">Dibuat subjek</span><b>{{ $log->subject->created_at?->translatedFormat('d M Y') ?? '—' }}</b></p><p><span class="lbl">Perubahan subjek terakhir</span><b>{{ $log->subject->updated_at?->translatedFormat('d M Y H:i') ?? '—' }}</b></p></div>
        @if($log->subject instanceof \App\Models\PriceChangeRequest)
            <div class="detail-kv"><p><span class="lbl">Harga lama</span><b>Rp {{ number_format((int) $log->subject->old_price, 0, ',', '.') }}</b></p><p><span class="lbl">Harga diajukan</span><b>Rp {{ number_format((int) $log->subject->new_price, 0, ',', '.') }}</b></p></div>
        @endif
        @if($log->subject instanceof \App\Models\CatalogItem || $log->subject instanceof \App\Models\Venue)
            <div class="detail-kv"><p><span class="lbl">Harga aktif saat ini</span><b>Rp {{ number_format((int) ($log->subject->catalog_price ?? $log->subject->rental_price ?? 0), 0, ',', '.') }}</b></p></div>
        @endif
    @else
        <p class="hint">Entri merujuk <b>{{ $module }} #{{ $log->subject_id }}</b>, namun subjeknya sudah dihapus dari basis data. Log tetap tersimpan sebagai jejak audit dan tidak diubah.</p>
    @endif
</div></section>

<section class="card mt-20"><div class="card-h"><h2>Metadata</h2></div><div class="card-b detail-kv"><p><span class="lbl">Alamat IP</span><b>{{ $log->ip ?? 'Tidak tercatat' }}</b></p><p><span class="lbl">Diproses pada</span><b>{{ $log->created_at?->translatedFormat('d M Y H:i') ?? '—' }}</b></p><p><span class="lbl">Catatan audit</span><b>Log aktivitas bersifat append-only dan tidak dapat diubah melalui halaman ini.</b></p></div></section>
@endif
</main>
@endsection
