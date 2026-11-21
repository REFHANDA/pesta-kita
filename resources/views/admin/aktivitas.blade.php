@extends('layouts.admin')

@section('title', 'Log aktivitas · Admin Pesta Kita')

@php
$logs = $logs ?? collect();
$actionLabels = \App\Http\Controllers\Admin\ActivityLogController::ACTION_LABELS;
$filter = $actionFilter ?? null;
$term = $term ?? '';
$totalCount = $total_count ?? $logs->count();

// Alias morph (Relation::enforceMorphMap) → nama modul yang dibaca Admin.
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

$actionLabel = fn ($action) => $actionLabels[$action] ?? ucwords(str_replace('_', ' ', (string) $action));

$statusLabel = fn ($status) => $status === null ? '—' : str_replace('_', ' ', (string) $status);

$subject = function ($log) use ($subjectModules) {
    $module = $subjectModules[$log->subject_type] ?? str_replace('_', ' ', (string) $log->subject_type);
    $name = $log->subject?->name ?? $log->subject?->business_name;

    return $name
        ? $module.' · '.$name
        : $module.' #'.$log->subject_id.' (subjek sudah dihapus)';
};
@endphp

@section('content')
<main class="content" id="konten" tabindex="-1">
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / Pemantauan / Log aktivitas</div><h1>Log aktivitas</h1><p class="sub">Jejak audit keputusan yang sudah dicatat sistem: verifikasi mitra, perubahan venue oleh Admin, dan keputusan pengajuan harga.</p></div></div>

@if(session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif

<p class="hint">Log bersifat hanya-tambah dan tidak dapat diubah.</p>

<section class="card">
    <div class="card-h"><h2>Entri tercatat</h2><span class="badge b-mute">{{ $totalCount }} total</span></div>

    <form class="toolbar" method="GET" action="{{ url('/admin/aktivitas') }}">
        <label class="sr-only" for="q">Cari aktor atau alasan</label>
        <input class="input" id="q" name="q" value="{{ $term }}" placeholder="Cari nama aktor, email, atau alasan…"/>
        <label class="sr-only" for="aksi">Jenis aktivitas</label>
        <select class="input" id="aksi" name="aksi">
            <option value="">Semua aktivitas</option>
            @foreach($actionLabels as $value => $label)
                <option value="{{ $value }}" @if($filter === $value) selected @endif>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn btn-o" type="submit">Terapkan</button>
        @if($filter !== null || $term !== '')<a class="btn btn-o" href="{{ url('/admin/aktivitas') }}">Reset filter</a>@endif
    </form>

    <div aria-label="Log aktivitas. Gulir horizontal untuk melihat seluruh kolom" class="table-wrap" role="region" tabindex="0">
        <table class="t-wide">
            <caption class="sr-only">Tabel: Log aktivitas</caption>
            <thead><tr><th scope="col">Waktu</th><th scope="col">Aktor</th><th scope="col">Aksi</th><th scope="col">Objek</th><th scope="col">Perubahan status</th><th class="col-aksi" scope="col">Aksi</th></tr></thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at?->translatedFormat('d M Y') ?? '—' }}<br/><small>{{ $log->created_at?->format('H:i:s') ?? '' }}</small></td>
                    <td>{{ $log->actor?->name ?? 'Sistem' }}@if($log->actor)<small class="sub-t">{{ $log->actor->email }}</small>@endif</td>
                    <td><span class="badge {{ $badge($log->action) }}">{{ $actionLabel($log->action) }}</span></td>
                    <td>{{ $subject($log) }}</td>
                    <td>{{ $statusLabel($log->from_status) }} → {{ $statusLabel($log->to_status) }}@if($log->reason)<br/><small>{{ $log->reason }}</small>@endif</td>
                    <td class="col-aksi"><a class="btn btn-o btn-sm" href="{{ route('admin.aktivitas.detail', $log) }}">Lihat detail</a></td>
                </tr>
            @empty
                <tr><td colspan="6"><p class="hint" style="padding:16px;margin:0">Belum ada log aktivitas tercatat. Entri muncul otomatis begitu Admin mencatat keputusan — misalnya menyetujui atau menolak verifikasi mitra dan pengajuan harga.</p></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($totalCount > $logs->count())
        <div class="note-box table-note"><span aria-hidden="true" class="ic i-info"></span><p>Ditampilkan {{ $logs->count() }} entri terbaru dari {{ $totalCount }} entri yang tersimpan.</p></div>
    @endif
</section>
</main>
@endsection
