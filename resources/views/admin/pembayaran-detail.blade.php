@extends('layouts.admin')

@section('title', 'Validasi pembayaran · Admin Pesta Kita')

@section('content')
@php
    $invoice = $invoice ?? null;
@endphp
<main class="content page-refined" id="konten" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs">
                <a href="{{ url('/admin') }}">Admin</a> /
                <a href="{{ route('admin.pembayaran.index') }}">Tagihan &amp; Pembayaran</a> /
                Detail
            </div>
            <h1>Validasi Pembayaran {{ $invoice?->invoice_number ?? '' }}</h1>
        </div>
    </div>

    @if(!$invoice)
    <div class="card card-b">
        <p>Pilih pembayaran dari <a href="{{ route('admin.pembayaran.index') }}">daftar antrean pembayaran</a> untuk melakukan validasi.</p>
    </div>
    @else

    @if(session('success'))
    <div class="alert alert-success" style="background:#dcfce7;color:#166534;padding:12px 16px;border-radius:8px;margin-bottom:20px;border:1px solid #bbf7d0;">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger" style="background:#fee2e2;color:#991b1b;padding:12px 16px;border-radius:8px;margin-bottom:20px;border:1px solid #fecaca;">
        <ul style="margin:0;padding-left:20px;">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @php
        $latestPayment = $invoice->latestPayment;
        $badgeClass = match($invoice->status) {
            \App\Enums\InvoiceStatus::Lunas => 'b-ok',
            \App\Enums\InvoiceStatus::MenungguValidasi => 'b-warn',
            \App\Enums\InvoiceStatus::BelumDibayar => 'b-warn',
            \App\Enums\InvoiceStatus::Kedaluwarsa, \App\Enums\InvoiceStatus::Dibatalkan => 'b-bad',
        };
    @endphp

    <div class="grid payment-layout" style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
        <section class="card payment-summary refined-card">
            <div class="card-h">
                <div>
                    <h2>Ringkasan tagihan</h2>
                    <p class="meta">{{ $invoice->invoice_number }} · Versi {{ $invoice->version }}</p>
                </div>
                <span class="badge {{ $badgeClass }}">{{ $invoice->status->label() }}</span>
            </div>

            <div class="card-b">
                <div class="payment-total" style="background:var(--bg-sub, #f8fafc);padding:16px;border-radius:8px;margin-bottom:16px;border:1px solid var(--line, #e2e8f0);">
                    <span style="font-size:13px;color:var(--sub, #64748b);display:block;">Total yang harus dibayar (Transfer Total)</span>
                    <strong style="font-size:24px;color:#1e1b4b;">Rp {{ number_format($invoice->transfer_total, 0, ',', '.') }}</strong>
                </div>

                <div class="detail-section-title" style="font-weight:700;font-size:13px;text-transform:uppercase;color:var(--sub, #64748b);margin-top:16px;margin-bottom:8px;">
                    Informasi acara &amp; Klien
                </div>
                <div class="detail-list payment-details" style="display:grid;gap:8px;font-size:13px;margin-bottom:16px;">
                    <div><span>Nama acara:</span> <b>{{ $invoice->event?->name }}</b></div>
                    <div><span>Klien:</span> <b>{{ $invoice->event?->client?->name }} ({{ $invoice->event?->client?->email }})</b></div>
                    <div><span>Tanggal acara:</span> <b>{{ $invoice->event?->event_date?->translatedFormat('d F Y') ?? '-' }}</b></div>
                    <div><span>Batas pembayaran:</span> <b>{{ $invoice->due_at?->translatedFormat('d M Y, H:i') ?? '-' }} WIB</b></div>
                </div>

                <div class="detail-section-title" style="font-weight:700;font-size:13px;text-transform:uppercase;color:var(--sub, #64748b);margin-top:16px;margin-bottom:8px;">
                    Rincian tagihan
                </div>
                <div class="detail-list payment-details" style="display:grid;gap:8px;font-size:13px;">
                    <div><span>Subtotal harga penyedia:</span> <b>Rp {{ number_format($invoice->subtotal_provider, 0, ',', '.') }}</b></div>
                    <div><span>Komisi platform (10%):</span> <b>Rp {{ number_format($invoice->total_commission, 0, ',', '.') }}</b></div>
                    @if($invoice->cleaning_fee > 0)
                    <div><span>Biaya kebersihan venue:</span> <b>Rp {{ number_format($invoice->cleaning_fee, 0, ',', '.') }}</b></div>
                    @endif
                    <div><span>Kode unik transfer:</span> <b>Rp {{ number_format($invoice->unique_code, 0, ',', '.') }}</b></div>
                    <div class="detail-total" style="border-top:1px solid var(--line, #e2e8f0);padding-top:8px;font-weight:700;">
                        <span>Total transfer:</span> <b>Rp {{ number_format($invoice->transfer_total, 0, ',', '.') }}</b>
                    </div>
                </div>

                <div class="actions" style="margin-top:20px;display:flex;gap:8px;">
                    <a class="btn btn-o btn-sm" href="{{ route('admin.tagihan.print', $invoice) }}" target="_blank">
                        Cetak Tagihan (PDF)
                    </a>
                    @if($invoice->status === \App\Enums\InvoiceStatus::Lunas)
                    <a class="btn btn-sm" href="{{ route('admin.tagihan.receipt', $invoice) }}" target="_blank">
                        Lihat Nota Lunas (PDF)
                    </a>
                    @endif
                    @if($invoice->status === \App\Enums\InvoiceStatus::BelumDibayar)
                    <form action="{{ route('admin.tagihan.reissue', $invoice) }}" method="POST" onsubmit="return confirm('Buat ulang tagihan baru (versi baru) dan batalkan versi ini?');" style="display:inline;">
                        @csrf
                        <button class="btn btn-o btn-sm" type="submit">Buat Ulang Tagihan</button>
                    </form>
                    @endif
                </div>
            </div>
        </section>

        <section class="card payment-proof-card refined-card">
            <div class="card-h">
                <div>
                    <h2>Bukti pembayaran</h2>
                    <p class="meta">
                        @if($latestPayment)
                            Unggahan {{ $latestPayment->created_at?->translatedFormat('d M Y, H:i') }} WIB
                        @else
                            Belum ada bukti pembayaran diunggah
                        @endif
                    </p>
                </div>
                @if($latestPayment)
                <span class="badge {{ $latestPayment->status === \App\Enums\PaymentStatus::Valid ? 'b-ok' : ($latestPayment->status === \App\Enums\PaymentStatus::Ditolak ? 'b-bad' : 'b-warn') }}">
                    {{ $latestPayment->status->label() }}
                </span>
                @endif
            </div>

            <div class="card-b">
                @if($latestPayment)
                    @php
                        $isAmountEqual = ($latestPayment->amount === $invoice->transfer_total);
                        $isAmountMatching = ($latestPayment->amount === $invoice->transfer_total);
                        $isImage = $latestPayment->proof && str_starts_with($latestPayment->proof->mime_type ?? '', 'image/');
                    @endphp

                    <!-- Tampilan Berdampingan: Berkas/Gambar Bukti Transfer & Rincian Klaim Pembayaran Klien -->
                    <div class="proof-comparison-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;margin-bottom:20px;">
                        <!-- Kolom Kiri: Gambar / Berkas Bukti Transfer -->
                        <div class="proof-preview-col" style="background:#f8fafc;border:1px solid #cbd5e1;border-radius:8px;padding:16px;text-align:center;">
                            <div class="detail-section-title" style="font-weight:700;font-size:12px;text-transform:uppercase;color:var(--sub, #64748b);margin-bottom:12px;text-align:left;">
                                Berkas Bukti Transfer
                            </div>
                            @if($latestPayment->proof)
                                @if($isImage)
                                <div style="margin-bottom:12px;background:#fff;border-radius:6px;overflow:hidden;border:1px solid #e2e8f0;max-height:260px;display:flex;align-items:center;justify-content:center;">
                                    <a href="{{ route('media.show', $latestPayment->proof) }}" target="_blank" title="Klik untuk membuka bukti ukuran penuh">
                                        <img src="{{ route('media.show', $latestPayment->proof) }}" alt="Bukti Transfer" style="max-width:100%;max-height:250px;object-fit:contain;display:block;">
                                    </a>
                                </div>
                                @else
                                <span class="proof-icon" style="font-size:42px;display:block;margin-bottom:8px;">📄</span>
                                @endif
                                <b style="display:block;font-size:13px;word-break:break-all;">{{ $latestPayment->proof->original_name ?? 'Bukti Transfer' }}</b>
                                <small style="display:block;color:#64748b;margin-top:4px;">{{ strtoupper($latestPayment->proof->mime_type ?? 'FILE') }} · {{ number_format(($latestPayment->proof->size ?? 0) / 1024, 1) }} KB</small>
                                <div style="margin-top:12px;">
                                    <a class="btn btn-sm btn-o" href="{{ route('media.show', $latestPayment->proof) }}" target="_blank">
                                        Buka File Penuh
                                    </a>
                                </div>
                            @else
                                <div style="padding:24px;color:#64748b;">
                                    <span style="font-size:36px;display:block;margin-bottom:8px;">📁</span>
                                    <p>Tidak ada berkas bukti pembayaran dilampirkan.</p>
                                </div>
                            @endif
                        </div>

                        <!-- Kolom Kanan: Rincian Klaim Transfer Klien -->
                        <div class="proof-claim-col" style="background:#fff;border:1px solid #cbd5e1;border-radius:8px;padding:16px;">
                            <div class="detail-section-title" style="font-weight:700;font-size:12px;text-transform:uppercase;color:var(--sub, #64748b);margin-bottom:12px;">
                                Rincian Klaim Transfer Klien
                            </div>

                            <div style="background:var(--bg-sub, #f8fafc);padding:12px 16px;border-radius:8px;margin-bottom:16px;border:1px solid var(--line, #e2e8f0);">
                                <span style="font-size:12px;color:var(--sub, #64748b);display:block;">Jumlah yang Ditransfer Klien</span>
                                <strong class="{{ $isAmountEqual ? 'ok-t' : 'bad-t' }}" style="font-size:22px;display:block;margin-top:2px;">
                                    Rp {{ number_format($latestPayment->amount, 0, ',', '.') }}
                                </strong>
                                <small style="font-size:12px;color:#64748b;display:block;margin-top:4px;">
                                    Total tagihan + kode unik: <b>Rp {{ number_format($invoice->transfer_total, 0, ',', '.') }}</b>
                                </small>
                            </div>

                            <div class="detail-list payment-details" style="display:grid;gap:10px;font-size:13px;">
                                <div><span>Nama Pengirim:</span> <b>{{ $latestPayment->sender_name }}</b></div>
                                <div><span>Tanggal Transfer:</span> <b>{{ $latestPayment->transfer_date?->translatedFormat('d F Y') ?? '-' }}</b></div>
                                <div>
                                    <span>Rekening Tujuan:</span>
                                    <b>{{ $latestPayment->companyBankAccount?->bank_name }} {{ $latestPayment->companyBankAccount?->account_number }} (a.n. {{ $latestPayment->companyBankAccount?->account_name }})</b>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(!$isAmountMatching)
                    <div class="alert" style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:16px;border:1px solid #f87171;font-size:13px;">
                        <span class="ic i-info" aria-hidden="true"></span>
                        <b>Perhatian:</b> Nominal transfer pada bukti (Rp {{ number_format($latestPayment->amount, 0, ',', '.') }}) <b>TIDAK SAMA</b> dengan total yang harus dibayar (Rp {{ number_format($invoice->transfer_total, 0, ',', '.') }}). Sistem menolak konfirmasi valid bila nominal tidak sesuai.
                    </div>
                    @else
                    <div class="alert" style="background:#f0fdf4;color:#166534;padding:12px;border-radius:8px;margin-bottom:16px;border:1px solid #86efac;font-size:13px;">
                        <span class="ic i-ok" aria-hidden="true"></span>
                        Nominal transfer sesuai tepat dengan total tagihan dan kode unik (Rp {{ number_format($invoice->transfer_total, 0, ',', '.') }}).
                    </div>
                    @endif

                    @if($latestPayment->status === \App\Enums\PaymentStatus::MenungguValidasi)
                    <div style="border-top:1px solid var(--line, #e2e8f0);padding-top:16px;margin-top:16px;">
                        <h3 style="font-size:14px;margin-bottom:12px;">Keputusan Validasi</h3>

                        <div style="display:flex;gap:12px;margin-bottom:16px;">
                            @if($isAmountMatching)
                            <form action="{{ route('admin.pembayaran.validate', $latestPayment) }}" method="POST" onsubmit="return confirm('Konfirmasi bahwa pembayaran ini valid? Tagihan akan menjadi Lunas, acara dan seluruh pesanan terkonfirmasi, serta nota lunas terbit.');">
                                @csrf
                                <input type="hidden" name="action" value="valid">
                                <button class="btn" type="submit" style="background:#16a34a;border-color:#16a34a;">
                                    Konfirmasi Valid
                                </button>
                            </form>
                            @else
                            <button class="btn" type="button" disabled style="opacity:0.5;cursor:not-allowed;" title="Nominal tidak sesuai">
                                Konfirmasi Valid (Terkunci)
                            </button>
                            @endif
                        </div>

                        <!-- Form Penolakan Pembayaran -->
                        <form action="{{ route('admin.pembayaran.validate', $latestPayment) }}" method="POST" style="background:#fff;border:1px solid #cbd5e1;border-radius:8px;padding:16px;">
                            @csrf
                            <input type="hidden" name="action" value="reject">
                            <h4 style="font-size:13px;color:#991b1b;margin-bottom:8px;">Tolak Bukti Pembayaran</h4>
                            <div class="field" style="margin-bottom:12px;">
                                <label for="admin_note" style="font-size:13px;display:block;margin-bottom:4px;">Alasan / Catatan Penolakan untuk Klien <span class="req">*</span></label>
                                <textarea class="input" id="admin_note" name="admin_note" rows="3" required placeholder="Contoh: Nominal transfer kurang Rp {{ number_format(abs($invoice->transfer_total - $latestPayment->amount), 0, ',', '.') }}. Pastikan 3 digit kode unik ikut ditransfer.">{{ old('admin_note') }}</textarea>
                            </div>
                            <button class="btn btn-danger btn-sm" type="submit" onclick="return confirm('Tolak bukti pembayaran ini? Klien akan menerima notifikasi untuk mengunggah ulang bukti.')">
                                Tolak Bukti Pembayaran
                            </button>
                        </form>
                    </div>
                    @elseif($latestPayment->status === \App\Enums\PaymentStatus::Valid)
                    <div style="background:#f0fdf4;padding:12px;border-radius:8px;font-size:13px;color:#166534;border:1px solid #bbf7d0;">
                        Divalidasi oleh: <b>{{ $latestPayment->confirmedBy?->name ?? 'Admin' }}</b> pada {{ $latestPayment->confirmed_at?->translatedFormat('d M Y, H:i') }} WIB.
                    </div>
                    @elseif($latestPayment->status === \App\Enums\PaymentStatus::Ditolak)
                    <div style="background:#fee2e2;padding:12px;border-radius:8px;font-size:13px;color:#991b1b;border:1px solid #fca5a5;">
                        Ditolak oleh: <b>{{ $latestPayment->confirmedBy?->name ?? 'Admin' }}</b> pada {{ $latestPayment->confirmed_at?->translatedFormat('d M Y, H:i') }} WIB.<br>
                        Catatan: <i>{{ $latestPayment->admin_note }}</i>
                    </div>
                    @endif

                @else
                    <div style="text-align:center;padding:32px 16px;color:var(--sub, #64748b);">
                        <span style="font-size:36px;display:block;margin-bottom:8px;">⏳</span>
                        <p>Klien belum mengunggah bukti transfer untuk tagihan ini.</p>
                        <p style="font-size:12px;margin-top:4px;">Batas bayar: {{ $invoice->due_at?->translatedFormat('d M Y, H:i') ?? '-' }} WIB.</p>
                    </div>
                @endif
            </div>
        </section>
    </div>
    @endif
</main>
@endsection
