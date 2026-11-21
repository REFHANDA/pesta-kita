@extends('layouts.venue')

@section('title', 'Pesanan · Panel Pemilik Venue Pesta Kita')

@section('content')
<main id="konten" class="content" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/venue') }}">Beranda</a> / Pesanan</div>
            <h1>Pesanan Venue</h1>
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
        <x-subnav label="Status pesanan" :items="[
            ['label' => 'Aktif', 'href' => route('venue.pesanan'), 'active' => ! in_array($statusTab, ['riwayat', 'keberatan'], true)],
            ['label' => 'Riwayat', 'href' => route('venue.pesanan', ['status' => 'riwayat']), 'active' => $statusTab === 'riwayat'],
            ['label' => 'Keberatan 24 jam', 'href' => route('venue.pesanan', ['status' => 'keberatan']), 'active' => $statusTab === 'keberatan'],
        ]" />

        <div class="table-wrap" role="region" tabindex="0" aria-label="Daftar pesanan venue">
            <table class="t-wide">
                <caption class="sr-only">Tabel: Daftar pesanan venue</caption>
                <thead>
                    <tr>
                        <th scope="col">Ruangan</th>
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
                            $roomName = $item->subject?->name ?? 'Ruangan #'.$item->id;
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
                                <a href="{{ route('venue.pesanan.show', $item) }}">
                                    <b>{{ $roomName }}</b>
                                </a>
                            </td>
                            <td>{{ $eventLabel }}</td>
                            <td>{{ $dateStr }}</td>
                            <td class="num">{{ $item->event?->guest_count ?? '-' }}</td>
                            <td class="num">Rp {{ number_format($price, 0, ',', '.') }}</td>
                            <td><span class="{{ $badgeClass }}">{{ str_replace('_', ' ', ucfirst($item->status->value)) }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center p-16">Tidak ada pesanan venue pada daftar ini.</td>
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
