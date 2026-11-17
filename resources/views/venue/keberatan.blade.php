@extends('layouts.venue')

@section('title', 'Keberatan saya · Panel Pemilik Venue Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ route('venue.beranda') }}">Beranda</a> / <a href="{{ route('venue.pesanan') }}">Pesanan</a> / Keberatan</div><h1>Keberatan saya</h1></div></div>
    <section class="card">
        <div class="table-wrap" role="region" tabindex="0" aria-label="Daftar keberatan">
            <table class="t-wide">
                <caption class="sr-only">Keberatan yang Anda ajukan</caption>
                <thead><tr><th scope="col">Pesanan</th><th scope="col">Tanggal acara</th><th scope="col">Alasan</th><th scope="col">Status</th></tr></thead>
                <tbody>
                @forelse ($objections as $objection)
                    <tr>
                        <td><a href="{{ route('venue.pesanan.detail', $objection->orderItem) }}">Pesanan #{{ $objection->order_item_id }}</a></td>
                        <td>{{ $objection->orderItem->event?->event_date?->translatedFormat('d M Y') }}</td>
                        <td>{{ $objection->reason }}</td>
                        <td>{{ ucfirst($objection->admin_decision ?? $objection->status) }}</td>
                    </tr>
                @empty
                    <tr><td class="hint" colspan="4">Anda belum mengajukan keberatan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($objections, 'links'))<div class="card-b">{{ $objections->links() }}</div>@endif
    </section>
</main>
@endsection
