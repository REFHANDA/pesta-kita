@extends('layouts.admin')

@section('title', 'Pengajuan harga · Admin Pesta Kita')

@php
$requests = $requests ?? collect();
$filter = $statusFilter ?? 'menunggu';
$rupiah = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
$badge = [
    \App\Enums\PriceChangeStatus::Menunggu->value => 'b-warn',
    \App\Enums\PriceChangeStatus::Disetujui->value => 'b-ok',
    \App\Enums\PriceChangeStatus::Ditolak->value => 'b-bad',
];
$label = [
    \App\Enums\PriceChangeStatus::Menunggu->value => 'Menunggu keputusan',
    \App\Enums\PriceChangeStatus::Disetujui->value => 'Disetujui',
    \App\Enums\PriceChangeStatus::Ditolak->value => 'Ditolak',
];
$describe = function ($change) {
    $subject = $change->subject;
    if ($subject instanceof \App\Models\CatalogItem) {
        return ['nama' => $subject->name, 'penyedia' => $subject->vendorProfile?->business_name ?? '—', 'jenis' => $subject->kind?->value === 'peralatan' ? 'Peralatan' : 'Layanan'];
    }
    if ($subject instanceof \App\Models\Venue) {
        return ['nama' => $subject->name, 'penyedia' => $subject->venueProfile?->business_name ?? '—', 'jenis' => 'Venue'];
    }
    return ['nama' => 'Subjek telah dihapus', 'penyedia' => '—', 'jenis' => '—'];
};
@endphp

@section('content')
<main class="content page-refined" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / Penyedia &amp; katalog / Pengajuan harga</div><h1>Pengajuan harga</h1><p class="sub">Tinjau perubahan harga penyedia. Persetujuan mengubah harga aktif katalog; selama menunggu harga lama tetap berlaku.</p></div></div>

    @if(session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
    @error('price_change')<div class="alert warn" role="alert">{{ $message }}</div>@enderror

    <section class="card workflow-card">
        <div aria-label="Navigasi status" class="tabs" role="navigation">
            <a href="{{ url('/admin/pengajuan-harga') }}" @if($filter === 'menunggu') aria-current="page" @endif>Menunggu keputusan <span class="n">{{ $waitingCount }}</span></a>
            <a href="{{ url('/admin/pengajuan-harga?status=disetujui') }}" @if($filter === 'disetujui') aria-current="page" @endif>Disetujui</a>
            <a href="{{ url('/admin/pengajuan-harga?status=ditolak') }}" @if($filter === 'ditolak') aria-current="page" @endif>Ditolak</a>
            <a href="{{ url('/admin/pengajuan-harga?status=semua') }}" @if($filter === null) aria-current="page" @endif>Semua</a>
        </div>

        @forelse($requests as $change)
            @php($info = $describe($change))
            <div class="item workflow-item">
                <div class="item-h">
                    <div><h2>{{ $info['nama'] }}</h2><p class="meta">{{ $info['penyedia'] }} · {{ $info['jenis'] }} · diajukan {{ $change->created_at->translatedFormat('d M Y') }}</p></div>
                    <span class="badge {{ $badge[$change->status->value] }}">{{ $label[$change->status->value] }}</span>
                </div>
                <div class="price-compare">
                    <div><small>Harga aktif (tetap berlaku)</small><b>{{ $rupiah($change->old_price) }}</b></div>
                    <span>→</span>
                    <div><small>Harga diajukan</small><b>{{ $rupiah($change->new_price) }}</b></div>
                </div>
                <p>Alasan: {{ $change->reason }}</p>
                @if($change->status === \App\Enums\PriceChangeStatus::Menunggu)
                <div class="toolbar">
                    <form method="POST" action="{{ route('admin.pengajuan-harga.decide', [$change, 'approve']) }}" onsubmit="return confirm('Setujui harga baru? Harga aktif katalog akan berubah.')">@csrf<button class="btn" type="submit">Setujui harga baru</button></form>
                    <a class="btn btn-o" href="{{ route('admin.pengajuan-harga.detail', $change) }}">Lihat &amp; tolak</a>
                </div>
                @else
                <div class="detail-list">
                    <div class="dl-row"><span>Keputusan oleh</span><strong>{{ $change->reviewer?->name ?? '—' }}</strong></div>
                    <div class="dl-row"><span>Waktu keputusan</span><strong>{{ $change->reviewed_at?->translatedFormat('d M Y H:i') ?? '—' }}</strong></div>
                    @if($change->decision_note)<div class="dl-row"><span>Catatan</span><strong>{{ $change->decision_note }}</strong></div>@endif
                </div>
                @endif
            </div>
        @empty
            <p class="hint" style="padding:16px">Tidak ada pengajuan harga berstatus {{ $filter ? str_replace('_', ' ', $filter) : 'ini' }}.</p>
        @endforelse
        <div class="alert">Harga lama tetap tampil ke klien sampai Admin menyetujui pengajuan. Pesanan yang sudah disetujui tidak berubah.</div>
    </section>
</main>
@endsection
