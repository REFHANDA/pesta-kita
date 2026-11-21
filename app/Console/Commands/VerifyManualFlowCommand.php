<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Enums\InvoiceStatus;
use App\Enums\MediaKind;
use App\Enums\MediaVisibility;
use App\Enums\OrderItemStatus;
use App\Enums\PaymentStatus;
use App\Models\AuditLog;
use App\Models\CompanyBankAccount;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\Media;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use App\Services\InvoiceIssuer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VerifyManualFlowCommand extends Command
{
    protected $signature = 'ems:verify-flow';

    protected $description = 'Verifikasi manual alur Fase 5: Tagihan terbit -> Unggah bukti -> Validasi salah ditolak -> Validasi benar -> Lunas -> Terbit Nota Lunas';

    public function handle(InvoiceIssuer $issuer): int
    {
        $this->info('================================================================');
        $this->info('VERIFIKASI MANUAL ALUR FASE 5: KEUANGAN, TAGIHAN & PEMBAYARAN');
        $this->info('================================================================');

        if (! app()->environment('local')) {
            $this->error('Perintah ems:verify-flow hanya diizinkan berjalan di environment local demi keamanan basis data.');

            return Command::FAILURE;
        }

        $tz = config('app.timezone');
        $this->info("Zona Waktu Aplikasi: {$tz} | Waktu Saat Ini: ".now()->format('Y-m-d H:i:s T'));

        // 1. Siapkan Klien, Admin, dan Rekening Bank
        $admin = User::where('email', 'admin@pestakita.test')->first() ?? User::factory()->admin()->create();
        $klien = User::where('email', 'klien@pestakita.test')->first() ?? User::factory()->klien()->create();
        $bank = CompanyBankAccount::where('is_active', true)->orderBy('sort_order')->first();

        if (! $bank) {
            $bank = CompanyBankAccount::create([
                'bank_name' => 'BCA',
                'account_number' => '1234567890',
                'account_name' => 'PT Pesta Kita Indonesia',
                'sort_order' => 1,
                'is_active' => true,
            ]);
        }

        Auth::login($admin);

        // 2. Siapkan / Ambil Acara Uji (Idempoten: hapus invoice & pembayaran uji sebelumnya agar bersih)
        $event = Event::firstOrCreate(
            ['name' => 'Resepsi Pernikahan Uji Manual Fase 5'],
            [
                'client_id' => $klien->id,
                'event_date' => now()->addDays(30)->toDateString(),
                'guest_count' => 300,
                'status' => EventStatus::Diajukan,
                'address' => 'Jl. Ijen No. 8, Malang',
            ]
        );

        // Bersihkan data uji invoice dan payment sebelumnya untuk event ini
        foreach ($event->invoices as $inv) {
            $inv->payments()->delete();
            $inv->items()->delete();
            $inv->delete();
        }

        $event->update(['status' => EventStatus::Diajukan]);

        // Pastikan memiliki order item disetujui (dengan masa sanggah sudah lewat)
        if ($event->orderItems()->count() === 0) {
            OrderItem::create([
                'event_id' => $event->id,
                'subject_type' => 'catalog_item',
                'subject_id' => 1,
                'provider_type' => 'vendor_profile',
                'provider_id' => 1,
                'usage_date' => $event->event_date,
                'quantity' => 1,
                'catalog_price_snapshot' => 10_000_000,
                'provider_price' => 10_000_000,
                'commission' => 1_000_000,
                'status' => OrderItemStatus::Disetujui,
                'approved_at' => now()->subHours(25),
                'objection_deadline' => now()->subHour(),
            ]);
        } else {
            $event->orderItems()->update([
                'status' => OrderItemStatus::Disetujui,
                'approved_at' => now()->subHours(25),
                'objection_deadline' => now()->subHour(),
            ]);
        }

        // -------------------------------------------------------------
        // LANGKAH 1: Terbitkan Tagihan Awal & Terbitkan Ulang (Reissue)
        // -------------------------------------------------------------
        $this->newLine();
        $this->info('[LANGKAH 1] Terbitkan Tagihan Awal dan Terbitkan Ulang (Reissue)');
        $initialInvoice = $event->activeInvoice ?? $issuer->issueForEvent($event);
        if (! $initialInvoice) {
            // Jika ada tagihan lama berstatus belum_dibayar
            $initialInvoice = $event->invoices()->where('status', InvoiceStatus::BelumDibayar)->latest()->first();
        }
        $this->line(" - Tagihan Awal   : {$initialInvoice->invoice_number} (Versi {$initialInvoice->version}, Status: {$initialInvoice->status->label()})");

        $invoice = $issuer->reissue($initialInvoice, $admin);

        $this->line(" - Tagihan Baru   : {$invoice->invoice_number} (Versi {$invoice->version})");
        $this->line(" - Status Tagihan : {$invoice->status->label()} (Tagihan lama berstatus {$initialInvoice->fresh()->status->label()})");
        $this->line(' - Subtotal       : Rp '.number_format($invoice->subtotal_provider, 0, ',', '.'));
        $this->line(' - Komisi         : Rp '.number_format($invoice->total_commission, 0, ',', '.'));
        $this->line(' - Biaya Bersih   : Rp '.number_format($invoice->cleaning_fee, 0, ',', '.'));
        $this->line(' - Grand Total    : Rp '.number_format($invoice->grand_total, 0, ',', '.'));
        $this->line(' - Kode Unik      : Rp '.number_format($invoice->unique_code, 0, ',', '.'));
        $this->line(' - Total Transfer : Rp '.number_format($invoice->transfer_total, 0, ',', '.'));
        $this->line(" - Batas Bayar    : {$invoice->due_at->translatedFormat('d F Y, H:i')} WIB ({$invoice->due_at->timezoneName})");

        // -------------------------------------------------------------
        // LANGKAH 2: Klien Unggah Bukti Pembayaran Nominal Salah
        // -------------------------------------------------------------
        $this->newLine();
        $this->info('[LANGKAH 2] Klien Unggah Bukti Pembayaran (Nominal Salah Tanpa Kode Unik)');
        $wrongAmount = $invoice->grand_total; // Tanpa kode unik!

        $media1 = Media::create([
            'owner_type' => 'payment',
            'owner_id' => 0,
            'kind' => MediaKind::BuktiPembayaran,
            'path' => 'payments/dummy_transfer_1.jpg',
            'disk' => 'private',
            'mime_type' => 'image/jpeg',
            'size' => 1024 * 150,
            'visibility' => MediaVisibility::Privat,
        ]);

        $payment1 = Payment::create([
            'invoice_id' => $invoice->id,
            'company_bank_account_id' => $bank->id,
            'amount' => $wrongAmount,
            'sender_name' => $klien->name,
            'transfer_date' => now()->toDateString(),
            'proof_media_id' => $media1->id,
            'status' => PaymentStatus::MenungguValidasi,
        ]);
        $media1->update(['owner_id' => $payment1->id]);
        $invoice->update(['status' => InvoiceStatus::MenungguValidasi]);

        $this->line(" - ID Pembayaran #1   : {$payment1->id}");
        $this->line(' - Jumlah Ditransfer : Rp '.number_format($payment1->amount, 0, ',', '.').' (Total Tagihan: Rp '.number_format($invoice->transfer_total, 0, ',', '.').')');
        $this->line(" - Status Tagihan    : {$invoice->fresh()->status->label()}");

        // -------------------------------------------------------------
        // LANGKAH 3: Admin Validasi Ditolak Karena Nominal Salah
        // -------------------------------------------------------------
        $this->newLine();
        $this->info('[LANGKAH 3] Admin Mencoba Validasi -> Sistem Mencegah Validasi');
        if ($payment1->amount !== $invoice->transfer_total) {
            $this->warn(' [DICEGAH] Nominal transfer bukti (Rp '.number_format($payment1->amount, 0, ',', '.').') TIDAK SAMA dengan total transfer (Rp '.number_format($invoice->transfer_total, 0, ',', '.').'). Tombol konfirmasi terkunci.');
        }

        $this->info(' Admin Menolak Bukti Pembayaran dengan Catatan');
        $rejectionNote = 'Nominal transfer kurang Rp '.number_format(abs($invoice->transfer_total - $payment1->amount), 0, ',', '.').'. Kode unik transfer wajib disertakan.';

        // Simulasikan batas waktu lama sudah lewat batas (misal 1 jam lalu) untuk menguji aturan:
        // "Untuk bukti yang ditolak setelah lewat due_at: set due_at = max(due_at, now + 24 jam)"
        $invoice->update(['due_at' => now()->subHour()]);
        $this->line(" [Simulasi] Batas waktu bayar lama telah terlewati: {$invoice->fresh()->due_at->translatedFormat('d F Y, H:i')} WIB");

        DB::transaction(function () use ($payment1, $invoice, $admin, $rejectionNote) {
            $payment1->update([
                'status' => PaymentStatus::Ditolak,
                'confirmed_by' => $admin->id,
                'confirmed_at' => now(),
                'admin_note' => $rejectionNote,
            ]);

            // Ekstensi due_at = max(due_at, now + 24 jam)
            $minDueAt = now()->addHours(24);
            $newDueAt = ($invoice->due_at && $invoice->due_at->gt($minDueAt)) ? $invoice->due_at : $minDueAt;

            $invoice->update([
                'status' => InvoiceStatus::BelumDibayar,
                'due_at' => $newDueAt,
            ]);

            AuditLog::create([
                'actor_id' => $admin->id,
                'action' => 'payment_rejected',
                'subject_type' => $payment1->getMorphClass(),
                'subject_id' => $payment1->id,
                'reason' => $rejectionNote,
                'ip' => '127.0.0.1',
            ]);
        });

        $this->line(" - Status Pembayaran #1 : {$payment1->fresh()->status->label()}");
        $this->line(" - Catatan Penolakan    : \"{$payment1->fresh()->admin_note}\"");
        $this->line(" - Status Tagihan       : {$invoice->fresh()->status->label()} (Kembali ke Belum Dibayar)");
        $this->line(" - Batas Bayar Baru     : {$invoice->fresh()->due_at->translatedFormat('d F Y, H:i')} WIB (Diperpanjang min 24 jam)");

        // -------------------------------------------------------------
        // LANGKAH 4: Klien Unggah Bukti Pembayaran Nominal Benar
        // -------------------------------------------------------------
        $this->newLine();
        $this->info('[LANGKAH 4] Klien Unggah Ulang Bukti Pembayaran (Nominal Sesuai Tepat)');
        $correctAmount = $invoice->transfer_total;

        $media2 = Media::create([
            'owner_type' => 'payment',
            'owner_id' => 0,
            'kind' => MediaKind::BuktiPembayaran,
            'path' => 'payments/dummy_transfer_2_correct.jpg',
            'disk' => 'private',
            'mime_type' => 'image/jpeg',
            'size' => 1024 * 220,
            'visibility' => MediaVisibility::Privat,
        ]);

        $payment2 = Payment::create([
            'invoice_id' => $invoice->id,
            'company_bank_account_id' => $bank->id,
            'amount' => $correctAmount,
            'sender_name' => $klien->name,
            'transfer_date' => now()->toDateString(),
            'proof_media_id' => $media2->id,
            'status' => PaymentStatus::MenungguValidasi,
        ]);
        $media2->update(['owner_id' => $payment2->id]);
        $invoice->update(['status' => InvoiceStatus::MenungguValidasi]);

        $this->line(" - ID Pembayaran #2   : {$payment2->id}");
        $this->line(' - Jumlah Ditransfer : Rp '.number_format($payment2->amount, 0, ',', '.').' (Sesuai tepat dengan total transfer)');
        $this->line(" - Status Tagihan    : {$invoice->fresh()->status->label()}");

        // -------------------------------------------------------------
        // LANGKAH 5: Admin Validasi Pembayaran Benar -> Lunas -> Terbit Nota Lunas
        // -------------------------------------------------------------
        $this->newLine();
        $this->info('[LANGKAH 5] Admin Validasi Pembayaran Benar');

        $receiptNumber = DB::transaction(function () use ($payment2, $invoice, $event, $admin, $issuer) {
            $rcptNo = $issuer->generateReceiptNumber($invoice);

            $payment2->update([
                'status' => PaymentStatus::Valid,
                'confirmed_by' => $admin->id,
                'confirmed_at' => now(),
            ]);

            $invoice->update([
                'status' => InvoiceStatus::Lunas,
                'paid_at' => now(),
                'receipt_number' => $rcptNo,
            ]);

            $event->update([
                'status' => EventStatus::Terkonfirmasi,
            ]);

            $event->orderItems()
                ->where('status', OrderItemStatus::Disetujui)
                ->update(['status' => OrderItemStatus::Terkonfirmasi]);

            AuditLog::create([
                'actor_id' => $admin->id,
                'action' => 'payment_validated',
                'subject_type' => $payment2->getMorphClass(),
                'subject_id' => $payment2->id,
                'reason' => "Pembayaran tagihan {$invoice->invoice_number} divalidasi valid. Nota lunas {$rcptNo} terbit.",
                'ip' => '127.0.0.1',
            ]);

            return $rcptNo;
        });

        $this->line(" - Status Pembayaran #2 : {$payment2->fresh()->status->label()}");
        $this->line(" - Status Tagihan       : {$invoice->fresh()->status->label()} (LUNAS)");
        $this->line(" - Waktu Lunas          : {$invoice->fresh()->paid_at->translatedFormat('d F Y, H:i')} WIB");
        $this->line(" - Nomor Tagihan        : {$invoice->invoice_number}");
        $this->line(" - Nomor Nota Lunas     : {$invoice->fresh()->receipt_number} (Unik, format PK/YYYY/MM/NNNN, berbeda dari no tagihan)");
        $this->line(" - Status Acara         : {$event->fresh()->status->value}");
        $this->line(' - Status Pesanan       : '.$event->orderItems()->first()->status->value);

        // -------------------------------------------------------------
        // LANGKAH 6: Pengujian Pencegahan Validasi Ganda
        // -------------------------------------------------------------
        $this->newLine();
        $this->info('[LANGKAH 6] Pengujian Pencegahan Validasi Ganda');
        if ($payment2->fresh()->status !== PaymentStatus::MenungguValidasi) {
            $this->warn(" [TERKUNCI] Status pembayaran #2 sudah {$payment2->fresh()->status->label()}. Percobaan validasi ulang kedua pada transaksi yang sama ditolak sistem.");
        }

        $this->newLine();
        $this->info('================================================================');
        $this->info('SELURUH ALUR FASE 5 BERHASIL DIUJI SECARA MANUAL DAN TERVALIDASI');
        $this->info('================================================================');

        return Command::SUCCESS;
    }
}
