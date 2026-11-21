@extends('layouts.admin')

@section('title', 'Detail pengajuan harga · Admin Pesta Kita')

@php
$change = $change ?? null;
$profile = $profile ?? null;
$rupiah = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
$subject = $change?->subject;
$isCatalog = $subject instanceof \App\Models\CatalogItem;
$subjNama = $subject?->name ?? 'Subjek telah dihapus';
$subjJenis = $isCatalog ? ($subject->kind?->value === 'peralatan' ? 'Peralatan' : 'Layanan') : ($subject instanceof \App\Models\Venue ? 'Venue' : '—');
$delta = $change ? $change->new_price - $change->old_price : 0;
$persen = ($change && $change->old_price > 0) ? round(($delta / $change->old_price) * 100, 2) : null;
@endphp

@section('content')
<main class="content page-refined detail-page" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / <a href="{{ route('admin.pengajuan-harga') }}">Pengajuan harga</a> / Detail</div><h1>Detail pengajuan harga</h1><p class="meta">Tinjau perubahan harga sebelum diberlakukan pada katalog penyedia.</p></div><a class="btn btn-o" href="{{ route('admin.pengajuan-harga') }}">Kembali ke daftar</a></div>

    @if(session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
    @error('price_change')<div class="alert warn" role="alert">{{ $message }}</div>@enderror

    @if($change === null)
    <section class="card detail-card"><div class="card-body"><p>Pengajuan tidak ditemukan.</p><a class="btn" href="{{ route('admin.pengajuan-harga') }}">Buka antrean pengajuan harga</a></div></section>
    @else
    <div class="detail-grid"><div class="detail-stack">
        <section class="card detail-card"><div class="card-h"><div class="split-head"><div><h2>{{ $subjNama }}</h2><p class="meta">{{ $profile?->business_name ?? 'Penyedia' }} · {{ $subjJenis }} · ID pengajuan: {{ $change->id }}</p></div><span class="badge b-{{ $change->status->value === 'disetujui' ? 'ok' : ($change->status->value === 'ditolak' ? 'bad' : 'warn') }}">{{ ucfirst($change->status->value) }}</span></div></div>
            <div class="card-body"><div class="detail-kv">
                <div class="kv"><span class="k">Diajukan pada</span><span class="v">{{ $change->created_at->translatedFormat('d F Y, H:i') }}</span></div>
                <div class="kv"><span class="k">Diajukan oleh</span><span class="v">{{ $profile?->user?->name ?? 'Penyedia' }}</span></div>
                <div class="kv"><span class="k">Kategori</span><span class="v">{{ $subjJenis }}</span></div>
            </div>
            <div class="price-compare"><div><small>Harga aktif (tetap berlaku)</small><b>{{ $rupiah($change->old_price) }}</b></div><span>→</span><div><small>Harga diajukan</small><b>{{ $rupiah($change->new_price) }}</b></div></div>
            <div class="detail-kv"><div class="kv"><span class="k">Perubahan nominal</span><span class="v">{{ $delta >= 0 ? '+' : '−' }} {{ $rupiah(abs($delta)) }}</span></div>@if($persen !== null)<div class="kv"><span class="k">Persentase</span><span class="v">{{ $delta >= 0 ? '+' : '−' }}{{ number_format(abs($persen), 2, ',', '.') }}%</span></div>@endif</div>
            </div></section>

        <section class="card detail-card"><div class="card-h"><h2>Alasan perubahan harga</h2><p class="meta">Keterangan yang disampaikan penyedia.</p></div><div class="card-body"><p class="body-copy">{{ $change->reason }}</p><div class="note-box detail-note"><span class="ic i-info"></span><p>Harga lama tetap tampil ke klien sampai Admin menyetujui pengajuan. Pesanan yang sudah disetujui tidak berubah.</p></div></div></section>
    </div>

    <aside class="detail-side">
        <section class="card detail-card"><div class="card-h"><h2>Tindakan Admin</h2><p class="meta">Tetapkan keputusan pengajuan.</p></div><div class="card-body">
            @if($change->status === \App\Enums\PriceChangeStatus::Menunggu)
            <form method="POST" action="{{ route('admin.pengajuan-harga.decide', [$change, 'approve']) }}" onsubmit="return confirm('Setujui harga baru? Harga aktif katalog akan berubah.')">@csrf<div class="field"><label for="catatan-setuju">Catatan (opsional)</label><textarea class="input" id="catatan-setuju" name="reason" rows="2" placeholder="Pertimbangan persetujuan…"></textarea></div><button class="btn" type="submit">Setujui harga baru</button></form>
            <form class="mt-12" method="POST" action="{{ route('admin.pengajuan-harga.decide', [$change, 'reject']) }}">@csrf<div class="field"><label for="alasan-tolak">Alasan penolakan <span class="req">*</span></label><textarea class="input" id="alasan-tolak" name="reason" rows="3" required placeholder="Tulis alasan agar penyedia dapat meninjau kembali">{{ old('reason') }}</textarea>@error('reason')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div><button class="btn btn-danger" type="submit">Tolak pengajuan</button></form>
            <div class="alert"><span class="ic i-info"></span><p>Persetujuan hanya berlaku untuk item ini. Harga pada pesanan yang telah disetujui tetap mengikuti harga sebelumnya.</p></div>
            @else
            <div class="detail-list">
                <div class="dl-row"><span>Status</span><strong>{{ ucfirst($change->status->value) }}</strong></div>
                <div class="dl-row"><span>Keputusan oleh</span><strong>{{ $change->reviewer?->name ?? '—' }}</strong></div>
                <div class="dl-row"><span>Waktu keputusan</span><strong>{{ $change->reviewed_at?->translatedFormat('d M Y H:i') ?? '—' }}</strong></div>
                @if($change->decision_note)<div class="dl-row"><span>Catatan</span><strong>{{ $change->decision_note }}</strong></div>@endif
            </div>
            <p class="hint mt-12">Hanya pengajuan berstatus menunggu yang dapat diputuskan.</p>
            @endif
        </div></section>

        @if($profile)
        <section class="card detail-card"><div class="card-h"><h2>Informasi penyedia</h2></div><div class="card-body"><div class="detail-list">
            <div class="dl-row"><span>Nama usaha</span><strong>{{ $profile->business_name }}</strong></div>
            <div class="dl-row"><span>Status penyedia</span><strong class="ok-t">{{ ucfirst($profile->verification_status->value) }}</strong></div>
            <div class="dl-row"><span>Kontak</span><strong>{{ $profile->user?->email }}</strong></div>
        </div></div></section>
        @endif
    </aside></div>
    @endif
</main>
@endsection
