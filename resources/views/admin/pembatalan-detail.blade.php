@extends('layouts.admin')

@section('title', 'Detail permohonan refund · Admin Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ route('admin.beranda') }}">Admin</a> / <a href="{{ route('admin.pembatalan') }}">Permohonan refund</a> / Detail</div><h1>Detail permohonan refund</h1></div><a class="btn btn-o" href="{{ route('admin.pembatalan') }}">Kembali</a></div>
    <div class="alert" role="note">Permohonan ini tercatat untuk ditinjau. Keputusan, persentase, kompensasi penyedia, dan nominal refund final belum dapat diinput sampai kebijakan B1 disetujui.</div>
    <div class="grid two">
        <section class="card">
            <div class="card-h"><h2>Informasi acara</h2><span class="badge b-warn">{{ ucfirst(str_replace('_', ' ', $refundRequest->status)) }}</span></div>
            <div class="card-b">
                <dl class="dl">
                    <div><dt>Acara</dt><dd><a href="{{ route('admin.acara.detail', $refundRequest->event) }}">{{ $refundRequest->event->name }}</a></dd></div>
                    <div><dt>Klien</dt><dd>{{ $refundRequest->client->name }}</dd></div>
                    <div><dt>Status acara</dt><dd>{{ ucfirst(str_replace('_', ' ', $refundRequest->event->status->value)) }}</dd></div>
                    <div><dt>Tanggal acara</dt><dd>{{ $refundRequest->event->event_date?->translatedFormat('d M Y') }}</dd></div>
                    <div><dt>Tagihan lunas</dt><dd>v{{ $refundRequest->invoice->version }} · Rp {{ number_format($refundRequest->invoice->total_amount, 0, ',', '.') }}</dd></div>
                    <div><dt>Diajukan</dt><dd>{{ $refundRequest->submitted_at->translatedFormat('d M Y H:i') }}</dd></div>
                </dl>
            </div>
        </section>
        <section class="card">
            <div class="card-h"><h2>Alasan Klien</h2></div>
            <div class="card-b"><p>{{ $refundRequest->reason }}</p></div>
        </section>
    </div>
</main>
@endsection
