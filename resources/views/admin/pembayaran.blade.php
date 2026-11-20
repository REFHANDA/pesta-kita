@extends('layouts.admin')

@section('title', 'Tagihan & Pembayaran · Admin Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / Tagihan &amp; Pembayaran</div>
            <h1>Tagihan &amp; Pembayaran</h1>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success" style="background:#dcfce7;color:#166534;padding:12px 16px;border-radius:8px;margin-bottom:20px;border:1px solid #bbf7d0;">
        {{ session('success') }}
    </div>
    @endif

    <div class="grid stats three">
        <div class="card stat">
            <span class="ic-box"><span class="ic i-bill"></span></span>
            <div>
                <div class="lbl">Menunggu pembayaran</div>
                <div class="val">{{ $countMenungguPembayaran }}</div>
            </div>
        </div>
        <div class="card stat">
            <span class="ic-box"><span class="ic i-clip"></span></span>
            <div>
                <div class="lbl">Bukti perlu validasi</div>
                <div class="val">{{ $countMenungguValidasi }}</div>
            </div>
        </div>
        <div class="card stat">
            <span class="ic-box"><span class="ic i-ok"></span></span>
            <div>
                <div class="lbl">Lunas bulan ini</div>
                <div class="val">{{ $countLunasBulanIni }}</div>
            </div>
        </div>
    </div>

    <section class="card">
        <div aria-label="Navigasi status" class="tabs" role="navigation">
            <a href="{{ route('admin.pembayaran.index') }}" class="{{ $statusTab === 'semua' ? 'active' : '' }}" {{ $statusTab === 'semua' ? 'aria-current="page"' : '' }}>Semua</a>
            <a href="{{ route('admin.pembayaran.index', ['status' => 'menunggu-pembayaran']) }}" class="{{ $statusTab === 'menunggu-pembayaran' ? 'active' : '' }}" {{ $statusTab === 'menunggu-pembayaran' ? 'aria-current="page"' : '' }}>Menunggu pembayaran</a>
            <a href="{{ route('admin.pembayaran.index', ['status' => 'menunggu-validasi']) }}" class="{{ $statusTab === 'menunggu-validasi' ? 'active' : '' }}" {{ $statusTab === 'menunggu-validasi' ? 'aria-current="page"' : '' }}>
                Menunggu validasi
                @if($countMenungguValidasi > 0)
                <span class="n">{{ $countMenungguValidasi }}</span>
                @endif
            </a>
            <a href="{{ route('admin.pembayaran.index', ['status' => 'lunas']) }}" class="{{ $statusTab === 'lunas' ? 'active' : '' }}" {{ $statusTab === 'lunas' ? 'aria-current="page"' : '' }}>Lunas</a>
            <a href="{{ route('admin.pembayaran.index', ['status' => 'kedaluwarsa']) }}" class="{{ $statusTab === 'kedaluwarsa' ? 'active' : '' }}" {{ $statusTab === 'kedaluwarsa' ? 'aria-current="page"' : '' }}>Kedaluwarsa</a>
        </div>

        <form class="toolbar" method="GET" action="{{ route('admin.pembayaran.index') }}">
            <input type="hidden" name="status" value="{{ $statusTab }}">
            <input class="input" name="q" value="{{ $q }}" aria-label="Cari nomor tagihan atau acara" placeholder="Cari nomor tagihan / nama acara..."/>
            <button class="btn btn-o" type="submit">Cari</button>
            @if(!empty($q))
            <a href="{{ route('admin.pembayaran.index', ['status' => $statusTab]) }}" class="btn btn-o">Reset</a>
            @endif
        </form>

        <div aria-label="Tagihan &amp; pembayaran. Gulir horizontal untuk melihat seluruh kolom" class="table-wrap" role="region" tabindex="0">
            <table class="t-wide">
                <caption class="sr-only">Tabel: Tagihan &amp; pembayaran</caption>
                <thead>
                    <tr>
                        <th scope="col">Nomor tagihan</th>
                        <th scope="col">Acara</th>
                        <th scope="col" class="num">Total transfer</th>
                        <th scope="col">Rekening tujuan</th>
                        <th scope="col">Batas bayar</th>
                        <th scope="col">Status</th>
                        <th class="col-aksi" scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    @php
                        $payment = $invoice->latestPayment;
                        $badgeClass = match($invoice->status) {
                            \App\Enums\InvoiceStatus::Lunas => 'b-ok',
                            \App\Enums\InvoiceStatus::MenungguValidasi => 'b-warn',
                            \App\Enums\InvoiceStatus::BelumDibayar => 'b-warn',
                            \App\Enums\InvoiceStatus::Kedaluwarsa, \App\Enums\InvoiceStatus::Dibatalkan => 'b-bad',
                        };
                    @endphp
                    <tr>
                        <td>
                            <b>{{ $invoice->invoice_number }}</b>
                            <small>Versi {{ $invoice->version }}</small>
                        </td>
                        <td>
                            <b>{{ $invoice->event?->name ?? 'Acara' }}</b>
                            <small>Klien: {{ $invoice->event?->client?->name ?? '-' }} · {{ $invoice->event?->event_date?->translatedFormat('d M Y') ?? '-' }}</small>
                        </td>
                        <td class="num">
                            <b>Rp {{ number_format($invoice->transfer_total, 0, ',', '.') }}</b>
                        </td>
                        <td>
                            @if($payment?->companyBankAccount)
                                {{ $payment->companyBankAccount->bank_name }} ···· {{ substr($payment->companyBankAccount->account_number, -4) }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if($invoice->status === \App\Enums\InvoiceStatus::Lunas)
                                Lunas {{ $invoice->paid_at?->translatedFormat('d M, H:i') }}
                            @elseif($invoice->due_at)
                                {{ $invoice->due_at->translatedFormat('d M, H:i') }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $badgeClass }}">{{ $invoice->status->label() }}</span>
                        </td>
                        <td class="col-aksi">
                            <a class="btn {{ $invoice->status === \App\Enums\InvoiceStatus::MenungguValidasi ? '' : 'btn-o' }} btn-sm" href="{{ route('admin.pembayaran.show', $invoice) }}">
                                {{ $invoice->status === \App\Enums\InvoiceStatus::MenungguValidasi ? 'Periksa' : 'Detail' }}
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:24px;color:var(--sub, #64748b);">
                            Tidak ada data tagihan atau pembayaran yang ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
        <div style="padding:16px;">
            {{ $invoices->links() }}
        </div>
        @endif
    </section>
</main>
@endsection
