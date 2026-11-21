<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tagihan {{ $invoice->invoice_number }} - Pesta Kita</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        body { background: #f8fafc; color: #1e293b; padding: 32px 16px; font-size: 14px; line-height: 1.5; }
        .invoice-container { max-width: 800px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); padding: 40px; border: 1px solid #e2e8f0; }
        .invoice-header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 24px; border-bottom: 2px solid #f1f5f9; margin-bottom: 24px; }
        .brand h1 { font-size: 24px; color: #4338ca; font-weight: 800; letter-spacing: -0.5px; }
        .brand p { color: #64748b; font-size: 13px; margin-top: 2px; }
        .invoice-meta { text-align: right; }
        .invoice-meta h2 { font-size: 18px; color: #0f172a; font-weight: 700; }
        .invoice-meta .status { display: inline-block; padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-top: 6px; }
        .status-belum_dibayar { background: #fef3c7; color: #92400e; }
        .status-menunggu_validasi { background: #e0e7ff; color: #3730a3; }
        .status-lunas { background: #dcfce7; color: #166534; }
        .status-kedaluwarsa, .status-dibatalkan { background: #fee2e2; color: #991b1b; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px; }
        .info-block h3 { font-size: 12px; text-transform: uppercase; color: #64748b; font-weight: 700; margin-bottom: 6px; letter-spacing: 0.5px; }
        .info-block p { font-size: 14px; color: #334155; }
        .info-block b { color: #0f172a; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th { background: #f8fafc; text-align: left; padding: 12px 14px; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; font-size: 13px; vertical-align: top; }
        td.num, th.num { text-align: right; }
        
        .summary-wrap { display: flex; justify-content: flex-end; margin-bottom: 32px; }
        .summary-table { width: 340px; border-collapse: collapse; }
        .summary-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        .summary-table tr.total-row td { border-top: 2px solid #cbd5e1; border-bottom: none; font-size: 15px; font-weight: 800; color: #1e1b4b; background: #f8fafc; padding: 12px; }

        .payment-instructions { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 24px; }
        .payment-instructions h3 { font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 8px; }
        .payment-instructions p { font-size: 13px; color: #475569; line-height: 1.6; }

        .print-actions { text-align: center; margin-top: 24px; }
        .btn-print { background: #4f46e5; color: #fff; border: none; padding: 10px 24px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; }
        .btn-print:hover { background: #4338ca; }
        .btn-back { display: inline-block; margin-left: 12px; color: #475569; text-decoration: none; font-size: 14px; }

        @media print {
            body { background: #fff; padding: 0; }
            .invoice-container { border: none; box-shadow: none; padding: 0; max-width: 100%; }
            .print-actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <div class="brand">
                <h1>Pesta Kita</h1>
                <p>Event Management System &amp; Marketplace</p>
                <p>Website: pesta-kita.local · Email: info@pesta-kita.id</p>
            </div>
            <div class="invoice-meta">
                <h2>TAGIHAN RESMI</h2>
                <p style="font-weight: 700; font-size: 15px; margin-top: 4px;">{{ $invoice->invoice_number }}</p>
                <span class="status status-{{ $invoice->status->value }}">{{ $invoice->status->label() }}</span>
                <p style="color: #64748b; font-size: 12px; margin-top: 6px;">Versi {{ $invoice->version }}</p>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-block">
                <h3>Ditujukan Kepada</h3>
                <p><b>{{ $invoice->event?->client?->name ?? 'Klien Pesta Kita' }}</b></p>
                <p>{{ $invoice->event?->client?->email ?? '' }}</p>
                <p style="margin-top: 6px;">Acara: <b>{{ $invoice->event?->name }}</b></p>
                <p>Tanggal Acara: {{ $invoice->event?->event_date?->translatedFormat('d F Y') ?? '-' }}</p>
            </div>
            <div class="info-block" style="text-align: right;">
                <h3>Informasi Pembayaran</h3>
                <p>Tanggal Terbit: <b>{{ $invoice->issued_at?->translatedFormat('d M Y, H:i') ?? '-' }} WIB</b></p>
                <p>Batas Pembayaran: <b>{{ $invoice->due_at?->translatedFormat('d M Y, H:i') ?? '-' }} WIB</b></p>
                @if($invoice->status === \App\Enums\InvoiceStatus::Lunas && $invoice->paid_at)
                    <p style="color: #166534; font-weight: 700; margin-top: 4px;">Lunas Pada: {{ $invoice->paid_at->translatedFormat('d M Y, H:i') }} WIB</p>
                @endif
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
                    <td>Subtotal Harga Penyedia</td>
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
                    <td>Total Tagihan (Grand Total)</td>
                    <td class="num">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                </tr>
                @if($invoice->unique_code > 0)
                <tr>
                    <td>Kode Unik Transfer</td>
                    <td class="num">Rp {{ number_format($invoice->unique_code, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td>Total Transfer</td>
                    <td class="num">Rp {{ number_format($invoice->transfer_total, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        <div class="payment-instructions">
            <h3>Petunjuk Pembayaran Transfer:</h3>
            <p>1. Transfer tepat sesuai dengan <b>Total Transfer (Rp {{ number_format($invoice->transfer_total, 0, ',', '.') }})</b> termasuk digit kode unik.</p>
            <p>2. Transfer dilakukan 100% penuh dalam satu transaksi ke salah satu rekening resmi PT Pesta Kita Indonesia.</p>
            <p>3. Unggah bukti transfer di portal klien sebelum batas waktu berakhir ({{ $invoice->due_at?->translatedFormat('d M Y, H:i') }} WIB).</p>
        </div>

        <div class="print-actions">
            <button class="btn-print" onclick="window.print()">Cetak / Simpan PDF</button>
            <a href="javascript:history.back()" class="btn-back">Kembali</a>
        </div>
    </div>
</body>
</html>
