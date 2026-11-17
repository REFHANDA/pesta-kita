@extends('layouts.admin')

@section('title', 'Peninjauan penawaran · Admin Pesta Kita')

@php
$rupiah = fn ($amount) => 'Rp '.number_format((int) $amount, 0, ',', '.');
$statusBadge = fn ($status) => match ($status) {
    \App\Enums\QuoteRequestStatus::Diajukan => ['b-warn', 'Menunggu usulan'],
    \App\Enums\QuoteRequestStatus::DiusulkanPenyedia => ['b-info', 'Menunggu keputusan'],
    \App\Enums\QuoteRequestStatus::DisetujuiAdmin => ['b-ok', 'Disetujui'],
    \App\Enums\QuoteRequestStatus::DitolakAdmin => ['b-bad', 'Ditolak'],
};
$providerName = fn ($subject) => match (true) {
    $subject instanceof \App\Models\CatalogItem => $subject->vendorProfile?->business_name,
    $subject instanceof \App\Models\VenueRoom => $subject->venue?->venueProfile?->business_name,
    default => null,
};
@endphp

@section('content')
<main class="content page-refined" id="konten" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / Permintaan &amp; penawaran</div>
            <h1>Peninjauan penawaran</h1>
            <p class="meta">Tinjau proposal harga penyedia dan catat keputusan final untuk setiap permintaan.</p>
        </div>
    </div>

    <section class="card workflow-card">
        <div aria-label="Navigasi status" class="tabs" role="navigation">
            @foreach(['menunggu' => 'Menunggu keputusan', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'semua' => 'Semua'] as $filter => $label)
            <a href="{{ route('admin.penawaran', ['status' => $filter]) }}" @if($statusFilter === $filter) aria-current="page" @endif>
                {{ $label }}@if($filter === 'menunggu') <span class="n">{{ $waitingCount }}</span>@endif
            </a>
            @endforeach
        </div>

        @forelse($requests as $quoteRequest)
        @php
        $proposal = $quoteRequest->proposals->first();
        [$badgeClass, $badgeLabel] = $statusBadge($quoteRequest->status);
        @endphp
        <article class="item workflow-item">
            <div class="item-h">
                <div>
                    <h2>{{ $quoteRequest->subject?->name ?? 'Subjek sudah tidak tersedia' }}</h2>
                    <p class="meta">
                        {{ $providerName($quoteRequest->subject) ?? 'Penyedia tidak tersedia' }}
                        · Permintaan #{{ $quoteRequest->id }}
                        · {{ $quoteRequest->event_date?->translatedFormat('d M Y') ?? 'Tanggal tidak tersedia' }}
                        · {{ $quoteRequest->created_at?->translatedFormat('d M Y') ?? '—' }}
                    </p>
                </div>
                <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
            </div>
            <div class="price-compare">
                <div><small>Harga katalog (referensi)</small><b>{{ $rupiah($quoteRequest->catalog_price_snapshot) }}</b></div>
                <span>→</span>
                <div><small>Harga diajukan penyedia</small><b>{{ $proposal ? $rupiah($proposal->proposed_amount) : 'Belum ada usulan' }}</b></div>
            </div>
            @if($proposal?->notes)
            <p>{{ $proposal->notes }}</p>
            @elseif($quoteRequest->notes)
            <p>Kebutuhan Klien: {{ $quoteRequest->notes }}</p>
            @endif
            <div class="toolbar">
                <a class="btn btn-o" href="{{ route('admin.penawaran.detail', $quoteRequest) }}">Lihat detail</a>
            </div>
        </article>
        @empty
        <div class="item workflow-item"><p class="m-0">Belum ada permintaan pada status ini.</p></div>
        @endforelse

        {{ $requests->links() }}

        <div class="alert"><span class="ic i-info" aria-hidden="true"></span><p>Harga katalog, harga usulan penyedia, dan harga final adalah nilai yang berbeda. Persetujuan mengambil nominal proposal terakhir yang tersimpan di server.</p></div>
    </section>
</main>
@endsection
