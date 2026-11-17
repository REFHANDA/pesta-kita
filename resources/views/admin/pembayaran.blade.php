@extends('layouts.admin')

@section('title', 'Tagihan &amp; pembayaran · Admin Pesta Kita')

@php
    $filters = ['semua' => 'Semua', 'terbit' => 'Belum lunas', 'menunggu_verifikasi' => 'Menunggu validasi', 'lunas' => 'Lunas', 'kedaluwarsa' => 'Kedaluwarsa'];
@endphp

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ route('admin.beranda') }}">Admin</a> / Tagihan &amp; pembayaran</div><h1>Tagihan &amp; pembayaran</h1></div></div>
    <section class="card">
        <nav class="tabs" aria-label="Filter tagihan">
            @foreach ($filters as $value => $label)
                <a href="{{ route('admin.pembayaran', ['status' => $value]) }}" @if ($statusFilter === $value) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        <div class="table-wrap" role="region" tabindex="0" aria-label="Daftar tagihan">
            <table class="t-wide">
                <caption class="sr-only">Invoice dan status pembayaran</caption>
                <thead><tr><th scope="col">Invoice</th><th scope="col">Acara</th><th scope="col">Total</th><th scope="col">Jatuh tempo</th><th scope="col">Pembayaran</th><th scope="col"></th></tr></thead>
                <tbody>
                @forelse ($invoices as $invoice)
                    @php
                        $payment = $invoice->latestPayment;
                        $paymentLabel = $payment?->status === 'menunggu_verifikasi'
                            ? 'Menunggu verifikasi'
                            : ($payment?->status === 'ditolak'
                                ? 'Bukti ditolak'
                                : ($invoice->status === 'lunas'
                                    ? 'Lunas'
                                    : ($invoice->status === 'kedaluwarsa' || ($invoice->status === 'terbit' && $invoice->due_at->isPast())
                                        ? 'Kedaluwarsa'
                                        : 'Menunggu pembayaran')));
                    @endphp
                    <tr>
                        <td><b>Tagihan #{{ $invoice->id }}</b><span class="sub-t">Versi {{ $invoice->version }}</span></td>
                        <td>{{ $invoice->event->name }}<span class="sub-t">{{ $invoice->event->event_date?->translatedFormat('d M Y') }}</span></td>
                        <td>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                        <td>{{ $invoice->due_at->translatedFormat('d M Y H:i') }} WIB</td>
                        <td>{{ $paymentLabel }}</td>
                        <td><a class="btn btn-o btn-sm" href="{{ route('admin.pembayaran.detail', $invoice) }}">Periksa</a></td>
                    </tr>
                @empty
                    <tr><td class="hint" colspan="6">Belum ada tagihan untuk filter ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-b">{{ $invoices->links() }}</div>
    </section>
</main>
@endsection
