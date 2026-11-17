@extends('layouts.venue')

@section('title', 'Riwayat pencairan · Panel Pemilik Venue Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ route('venue.beranda') }}">Beranda</a> / Riwayat pencairan</div><h1>Riwayat pencairan</h1></div></div>
    <p class="hint">Daftar ini hanya menampilkan transfer yang telah dicatat Admin untuk venue milik Anda. Hak penyedia mengikuti harga final pesanan.</p>
    <section class="card">
        <div class="table-wrap" role="region" tabindex="0" aria-label="Riwayat pencairan">
            <table class="t-wide">
                <caption class="sr-only">Riwayat pencairan untuk pesanan venue</caption>
                <thead><tr><th scope="col">Tanggal transfer</th><th scope="col">Acara</th><th scope="col">Item venue</th><th scope="col" class="num">Nominal</th><th scope="col"></th></tr></thead>
                <tbody>
                @forelse ($payouts as $payout)
                    <tr>
                        <td>{{ $payout->transferred_at->translatedFormat('d M Y') }}</td>
                        <td>{{ $payout->orderItem->event->name }}</td>
                        <td>{{ $payout->orderItem->subject?->name ?? 'Item tidak tersedia' }}</td>
                        <td class="num">Rp {{ number_format($payout->amount, 0, ',', '.') }}</td>
                        <td><a class="btn btn-o btn-sm" href="{{ route('venue.pencairan.detail', $payout) }}">Detail</a></td>
                    </tr>
                @empty
                    <tr><td class="hint" colspan="5">Belum ada pencairan yang dicatat untuk venue Anda.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-b">{{ $payouts->links() }}</div>
    </section>
</main>
@endsection
