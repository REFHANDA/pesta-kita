@extends('layouts.admin')

@section('title', 'Review pesanan · Admin Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / Review pesanan</div>
            <h1>Review pesanan</h1>
            <p class="sub">Pesanan dari acara yang sudah diajukan klien. Acara draft belum perlu direview.</p>
        </div>
        <a class="btn btn-o" href="{{ url('/admin/pesanan?j=venue') }}">Pesanan venue</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success mb-16"><span class="ic i-check" aria-hidden="true"></span><span>{{ session('success') }}</span></div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger mb-16"><span class="ic i-info" aria-hidden="true"></span><span>{{ session('error') }}</span></div>
    @endif

    <p class="deadline">
        <span class="chip">SLA review 24 jam</span>
        {{ $counts['menunggu_review'] }} pesanan menunggu review.
        Pesanan yang melewati batas ditandai <span class="badge b-bad">Mendesak</span>.
    </p>

    <div class="alert">
        <span class="ic i-info" aria-hidden="true"></span>
        <span>Buka tiap pesanan untuk melihat objek, nilai, dan konsekuensinya sebelum menyetujui atau menolak.</span>
    </div>

    <section class="card">
        <x-subnav label="Status pesanan" :items="[
            ['label' => 'Menunggu review', 'href' => route('admin.pesanan', ['tab' => 'review']), 'active' => $activeTab === 'review', 'count' => $counts['menunggu_review']],
            ['label' => 'Berjalan', 'href' => route('admin.pesanan', ['tab' => 'berjalan']), 'active' => $activeTab === 'berjalan', 'count' => $counts['berjalan']],
            ['label' => 'Keberatan', 'href' => route('admin.pesanan', ['tab' => 'keberatan']), 'active' => $activeTab === 'keberatan', 'count' => $counts['keberatan']],
            ['label' => 'Riwayat', 'href' => route('admin.pesanan', ['tab' => 'riwayat']), 'active' => $activeTab === 'riwayat', 'count' => $counts['riwayat']],
        ]" />

        <form class="toolbar" method="GET" action="{{ url('/admin/pesanan') }}">
            @if($activeStatus && $activeStatus !== 'semua')
                <input type="hidden" name="status" value="{{ $activeStatus }}"/>
            @endif
            <label class="sr-only" for="q">Cari nama acara atau klien</label>
            <input class="input" id="q" name="q" value="{{ $search }}" placeholder="Cari nama acara atau klien…"/>
            <label class="sr-only" for="j">Jenis</label>
            <select aria-label="Jenis" class="input" id="j" name="j">
                <option value="">Semua jenis</option>
                <option value="layanan" @selected($type === 'layanan')>Layanan vendor</option>
                <option value="venue" @selected($type === 'venue')>Venue</option>
                <option value="peralatan" @selected($type === 'peralatan')>Peralatan</option>
            </select>
            <button class="btn btn-o" type="submit">Terapkan</button>
        </form>

        <div aria-label="Tabel pesanan" class="table-wrap" role="region" tabindex="0">
            <table class="t-wide">
                <caption class="sr-only">Pesanan dari acara yang sudah diajukan</caption>
                <thead>
                    <tr>
                        <th scope="col">Item &amp; penyedia</th>
                        <th scope="col">Acara &amp; klien</th>
                        <th scope="col">Tanggal</th>
                        <th class="num" scope="col">Harga penyedia</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orderItems as $item)
                        @php
                            $subjectName = $item->subject?->name ?? 'Item #'.$item->id;
                            $providerName = $item->provider?->business_name ?? $item->provider?->name ?? 'Penyedia';
                            $eventName = $item->event?->name ?? 'Acara #'.$item->event_id;
                            $clientName = $item->event?->client?->name ?? 'Klien';
                            $dateStr = $item->usage_date ? $item->usage_date->format('d M Y') : ($item->event?->event_date?->format('d M Y') ?? '-');
                            $price = $item->provider_price ?? $item->catalog_price_snapshot ?? 0;
                            $badgeClass = match($item->status->value) {
                                'menunggu_review' => 'badge b-warn',
                                'disetujui', 'terkonfirmasi', 'selesai' => 'badge b-ok',
                                'ditolak', 'dibatalkan' => 'badge b-bad',
                                'dialihkan' => 'badge b-info',
                                default => 'badge',
                            };
                            $isUrgent = $item->status === \App\Enums\OrderItemStatus::MenungguReview
                                && $item->review_due_at
                                && now()->greaterThan($item->review_due_at);
                        @endphp
                        <tr>
                            <td>
                                <a class="fw-7 no-ul" href="{{ route('admin.pesanan.show', $item) }}">
                                    {{ $subjectName }}
                                    @if($item->quantity > 1) ×{{ $item->quantity }} @endif
                                </a>
                                <span class="sub-t">{{ $providerName }}</span>
                            </td>
                            <td>
                                {{ $eventName }}
                                <span class="sub-t">{{ $clientName }}</span>
                            </td>
                            <td>{{ $dateStr }}</td>
                            <td class="num">
                                Rp {{ number_format($item->lineTotal(), 0, ',', '.') }}
                                @if($item->quantity > 1)
                                    <span class="sub-t">@ Rp {{ number_format($price, 0, ',', '.') }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="{{ $badgeClass }}">{{ str_replace('_', ' ', ucfirst($item->status->value)) }}</span>
                                @if($isUrgent)
                                    <span class="badge b-bad">Mendesak</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center p-16">Tidak ada pesanan pada filter ini.</td>
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
