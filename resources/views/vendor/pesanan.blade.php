@extends('layouts.vendor')

@section('title', 'Pesanan · Panel Vendor Pesta Kita')

@section('content')
<main id="konten" class="content" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/vendor') }}">Beranda</a> / Pesanan</div>
            <h1>Pesanan</h1>
            <p class="sub">Nama klien tidak ditampilkan; komunikasi lewat Pesta Kita.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success mb-16"><span class="ic i-check" aria-hidden="true"></span><span>{{ session('success') }}</span></div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger mb-16"><span class="ic i-info" aria-hidden="true"></span><span>{{ session('error') }}</span></div>
    @endif

    <section class="card">
        <div class="tabs" aria-label="Status pesanan">
            <a href="{{ url('/vendor/pesanan') }}" @if($statusTab !== 'riwayat') aria-current="page" @endif>Aktif</a>
            <a href="{{ url('/vendor/pesanan') }}?status=riwayat" @if($statusTab === 'riwayat') aria-current="page" @endif>Riwayat</a>
        </div>

        <div class="table-wrap" role="region" tabindex="0" aria-label="Daftar pesanan">
            <table class="t-wide">
                <caption class="sr-only">Tabel: Daftar pesanan</caption>
                <thead>
                    <tr>
                        <th scope="col">Pesanan</th>
                        <th scope="col">Acara</th>
                        <th scope="col">Tanggal</th>
                        <th scope="col" class="num">Tamu</th>
                        <th scope="col" class="num">Hak Anda</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orderItems as $item)
                        @php
                            $subjectName = $item->subject?->name ?? 'Layanan #'.$item->id;
                            $eventLabel = 'Acara #'.$item->event_id;
                            $dateStr = $item->usage_date ? $item->usage_date->format('D, d M Y') : ($item->event?->event_date?->format('D, d M Y') ?? '-');
                            $price = $item->provider_price ?? $item->catalog_price_snapshot ?? 0;
                            $badgeClass = match($item->status->value) {
                                'menunggu_review' => 'badge b-warn',
                                'disetujui', 'terkonfirmasi', 'selesai' => 'badge b-ok',
                                'ditolak', 'dibatalkan' => 'badge b-bad',
                                'dialihkan' => 'badge b-info',
                                default => 'badge',
                            };
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('vendor.pesanan.show', $item) }}">
                                    <b>{{ $subjectName }}</b>
                                    @if($item->quantity > 1) ×{{ $item->quantity }} @endif
                                </a>
                            </td>
                            <td>{{ $eventLabel }}</td>
                            <td>{{ $dateStr }}</td>
                            <td class="num">{{ $item->event?->guest_count ?? '-' }}</td>
                            <td class="num">
                                Rp {{ number_format($item->lineTotal(), 0, ',', '.') }}
                                @if($item->quantity > 1)
                                    <span class="sub-t">@ Rp {{ number_format($price, 0, ',', '.') }}</span>
                                @endif
                            </td>
                            <td><span class="{{ $badgeClass }}">{{ str_replace('_', ' ', ucfirst($item->status->value)) }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center p-16">Tidak ada pesanan pada daftar ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orderItems->hasPages())
            <div class="pager">
                <span>Menampilkan {{ $orderItems->firstItem() }}–{{ $orderItems->lastItem() }} dari {{ $orderItems->total() }} pesanan</span>
                {{ $orderItems->links() }}
            </div>
        @else
            <div class="pager">
                <span>Menampilkan {{ $orderItems->total() }} pesanan</span>
            </div>
        @endif
    </section>
</main>
@endsection
