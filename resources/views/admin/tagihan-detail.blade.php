@extends('layouts.admin')

@section('title', 'Detail tagihan · Admin Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ route('admin.beranda') }}">Admin</a> / <a href="{{ route('admin.pembayaran') }}">Tagihan &amp; pembayaran</a> / Detail</div><h1>Tagihan #{{ $invoice->id }} · Versi {{ $invoice->version }}</h1><p class="sub">{{ $invoice->event->name }} · {{ $invoice->event->client->name }}</p></div><a class="btn btn-o" href="{{ route('admin.pembayaran') }}">Kembali</a></div>
    @if (session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
    @error('payment')<div class="alert warn" role="alert">{{ $message }}</div>@enderror
    @error('event')<div class="alert warn" role="alert">{{ $message }}</div>@enderror
    <div class="grid two">
        <section class="card">
            <div class="card-h"><h2>Rincian tagihan</h2><span class="badge {{ $invoice->status === 'lunas' ? 'b-ok' : 'b-warn' }}">{{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</span></div>
            <div class="table-wrap" role="region" tabindex="0" aria-label="Rincian invoice">
                <table class="t-wide">
                    <caption class="sr-only">Baris invoice snapshot</caption>
                    <thead><tr><th scope="col">Item</th><th scope="col" class="num">Harga penyedia</th><th scope="col" class="num">Komisi</th><th scope="col" class="num">Kebersihan</th><th scope="col" class="num">Total baris</th></tr></thead>
                    <tbody>
                    @foreach ($invoice->lines as $line)
                        <tr><td>{{ $line->description_snapshot }}</td><td class="num">Rp {{ number_format($line->provider_amount, 0, ',', '.') }}</td><td class="num">Rp {{ number_format($line->commission_amount, 0, ',', '.') }}</td><td class="num">Rp {{ number_format($line->cleaning_fee_amount, 0, ',', '.') }}</td><td class="num">Rp {{ number_format($line->total_amount, 0, ',', '.') }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-b">
                <dl class="dl">
                    <div><dt>Subtotal penyedia</dt><dd>Rp {{ number_format($invoice->provider_subtotal, 0, ',', '.') }}</dd></div>
                    <div><dt>Komisi 10%</dt><dd>Rp {{ number_format($invoice->commission_total, 0, ',', '.') }}</dd></div>
                    <div><dt>Biaya kebersihan</dt><dd>Rp {{ number_format($invoice->cleaning_total, 0, ',', '.') }}</dd></div>
                    <div><dt>Kode unik (3 digit)</dt><dd>{{ $invoice->unique_code }}</dd></div>
                    <div><dt>Total transfer</dt><dd><b>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</b></dd></div>
                    <div><dt>Jatuh tempo</dt><dd>{{ $invoice->due_at->translatedFormat('d M Y H:i') }} WIB</dd></div>
                </dl>
            </div>
        </section>
        <section class="card">
            <div class="card-h"><h2>Bukti pembayaran</h2></div>
            @forelse ($invoice->payments->sortByDesc('id') as $payment)
                <div class="card-b">
                    <h3>{{ ucfirst(str_replace('_', ' ', $payment->status)) }}</h3>
                    <dl class="dl">
                        <div><dt>Pengirim</dt><dd>{{ $payment->sender_name }}</dd></div>
                        <div><dt>Tanggal transfer</dt><dd>{{ $payment->transferred_at?->translatedFormat('d M Y') }}</dd></div>
                        <div><dt>Nominal bukti</dt><dd>Rp {{ number_format($payment->amount, 0, ',', '.') }}</dd></div>
                        <div><dt>Rekening tujuan</dt><dd>{{ $payment->bank_name_snapshot }} · {{ $payment->account_number_snapshot }} · {{ $payment->account_name_snapshot }}</dd></div>
                    </dl>
                    @if ($payment->proof_path)<a class="btn btn-o mt-8" href="{{ route('admin.pembayaran.bukti', $payment) }}">Unduh bukti privat</a>@endif
                    @if ($payment->rejection_reason)<p class="hint">{{ $payment->rejection_reason }}</p>@endif
                    @if ($payment->status === 'menunggu_verifikasi')
                        <form class="mt-16" method="POST" action="{{ route('admin.pembayaran.decide', [$payment, 'approve']) }}">
                            @csrf
                            <div class="field"><label for="received-{{ $payment->id }}">Nominal yang diterima</label><input class="input" id="received-{{ $payment->id }}" name="received_amount" type="number" min="0" step="1" value="{{ old('received_amount', $payment->amount) }}" required>@error('received_amount')<p class="hint">{{ $message }}</p>@enderror</div>
                            <button class="btn full" type="submit">Sahkan pembayaran</button>
                        </form>
                        <form class="mt-16" method="POST" action="{{ route('admin.pembayaran.decide', [$payment, 'reject']) }}">
                            @csrf
                            <div class="field"><label for="reason-{{ $payment->id }}">Alasan penolakan</label><textarea class="input" id="reason-{{ $payment->id }}" name="reason" minlength="5" maxlength="2000" rows="3" required>{{ old('reason') }}</textarea>@error('reason')<p class="hint">{{ $message }}</p>@enderror</div>
                            <button class="btn btn-danger full" type="submit">Tolak bukti</button>
                        </form>
                    @endif
                </div>
            @empty
                <p class="hint" style="padding:16px">Belum ada bukti pembayaran.</p>
            @endforelse
        </section>
    </div>
    @if ($invoice->status === 'kedaluwarsa' || ($invoice->status === 'terbit' && ! $invoice->due_at->isFuture()))
        <form class="mt-16" method="POST" action="{{ route('admin.tagihan.issue', $invoice->event) }}">
            @csrf
            <button class="btn" type="submit">Terbitkan versi tagihan baru</button>
        </form>
    @endif
</main>
@endsection
