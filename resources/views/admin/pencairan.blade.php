@extends('layouts.admin')

@section('title', 'Pencairan penyedia · Admin Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ route('admin.beranda') }}">Admin</a> / Keuangan / Pencairan</div><h1>Pencairan penyedia</h1></div></div>
    <p class="hint">Hak penyedia adalah harga final penuh; komisi platform ditagihkan kepada Klien. Setiap item hanya dapat dicatat cair satu kali.</p>
    <section class="card">
        <div class="card-h"><div><h2>Siap dicairkan</h2><p class="meta">Acara selesai, tagihan lunas, dan belum ada transfer tercatat.</p></div></div>
        <div class="table-wrap" role="region" tabindex="0" aria-label="Item siap dicairkan">
            <table class="t-wide">
                <caption class="sr-only">Item penyedia yang memenuhi syarat pencairan</caption>
                <thead><tr><th scope="col">Acara</th><th scope="col">Item</th><th scope="col">Penyedia</th><th scope="col" class="num">Hak penyedia</th><th scope="col"></th></tr></thead>
                <tbody>
                @forelse ($eligibleItems as $orderItem)
                    <tr>
                        <td>{{ $orderItem->event->name }}</td>
                        <td>{{ $orderItem->subject?->name ?? 'Item tidak tersedia' }}</td>
                        <td>{{ $orderItem->provider?->business_name ?? 'Penyedia tidak tersedia' }}</td>
                        <td class="num">Rp {{ number_format($orderItem->final_price, 0, ',', '.') }}</td>
                        <td><a class="btn btn-o btn-sm" href="{{ route('admin.pencairan.detail', $orderItem) }}">Tinjau dan catat</a></td>
                    </tr>
                @empty
                    <tr><td class="hint" colspan="5">Belum ada item yang siap dicairkan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-b">{{ $eligibleItems->links() }}</div>
    </section>
    <section class="card mt-16">
        <div class="card-h"><div><h2>Transfer tercatat</h2><p class="meta">Riwayat pencairan yang sudah dicatat Admin.</p></div></div>
        <div class="table-wrap" role="region" tabindex="0" aria-label="Riwayat transfer">
            <table class="t-wide">
                <caption class="sr-only">Riwayat pencairan penyedia</caption>
                <thead><tr><th scope="col">Tanggal transfer</th><th scope="col">Acara</th><th scope="col">Item</th><th scope="col">Penyedia</th><th scope="col" class="num">Nominal</th><th scope="col"></th></tr></thead>
                <tbody>
                @forelse ($payouts as $payout)
                    <tr>
                        <td>{{ $payout->transferred_at->translatedFormat('d M Y') }}</td>
                        <td>{{ $payout->orderItem->event->name }}</td>
                        <td>{{ $payout->orderItem->subject?->name ?? 'Item tidak tersedia' }}</td>
                        <td>{{ $payout->orderItem->provider?->business_name ?? 'Penyedia tidak tersedia' }}</td>
                        <td class="num">Rp {{ number_format($payout->amount, 0, ',', '.') }}</td>
                        <td><a class="btn btn-o btn-sm" href="{{ route('admin.pencairan.detail', $payout->order_item_id) }}">Detail</a></td>
                    </tr>
                @empty
                    <tr><td class="hint" colspan="6">Belum ada transfer yang dicatat.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-b">{{ $payouts->links() }}</div>
    </section>
</main>
@endsection
