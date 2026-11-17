@extends('layouts.admin')

@section('title', 'Permohonan refund · Admin Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ route('admin.beranda') }}">Admin</a> / Keuangan / Permohonan refund</div><h1>Permohonan refund</h1></div></div>
    <div class="alert" role="note">Saat ini Admin dapat menerima dan meninjau permohonan saja. Keputusan dan nominal final refund ditahan sampai kebijakan B1 disetujui.</div>
    <section class="card">
        <nav class="tabs" aria-label="Filter permohonan">
            <a href="{{ route('admin.pembatalan', ['status' => 'diajukan']) }}" @if ($statusFilter === 'diajukan') aria-current="page" @endif>Permohonan diajukan</a>
            <a href="{{ route('admin.pembatalan', ['status' => 'semua']) }}" @if ($statusFilter === 'semua') aria-current="page" @endif>Semua</a>
        </nav>
        <div class="table-wrap" role="region" tabindex="0" aria-label="Daftar permohonan refund">
            <table class="t-wide">
                <caption class="sr-only">Daftar permohonan refund klien</caption>
                <thead><tr><th scope="col">Acara</th><th scope="col">Klien</th><th scope="col">Tanggal acara</th><th scope="col">Diajukan</th><th scope="col">Tagihan</th><th scope="col">Status</th><th scope="col"></th></tr></thead>
                <tbody>
                @forelse ($refundRequests as $refundRequest)
                    <tr>
                        <td>{{ $refundRequest->event->name }}</td>
                        <td>{{ $refundRequest->client->name }}</td>
                        <td>{{ $refundRequest->event->event_date?->translatedFormat('d M Y') }}</td>
                        <td>{{ $refundRequest->submitted_at->translatedFormat('d M Y H:i') }}</td>
                        <td>v{{ $refundRequest->invoice->version }} · Rp {{ number_format($refundRequest->invoice->total_amount, 0, ',', '.') }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $refundRequest->status)) }}</td>
                        <td><a class="btn btn-o btn-sm" href="{{ route('admin.pembatalan.detail', $refundRequest) }}">Tinjau</a></td>
                    </tr>
                @empty
                    <tr><td class="hint" colspan="7">Belum ada permohonan refund.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-b">{{ $refundRequests->links() }}</div>
    </section>
</main>
@endsection
