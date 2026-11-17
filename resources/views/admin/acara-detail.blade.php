@extends('layouts.admin')

@section('title', 'Detail acara · Admin Pesta Kita')

@php
    $invoice = $event->latestInvoice;
    $canIssue = in_array($event->status, [\App\Enums\EventStatus::Diajukan, \App\Enums\EventStatus::MenungguPembayaran], true)
        && ($invoice === null
            || $invoice->status === 'kedaluwarsa'
            || ($invoice->status === 'terbit'
                && ! $invoice->due_at->isFuture()
                && $invoice->latestPayment?->status !== 'menunggu_verifikasi'));
    $canComplete = $event->status === \App\Enums\EventStatus::Terkonfirmasi
        && $event->event_date?->isPast()
        && ! $event->event_date?->isToday();
    $statusLabel = str_replace('_', ' ', $event->status->value);
@endphp

@section('content')
<main class="content event-detail-page" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ route('admin.beranda') }}">Admin</a> / Pemantauan / <a href="{{ route('admin.acara') }}">Pantau acara</a> / Detail</div><h1>{{ $event->name }}</h1><p class="sub">Acara #{{ $event->id }} · {{ $event->client->name }}</p></div><a class="btn btn-o" href="{{ route('admin.acara') }}">Kembali</a></div>
    @if (session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
    @error('event')<div class="alert warn" role="alert">{{ $message }}</div>@enderror
    <section class="card">
        <div class="card-h"><h2>Ringkasan acara</h2><span class="badge b-warn">{{ ucfirst($statusLabel) }}</span></div>
        <div class="card-b">
            <dl class="dl">
                <div><dt>Tanggal</dt><dd>{{ $event->event_date?->translatedFormat('l, d F Y') }}</dd></div>
                <div><dt>Jumlah tamu</dt><dd>{{ number_format($event->guest_count) }}</dd></div>
                <div><dt>Tema</dt><dd>{{ $event->theme ?: 'Belum diisi' }}</dd></div>
                @if ($event->closed_at)<div><dt>Ditutup</dt><dd>{{ $event->closed_at->translatedFormat('d M Y H:i') }}</dd></div>@endif
            </dl>
            @if ($event->description)<p class="mt-16">{{ $event->description }}</p>@endif
        </div>
    </section>
    <section class="card mt-16">
        <div class="card-h"><div><h2>Item pesanan</h2><p class="meta">Item ditolak tidak dimasukkan ke tagihan.</p></div></div>
        <div class="table-wrap" role="region" tabindex="0" aria-label="Item pesanan acara">
            <table class="t-wide">
                <caption class="sr-only">Item pesanan acara</caption>
                <thead><tr><th scope="col">Item</th><th scope="col" class="num">Harga final</th><th scope="col">Status</th><th scope="col">Keberatan</th></tr></thead>
                <tbody>
                @forelse ($event->orderItems as $orderItem)
                    @php
                        $subject = $orderItem->subject;
                        $status = str_replace('_', ' ', $orderItem->status->value);
                        $openObjections = $orderItem->objections->where('status', 'diajukan')->count();
                    @endphp
                    <tr>
                        <td><a href="{{ route('admin.pesanan.detail', $orderItem) }}">{{ $subject?->name ?? 'Item tidak tersedia' }}</a></td>
                        <td class="num">{{ $orderItem->final_price === null ? '—' : 'Rp '.number_format($orderItem->final_price, 0, ',', '.') }}</td>
                        <td>{{ ucfirst($status) }}</td>
                        <td>{{ $openObjections }}</td>
                    </tr>
                @empty
                    <tr><td class="hint" colspan="4">Belum ada item pesanan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <section class="card mt-16">
        <div class="card-h"><div><h2>Rundown</h2><p class="meta">Monitoring saja; Admin tidak dapat mengubah segmen.</p></div><a class="btn btn-o btn-sm" href="{{ route('admin.rundown') }}">Monitoring rundown</a></div>
        @forelse ($event->rundownItems->sortBy('starts_at') as $segment)
            <div class="card-b"><b>{{ $segment->starts_at?->format('H:i') ?? 'Waktu belum ditentukan' }} · {{ $segment->title }}</b>@if ($segment->notes)<p>{{ $segment->notes }}</p>@endif</div>
        @empty
            <p class="hint" style="padding:16px">Belum ada segmen rundown.</p>
        @endforelse
    </section>
    @if ($invoice)
        <section class="card mt-16"><div class="card-b"><b>Tagihan versi {{ $invoice->version }}</b> · {{ ucfirst(str_replace('_', ' ', $invoice->status)) }} · Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}<a class="btn btn-o btn-sm ml-8" href="{{ route('admin.pembayaran.detail', $invoice) }}">Lihat tagihan</a></div></section>
    @endif
    @if ($canIssue)
        <form class="mt-16" method="POST" action="{{ route('admin.tagihan.issue', $event) }}">
            @csrf
            <button class="btn" type="submit">{{ $invoice ? 'Terbitkan versi tagihan baru' : 'Terbitkan tagihan' }}</button>
        </form>
    @endif
    @if ($canComplete)
        <form class="mt-16" method="POST" action="{{ route('admin.acara.selesai', $event) }}">
            @csrf
            <button class="btn" type="submit">Tandai acara selesai</button>
        </form>
    @endif
</main>
@endsection
