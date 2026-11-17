@extends('layouts.klien')

@section('title', 'Tagihan acara · Pesta Kita')

@push('styles')
<style>
    @media print {
        .sidebar, .topbar, .skip, .no-print { display: none !important; }
        .app, .main, .content { display: block !important; width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .card { break-inside: avoid; box-shadow: none !important; }
    }
</style>
@endpush

@php
    $payment = $invoice->latestPayment;
    $expired = $invoice->status === 'terbit' && ! $invoice->due_at->isFuture();
    $status = $invoice->status === 'lunas'
        ? ['b-ok', 'Lunas']
        : ($invoice->status === 'kedaluwarsa' || $expired
            ? ['b-bad', 'Kedaluwarsa']
            : ($payment?->status === 'menunggu_verifikasi'
                ? ['b-warn', 'Menunggu verifikasi']
                : ($payment?->status === 'ditolak'
                    ? ['b-bad', 'Bukti ditolak']
                    : ['b-warn', 'Menunggu pembayaran'])));
    [$badgeClass, $badgeLabel] = $status;
@endphp

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ route('klien.beranda') }}">Beranda</a> / <a href="{{ route('klien.acara.detail', $invoice->event) }}">{{ $invoice->event->name }}</a> / Tagihan</div><h1>Tagihan · Versi {{ $invoice->version }}</h1><p class="sub">Terbit {{ $invoice->issued_at->translatedFormat('d M Y H:i') }} WIB</p></div><div class="actions no-print"><span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span><button class="btn btn-o" type="button" onclick="window.print()">Cetak / Simpan PDF</button></div></div>
    @if (session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
    @error('invoice')<div class="alert warn" role="alert">{{ $message }}</div>@enderror
    <div class="grid two">
        <section class="card">
            <div class="card-h"><div><h2>Rincian tagihan</h2><p class="meta">Acara {{ $invoice->event->name }}</p></div></div>
            <div class="table-wrap" role="region" tabindex="0" aria-label="Rincian tagihan">
                <table class="t-wide">
                    <caption class="sr-only">Rincian harga snapshot tagihan</caption>
                    <thead><tr><th scope="col">Rincian</th><th scope="col" class="num">Harga penyedia</th><th scope="col" class="num">Komisi 10%</th><th scope="col" class="num">Biaya venue</th><th scope="col" class="num">Total</th></tr></thead>
                    <tbody>
                    @foreach ($invoice->lines as $line)
                        <tr><td>{{ $line->description_snapshot }}</td><td class="num">Rp {{ number_format($line->provider_amount, 0, ',', '.') }}</td><td class="num">Rp {{ number_format($line->commission_amount, 0, ',', '.') }}</td><td class="num">Rp {{ number_format($line->cleaning_fee_amount, 0, ',', '.') }}</td><td class="num">Rp {{ number_format($line->total_amount, 0, ',', '.') }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-b">
                <div class="detail-list">
                    <div class="dl-row"><span>Subtotal harga penyedia</span><strong>Rp {{ number_format($invoice->provider_subtotal, 0, ',', '.') }}</strong></div>
                    <div class="dl-row"><span>Komisi Pesta Kita (10%)</span><strong>Rp {{ number_format($invoice->commission_total, 0, ',', '.') }}</strong></div>
                    <div class="dl-row"><span>Biaya kebersihan venue</span><strong>Rp {{ number_format($invoice->cleaning_total, 0, ',', '.') }}</strong></div>
                    <div class="dl-row"><span>Kode unik (termasuk dalam total)</span><strong>{{ $invoice->unique_code }}</strong></div>
                    <div class="dl-row"><span><b>Total yang harus ditransfer</b></span><strong>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</strong></div>
                </div>
                <p class="deadline mt-16">Bayar sebelum {{ $invoice->due_at->translatedFormat('d M Y H:i') }} WIB.</p>
                <p class="hint">Tagihan yang kedaluwarsa tidak menerima bukti baru dan tidak otomatis membatalkan pesanan.</p>
            </div>
        </section>

        <div class="stack no-print">
            @if ($payment?->status === 'menunggu_verifikasi')
                <section class="card"><div class="card-h"><h2>Bukti pembayaran</h2><span class="badge b-warn">Menunggu verifikasi</span></div><div class="card-b"><p>Transfer {{ $payment->transferred_at?->translatedFormat('d M Y') }} · Rp {{ number_format($payment->amount, 0, ',', '.') }}</p><p>Pengirim: {{ $payment->sender_name }}</p><a class="btn btn-o" href="{{ route('klien.pembayaran.bukti', $payment) }}">Lihat bukti yang dikirim</a></div></section>
            @elseif ($payment?->status === 'ditolak')
                <div class="alert warn"><b>Bukti pembayaran ditolak.</b><p>{{ $payment->rejection_reason }}</p></div>
            @endif

            @if ($invoice->status === 'terbit' && ! $expired && $payment?->status !== 'menunggu_verifikasi')
                <section class="card">
                    <div class="card-h"><h2>Kirim bukti transfer</h2></div>
                    <div class="card-b">
                        @if ($bankAccounts->isEmpty())
                            <p class="hint">Belum ada rekening tujuan aktif. Silakan hubungi Admin.</p>
                        @else
                            <form class="stack" method="POST" action="{{ route('klien.tagihan.pembayaran.store', $invoice) }}" enctype="multipart/form-data">
                                @csrf
                                <fieldset class="choice-group">
                                    <legend>Pilih rekening tujuan</legend>
                                    @foreach ($bankAccounts as $account)
                                        <label class="choice"><input type="radio" name="company_bank_account_id" value="{{ $account->id }}" @checked(old('company_bank_account_id') == $account->id) required><span class="rek"><b>{{ $account->bank_name }} {{ $account->account_number }}</b>{{ $account->account_name }}</span></label>
                                    @endforeach
                                    @error('company_bank_account_id')<p class="hint">{{ $message }}</p>@enderror
                                </fieldset>
                                <div class="field"><label for="sender">Nama pengirim</label><input class="input" id="sender" name="sender_name" value="{{ old('sender_name') }}" maxlength="255" required>@error('sender_name')<p class="hint">{{ $message }}</p>@enderror</div>
                                <div class="field"><label for="transferred">Tanggal transfer</label><input class="input" id="transferred" name="transferred_at" type="date" value="{{ old('transferred_at', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>@error('transferred_at')<p class="hint">{{ $message }}</p>@enderror</div>
                                <div class="field"><label for="amount">Jumlah transfer (Rp)</label><input class="input" id="amount" name="amount" type="number" min="1" step="1" value="{{ old('amount', $invoice->total_amount) }}" required>@error('amount')<p class="hint">{{ $message }}</p>@enderror</div>
                                <div class="field"><label for="proof">Bukti transfer</label><input class="input" id="proof" name="proof" type="file" accept=".jpg,.jpeg,.png,.pdf" required><p class="hint">JPG, PNG, atau PDF; maksimum 5 MB. File disimpan privat.</p>@error('proof')<p class="hint">{{ $message }}</p>@enderror</div>
                                <button class="btn" type="submit">Kirim untuk verifikasi</button>
                            </form>
                        @endif
                    </div>
                </section>
            @elseif ($expired || $invoice->status === 'kedaluwarsa')
                <div class="data-note" role="note">Tagihan kedaluwarsa. Admin dapat menerbitkan versi pengganti; pesanan tidak dibatalkan otomatis.</div>
            @elseif ($invoice->status === 'lunas')
                <div class="alert">Pembayaran telah disahkan. Acara terkonfirmasi.</div>
                <a class="btn btn-o" href="{{ route('klien.refund.create', $invoice->event) }}">Ajukan permohonan refund</a>
            @endif

            @if ($refundRequest)
                <section class="card"><div class="card-h"><h2>Permohonan refund tercatat</h2></div><div class="card-b"><p>{{ $refundRequest->reason }}</p><p class="hint">Keputusan dan nominal final belum tersedia.</p></div></section>
            @endif
            <a class="btn btn-o" href="{{ route('klien.acara.detail', $invoice->event) }}">Kembali ke acara</a>
        </div>
    </div>
</main>
@endsection
