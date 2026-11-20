@extends('layouts.klien')

@section('title', 'Nota Lunas · Pesta Kita')

@section('content')
@php
    $invoice = $invoice ?? null;
@endphp
<main id="konten" class="content" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs">
                <a href="{{ url('/klien') }}">Beranda</a> /
                @if($invoice && $invoice->event)
                <a href="{{ route('klien.acara.detail', $invoice->event) }}">{{ $invoice->event->name }}</a> /
                @endif
                Nota Lunas
            </div>
            <h1>Nota Lunas (Kuitansi)</h1>
        </div>
    </div>

    @if(!$invoice)
    <div class="card card-b">
        <p>Pilih acara dari <a href="{{ route('klien.acara') }}">daftar acara Anda</a> untuk melihat nota lunas.</p>
    </div>
    @else

    @php
        $validPayment = $invoice->payments()->where('status', \App\Enums\PaymentStatus::Valid)->latest()->first() ?? $invoice->latestPayment;
    @endphp

    <div class="grid two">
        <div class="stack">
            <section class="card">
                <div class="nota-h" style="display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:16px;border-bottom:1px solid var(--line, #e2e8f0);margin-bottom:16px;">
                    <div>
                        <h2>Nota Lunas</h2>
                        <div class="no" style="font-weight:700;color:var(--text, #0f172a);margin-top:4px;">
                            {{ $invoice->receipt_number ? 'Nomor: ' . $invoice->receipt_number : 'Tagihan ' . $invoice->invoice_number }}
                        </div>
                        <p class="meta">Tagihan Referensi: {{ $invoice->invoice_number }} · {{ $invoice->event->name }}</p>
                    </div>
                    <div class="stamp" style="border:3px solid #16a34a;color:#16a34a;font-weight:900;font-size:18px;padding:4px 12px;border-radius:6px;transform:rotate(-4deg);" aria-label="Status lunas">
                        LUNAS
                    </div>
                </div>

                <div class="table-wrap" role="region" tabindex="0" aria-label="Nota lunas. Gulir horizontal untuk melihat seluruh kolom">
                    <table class="t-wide">
                        <caption class="sr-only">Tabel: Nota lunas</caption>
                        <thead>
                            <tr>
                                <th scope="col">Rincian Komponen</th>
                                <th scope="col">Jenis</th>
                                <th scope="col" class="num">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items as $item)
                            <tr>
                                <td><b>{{ $item->description }}</b></td>
                                <td>{{ $item->type->label() }}</td>
                                <td class="num">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <table class="sum">
                    <tr>
                        <td>Total tagihan</td>
                        <td class="num">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                    </tr>
                    @if($invoice->unique_code > 0)
                    <tr>
                        <td><b>Kode unik transfer</b></td>
                        <td class="num">Rp {{ number_format($invoice->unique_code, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr class="t">
                        <td>Total yang ditransfer</td>
                        <td class="num"><b>Rp {{ number_format($invoice->transfer_total, 0, ',', '.') }}</b></td>
                    </tr>
                    @if($validPayment?->companyBankAccount)
                    <tr>
                        <td>Rekening tujuan</td>
                        <td class="num">{{ $validPayment->companyBankAccount->bank_name }} {{ $validPayment->companyBankAccount->account_number }}</td>
                    </tr>
                    @endif
                </table>

                <div class="card-b">
                    <div class="actions">
                        <a class="btn" href="{{ route('klien.tagihan.receipt', $invoice) }}" target="_blank">
                            Cetak / Unduh Nota Lunas (PDF)
                        </a>
                        <a class="btn btn-o" href="{{ route('klien.tagihan.print', $invoice) }}" target="_blank">
                            Cetak Tagihan (PDF)
                        </a>
                    </div>
                </div>
            </section>
        </div>

        <div class="stack">
            <section class="card">
                <div class="card-h">
                    <h2>Informasi Pembayaran</h2>
                    <span class="badge b-ok">Valid</span>
                </div>
                <div class="card-b">
                    <dl class="dl" style="grid-template-columns:1fr;gap:12px;">
                        <div>
                            <dt>Rekening tujuan</dt>
                            <dd>
                                {{ $validPayment?->companyBankAccount?->bank_name ?? 'Bank' }} {{ $validPayment?->companyBankAccount?->account_number ?? '-' }}
                                · a.n. {{ $validPayment?->companyBankAccount?->account_name ?? 'PT Pesta Kita Indonesia' }}
                            </dd>
                        </div>
                        <div>
                            <dt>Nama pengirim</dt>
                            <dd>{{ $validPayment?->sender_name ?? $invoice->event?->client?->name }}</dd>
                        </div>
                        <div>
                            <dt>Tanggal transfer</dt>
                            <dd>{{ $validPayment?->transfer_date?->translatedFormat('d F Y') ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt>Dikonfirmasi valid</dt>
                            <dd>{{ $invoice->paid_at?->translatedFormat('d M Y, H:i') ?? '-' }} WIB</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section class="card locked">
                <div class="card-b">
                    <b>Isi tagihan terkunci</b>
                    <p class="sub mt-8">Setelah lunas, rincian pesanan dan biaya tidak dapat diubah kembali. Penyedia telah diberitahu dan jadwal acara Anda telah dikonfirmasi.</p>
                </div>
            </section>
        </div>
    </div>
    @endif
</main>
@endsection
