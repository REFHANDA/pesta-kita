@extends('layouts.admin')

@section('title', 'Pantau acara · Admin Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ route('admin.beranda') }}">Admin</a> / Pemantauan / Pantau acara</div><h1>Pantau acara</h1></div></div>
    <section class="card">
        <div class="table-wrap" role="region" tabindex="0" aria-label="Daftar acara">
            <table class="t-wide">
                <caption class="sr-only">Acara yang sedang berjalan atau telah selesai</caption>
                <thead><tr><th scope="col">Acara</th><th scope="col">Klien</th><th scope="col">Tanggal</th><th scope="col">Pesanan</th><th scope="col">Tagihan terbaru</th><th scope="col">Status</th><th scope="col"></th></tr></thead>
                <tbody>
                @forelse ($events as $event)
                    @php
                        $invoice = $event->latestInvoice;
                        $label = str_replace('_', ' ', $event->status->value);
                    @endphp
                    <tr>
                        <td>{{ $event->name }}</td>
                        <td>{{ $event->client->name }}</td>
                        <td>{{ $event->event_date?->translatedFormat('d M Y') }}</td>
                        <td>{{ $event->order_items_count ?? $event->orderItems()->count() }}</td>
                        <td>{{ $invoice ? ucfirst(str_replace('_', ' ', $invoice->status)).' · v'.$invoice->version : 'Belum diterbitkan' }}</td>
                        <td>{{ ucfirst($label) }}</td>
                        <td><a class="btn btn-o btn-sm" href="{{ route('admin.acara.detail', $event) }}">Pantau</a></td>
                    </tr>
                @empty
                    <tr><td class="hint" colspan="7">Belum ada acara untuk dipantau.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-b">{{ $events->links() }}</div>
    </section>
</main>
@endsection
