<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Lunas {{ $invoice->invoice_number }} - Pesta Kita</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        body { background: #f8fafc; color: #1e293b; padding: 32px 16px; font-size: 14px; line-height: 1.5; }
        .receipt-container { max-width: 800px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); padding: 40px; border: 1px solid #e2e8f0; position: relative; }
        .receipt-header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 24px; border-bottom: 2px solid #f1f5f9; margin-bottom: 24px; }
        .brand h1 { font-size: 24px; color: #4338ca; font-weight: 800; letter-spacing: -0.5px; }
        .brand p { color: #64748b; font-size: 13px; margin-top: 2px; }
        .receipt-meta { text-align: right; }
        .receipt-meta h2 { font-size: 18px; color: #0f172a; font-weight: 700; }
        
        .stamp-lunas {
            display: inline-block;
            border: 3px solid #16a34a;
            color: #16a34a;
            font-size: 22px;
            font-weight: 900;
            padding: 6px 18px;
            border-radius: 8px;
            letter-spacing: 2px;
            text-transform: uppercase;
            transform: rotate(-6deg);
            margin-top: 10px;
        }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px; }
        .info-block h3 { font-size: 12px; text-transform: uppercase; color: #64748b; font-weight: 700; margin-bottom: 6px; letter-spacing: 0.5px; }
        .info-block p { font-size: 14px; color: #334155; }
        .info-block b { color: #0f172a; }

        .payment-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px 20px; margin-bottom: 24px; }
        .payment-box h4 { font-size: 14px; color: #166534; font-weight: 700; margin-bottom: 8px; }
        .payment-box .grid-pay { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; font-size: 13px; }
        .payment-box .grid-pay div span { display: block; color: #15803d; font-size: 11px; font-weight: 600; text-transform: uppercase; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th { background: #f8fafc; text-align: left; padding: 12px 14px; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; font-size: 13px; vertical-align: top; }
        td.num, th.num { text-align: right; }
        
        .summary-wrap { display: flex; justify-content: flex-end; margin-bottom: 32px; }
        .summary-table { width: 340px; border-collapse: collapse; }
        .summary-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        .summary-table tr.total-row td { border-top: 2px solid #cbd5e1; border-bottom: none; font-size: 15px; font-weight: 800; color: #166534; background: #f0fdf4; padding: 12px; }

        .footer-note { text-align: center; color: #64748b; font-size: 12px; margin-top: 24px; border-top: 1px solid #f1f5f9; padding-top: 16px; }

        .print-actions { text-align: center; margin-top: 24px; }
        .btn-print { background: #16a34a; color: #fff; border: none; padding: 10px 24px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; }
        .btn-print:hover { background: #15803d; }
        .btn-back { display: inline-block; margin-left: 12px; color: #475569; text-decoration: none; font-size: 14px; }

        @media print {
            body { background: #fff; padding: 0; }
            .receipt-container { border: none; box-shadow: none; padding: 0; max-width: 100%; }
            .print-actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <div class="brand">
                <h1>Pesta Kita</h1>
                <p>Event Management System &amp; Marketplace</p>
                <p>Website: pesta-kita.local · Email: info@pesta-kita.id</p>
            </div>
            <div class="receipt-meta">
                <h2>NOTA LUNAS (KUITANSI)</h2>
                <p style="font-weight: 700; font-size: 15px; margin-top: 4px; color: #166534;">Nomor Nota: {{ $invoice->receipt_number ?? $invoice->invoice_number }}</p>
                <p style="font-weight: 600; font-size: 13px; color: #475569;">No Tagihan: {{ $invoice->invoice_number }}</p>
                <div>
                    <span class="stamp-lunas">LUNAS</span>
                </div>
            </div>
        </div>

        @php
            $latestValidPayment = $invoice->payments()->where('status', \App\Enums\PaymentStatus::Valid)->latest()->first() ?? $invoice->latestPayment;
        @endphp

        <div class="payment-box">
            <h4>Data Pembayaran Tervalidasi</h4>
            <div class="grid-pay">
                <div>
                    <span>Tanggal Bayar</span>
                    <b>{{ $invoice->paid_at?->translatedFormat('d F Y, H:i') ?? '-' }} WIB</b>
                </div>
                <div>
                    <span>Pengirim</span>
                    <b>{{ $latestValidPayment?->sender_name ?? $invoice->event?->client?->name }}</b>
                </div>
                <div>
                    <span>Rekening Tujuan</span>
                    <b>{{ $latestValidPayment?->companyBankAccount?->bank_name ?? 'Bank' }} · {{ $latestValidPayment?->companyBankAccount?->account_number ?? '-' }}</b>
                </div>
                <div>
                    <span>Total Lunas</span>
                    <b>Rp {{ number_format($invoice->transfer_total, 0, ',', '.') }}</b>
                </div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-block">
                <h3>Klien Penyelenggara</h3>
                <p><b>{{ $invoice->event?->client?->name ?? 'Klien Pesta Kita' }}</b></p>
                <p>{{ $invoice->event?->client?->email ?? '' }}</p>
                <p style="margin-top: 6px;">Acara: <b>{{ $invoice->event?->name }}</b></p>
                <p>Tanggal Acara: {{ $invoice->event?->event_date?->translatedFormat('d F Y') ?? '-' }}</p>
            </div>
            <div class="info-block" style="text-align: right;">
                <h3>Status Pesanan</h3>
                <p>Status Acara: <b style="color: #166534;">Terkonfirmasi</b></p>
                <p>Seluruh item layanan/venue terkunci dan jadwal telah dikonfirmasi ke penyedia jasa.</p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Rincian Layanan / Komponen</th>
                    <th>Jenis</th>
                    <th class="num">Nominal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $idx => $item)
                <tr>
                    <td style="width: 40px;">{{ $idx + 1 }}</td>
                    <td><b>{{ $item->description }}</b></td>
                    <td>{{ $item->type->label() }}</td>
                    <td class="num">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary-wrap">
            <table class="summary-table">
                <tr>
                    <td>Subtotal Layanan Penyedia</td>
                    <td class="num">Rp {{ number_format($invoice->subtotal_provider, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Komisi Platform (10%)</td>
                    <td class="num">Rp {{ number_format($invoice->total_commission, 0, ',', '.') }}</td>
                </tr>
                @if($invoice->cleaning_fee > 0)
                <tr>
                    <td>Biaya Kebersihan Venue</td>
                    <td class="num">Rp {{ number_format($invoice->cleaning_fee, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr style="font-weight: 700;">
                    <td>Total Tagihan</td>
                    <td class="num">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                </tr>
                @if($invoice->unique_code > 0)
                <tr>
                    <td>Kode Unik Transfer</td>
                    <td class="num">Rp {{ number_format($invoice->unique_code, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td>Total Telah Dibayar</td>
                    <td class="num">Rp {{ number_format($invoice->transfer_total, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        <div class="footer-note">
            <p>Dokumen ini adalah bukti transaksi sah yang diterbitkan oleh sistem resmi PT Pesta Kita Indonesia.</p>
            <p>Terima kasih telah mempercayakan acara spesial Anda kepada Pesta Kita.</p>
        </div>

        <div class="print-actions">
            <button class="btn-print" onclick="window.print()">Cetak / Simpan PDF Nota Lunas</button>
            <a href="javascript:history.back()" class="btn-back">Kembali</a>
        </div>
    </div>
</body>
</html>
