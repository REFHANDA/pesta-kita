@extends('layouts.vendor')

@section('title', 'Rundown acara · Panel Vendor Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ route('vendor.beranda') }}">Beranda</a> / Rundown acara</div><h1>Rundown acara</h1><p class="sub">Kelola segmen layanan untuk acara yang sudah terkonfirmasi.</p></div></div>
    <section class="card">
        <div class="table-wrap" role="region" tabindex="0" aria-label="Acara dengan pesanan Vendor terkonfirmasi">
            <table class="t-wide">
                <caption class="sr-only">Acara yang memiliki pesanan layanan Vendor terkonfirmasi</caption>
                <thead><tr><th scope="col">Acara</th><th scope="col">Tanggal</th><th scope="col">Layanan Anda</th><th scope="col">Segmen Anda</th><th scope="col"></th></tr></thead>
                <tbody>
                @forelse ($events as $event)
                    @php
                        $segments = $event->orderItems->sum(fn ($orderItem) => $orderItem->rundownItems->count());
                        $services = $event->orderItems->map(fn ($orderItem) => $orderItem->subject?->name)->filter()->join(', ');
                    @endphp
                    <tr>
                        <td>{{ $event->name }}</td>
                        <td>{{ $event->event_date?->translatedFormat('d M Y') }}</td>
                        <td>{{ $services ?: '—' }}</td>
                        <td>{{ $segments }}</td>
                        <td><a class="btn btn-o btn-sm" href="{{ route('vendor.rundown', $event) }}">Buka rundown</a></td>
                    </tr>
                @empty
                    <tr><td class="hint" colspan="5">Belum ada acara dengan pesanan layanan terkonfirmasi.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-b">{{ $events->links() }}</div>
    </section>
</main>
@endsection
