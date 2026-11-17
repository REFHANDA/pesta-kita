@extends('layouts.klien')

@section('title', 'Permohonan refund · Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head">
        <div><div class="crumbs"><a href="{{ route('klien.beranda') }}">Beranda</a> / <a href="{{ route('klien.acara.detail', $event) }}">{{ $event->name }}</a> / Permohonan refund</div><h1>Permohonan refund</h1></div>
        <a class="btn btn-o" href="{{ route('klien.acara.detail', $event) }}">Kembali ke acara</a>
    </div>
    @if (session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
    @error('refund')<div class="alert warn" role="alert">{{ $message }}</div>@enderror
    <div class="alert" role="note">Halaman ini hanya mencatat permohonan. Kebijakan, keputusan, kompensasi, dan nominal refund final belum ditetapkan; tidak ada estimasi refund yang dihitung di halaman ini.</div>
    <section class="card">
        <div class="card-h"><h2>Ringkasan transaksi</h2></div>
        <div class="card-b">
            <dl class="dl">
                <div><dt>Acara</dt><dd>{{ $event->name }}</dd></div>
                <div><dt>Tanggal acara</dt><dd>{{ $event->event_date?->translatedFormat('d M Y') }}</dd></div>
                <div><dt>Tagihan lunas</dt><dd>Versi {{ $invoice->version }} · Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</dd></div>
                <div><dt>Status permohonan</dt><dd>{{ $refundRequest ? ucfirst(str_replace('_', ' ', $refundRequest->status)) : 'Belum diajukan' }}</dd></div>
            </dl>
        </div>
    </section>
    @if ($refundRequest)
        <section class="card mt-16">
            <div class="card-h"><h2>Permohonan tercatat</h2></div>
            <div class="card-b"><p>{{ $refundRequest->reason }}</p><p class="hint">Diajukan {{ $refundRequest->submitted_at->translatedFormat('d M Y H:i') }}. Keputusan dan nominal final belum tersedia.</p></div>
        </section>
    @else
        <form class="card mt-16" method="POST" action="{{ route('klien.refund.store', $event) }}">
            @csrf
            <div class="card-h"><h2>Alasan permohonan</h2></div>
            <div class="card-b">
                <div class="field">
                    <label for="reason">Alasan <span class="req" aria-hidden="true">*</span></label>
                    <textarea class="input" id="reason" name="reason" rows="5" minlength="10" maxlength="2000" required>{{ old('reason') }}</textarea>
                    @error('reason')<p class="hint">{{ $message }}</p>@enderror
                </div>
                <div class="actions mt-16"><a class="btn btn-o" href="{{ route('klien.acara.detail', $event) }}">Batal</a><button class="btn btn-bad" type="submit">Kirim permohonan</button></div>
            </div>
        </form>
    @endif
</main>
@endsection
