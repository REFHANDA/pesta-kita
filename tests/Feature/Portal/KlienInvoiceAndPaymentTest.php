<?php

namespace Tests\Feature\Portal;

use App\Enums\EventStatus;
use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\MediaKind;
use App\Enums\MediaVisibility;
use App\Enums\PaymentStatus;
use App\Models\CompanyBankAccount;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Media;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KlienInvoiceAndPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('commission_percent', 10);
        Setting::set('payment_deadline_hours', 48);
        Setting::set('objection_window_hours', 24);
        Setting::set('unique_code_enabled', true);
    }

    public function test_client_can_view_their_invoice_with_bank_accounts_ordered_by_sort_order(): void
    {
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $klien->id,
            'status' => EventStatus::MenungguPembayaran,
        ]);

        $bank2 = CompanyBankAccount::factory()->create([
            'bank_name' => 'Bank Mandiri',
            'is_active' => true,
            'sort_order' => 2,
        ]);
        $bank1 = CompanyBankAccount::factory()->create([
            'bank_name' => 'BCA',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $bankInactive = CompanyBankAccount::factory()->create([
            'bank_name' => 'BNI Nonaktif',
            'is_active' => false,
            'sort_order' => 0,
        ]);

        $invoice = Invoice::factory()->create([
            'event_id' => $event->id,
            'status' => InvoiceStatus::BelumDibayar,
            'subtotal_provider' => 10_000_000,
            'total_commission' => 1_000_000,
            'cleaning_fee' => 500_000,
            'grand_total' => 11_500_000,
            'unique_code' => 342,
            'transfer_total' => 11_500_342,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'type' => InvoiceItemType::HargaPenyedia,
            'description' => 'Harga penyedia: Sewa Gedung',
            'amount' => 10_000_000,
        ]);

        $response = $this->actingAs($klien)->get(route('klien.tagihan.show', $invoice));

        $response->assertOk();
        $response->assertSee($invoice->invoice_number);
        $response->assertSee('11.500.342');
        $response->assertSee('BCA');
        $response->assertSee('Bank Mandiri');
        $response->assertDontSee('BNI Nonaktif');
    }

    public function test_another_client_cannot_view_invoice(): void
    {
        $klien1 = User::factory()->klien()->create();
        $klien2 = User::factory()->klien()->create();

        $event = Event::factory()->create(['client_id' => $klien1->id]);
        $invoice = Invoice::factory()->create(['event_id' => $event->id]);

        $response = $this->actingAs($klien2)->get(route('klien.tagihan.show', $invoice));
        $response->assertForbidden();
    }

    public function test_client_can_submit_payment_proof(): void
    {
        Storage::fake('private');

        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $klien->id,
            'status' => EventStatus::MenungguPembayaran,
        ]);

        $bank = CompanyBankAccount::factory()->create(['is_active' => true]);
        $invoice = Invoice::factory()->create([
            'event_id' => $event->id,
            'status' => InvoiceStatus::BelumDibayar,
            'transfer_total' => 5_500_123,
        ]);

        $file = UploadedFile::fake()->create('bukti_transfer.jpg', 500, 'image/jpeg');

        $response = $this->actingAs($klien)->post(route('klien.tagihan.pay', $invoice), [
            'company_bank_account_id' => $bank->id,
            'amount' => 5_500_123,
            'sender_name' => 'Budi Santoso',
            'transfer_date' => now()->toDateString(),
            'proof' => $file,
        ]);

        $response->assertRedirect(route('klien.tagihan.show', $invoice));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'company_bank_account_id' => $bank->id,
            'amount' => 5_500_123,
            'sender_name' => 'Budi Santoso',
            'status' => 'menunggu_validasi',
        ]);

        $payment = Payment::query()->where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->proof);
        $this->assertEquals(MediaKind::BuktiPembayaran, $payment->proof->kind);
        $this->assertEquals('private', $payment->proof->disk);

        Storage::disk('private')->assertExists($payment->proof->path);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'menunggu_validasi',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $klien->id,
            'action' => 'payment_submitted',
            'subject_id' => $payment->id,
        ]);
    }

    public function test_client_cannot_upload_invalid_proof_file_format_or_oversized(): void
    {
        Storage::fake('private');

        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $klien->id]);
        $bank = CompanyBankAccount::factory()->create(['is_active' => true]);
        $invoice = Invoice::factory()->create(['event_id' => $event->id]);

        // File txt (tidak diizinkan)
        $invalidFile = UploadedFile::fake()->create('dokumen.txt', 100, 'text/plain');

        $response = $this->actingAs($klien)->post(route('klien.tagihan.pay', $invoice), [
            'company_bank_account_id' => $bank->id,
            'amount' => 1_000_000,
            'sender_name' => 'Budi',
            'transfer_date' => now()->toDateString(),
            'proof' => $invalidFile,
        ]);

        $response->assertSessionHasErrors('proof');
    }

    public function test_client_can_view_print_invoice_html(): void
    {
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $klien->id]);
        $invoice = Invoice::factory()->create([
            'event_id' => $event->id,
            'invoice_number' => 'PK/2026/11/0099',
            'transfer_total' => 12_000_123,
        ]);

        $response = $this->actingAs($klien)->get(route('klien.tagihan.print', $invoice));

        $response->assertOk();
        $response->assertSee('PK/2026/11/0099');
        $response->assertSee('12.000.123');
        $response->assertSee('window.print()');
    }

    public function test_client_cannot_view_receipt_when_invoice_not_paid(): void
    {
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $klien->id]);
        $invoice = Invoice::factory()->create([
            'event_id' => $event->id,
            'status' => InvoiceStatus::BelumDibayar,
        ]);

        $response = $this->actingAs($klien)->get(route('klien.tagihan.receipt', $invoice));
        $response->assertForbidden();
    }

    public function test_client_can_view_receipt_when_invoice_is_paid(): void
    {
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $klien->id]);
        $invoice = Invoice::factory()->create([
            'event_id' => $event->id,
            'invoice_number' => 'PK/2026/11/0077',
            'status' => InvoiceStatus::Lunas,
            'paid_at' => now(),
            'transfer_total' => 15_000_456,
        ]);

        $response = $this->actingAs($klien)->get(route('klien.tagihan.receipt', $invoice));

        $response->assertOk();
        $response->assertSee('NOTA LUNAS');
        $response->assertSee('LUNAS');
        $response->assertSee('PK/2026/11/0077');
        $response->assertSee('15.000.456');
    }

    public function test_another_client_and_vendor_get_403_on_invoice_receipt_and_proof(): void
    {
        Storage::fake('private');

        $klienOwner = User::factory()->klien()->create();
        $otherKlien = User::factory()->klien()->create();
        $vendorUser = User::factory()->vendor()->create();

        $event = Event::factory()->create(['client_id' => $klienOwner->id]);
        $invoice = Invoice::factory()->create([
            'event_id' => $event->id,
            'status' => InvoiceStatus::Lunas,
            'paid_at' => now(),
            'receipt_number' => 'PK/2026/11/0088',
        ]);

        $file = UploadedFile::fake()->create('bukti.jpg', 200, 'image/jpeg');
        $path = $file->store('payments', 'private');
        $media = Media::create([
            'owner_type' => 'payment',
            'owner_id' => 0,
            'kind' => MediaKind::BuktiPembayaran,
            'path' => $path,
            'disk' => 'private',
            'mime_type' => 'image/jpeg',
            'size' => 200,
            'visibility' => MediaVisibility::Privat,
        ]);

        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'proof_media_id' => $media->id,
            'status' => PaymentStatus::Valid,
        ]);
        $media->update(['owner_id' => $payment->id]);

        // 1. Klien lain ditolak (403) pada tagihan, nota, dan bukti
        $this->actingAs($otherKlien)->get(route('klien.tagihan.show', $invoice))->assertForbidden();
        $this->actingAs($otherKlien)->get(route('klien.tagihan.receipt', $invoice))->assertForbidden();
        $this->actingAs($otherKlien)->get(route('media.show', $media))->assertForbidden();

        // 2. Vendor ditolak (403) pada tagihan, nota, dan bukti klien
        $this->actingAs($vendorUser)->get(route('klien.tagihan.show', $invoice))->assertForbidden();
        $this->actingAs($vendorUser)->get(route('klien.tagihan.receipt', $invoice))->assertForbidden();
        $this->actingAs($vendorUser)->get(route('media.show', $media))->assertForbidden();
    }

    public function test_client_cannot_upload_multiple_pending_payments_simultaneously(): void
    {
        Storage::fake('private');

        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $klien->id]);
        $bank = CompanyBankAccount::factory()->create(['is_active' => true]);
        $invoice = Invoice::factory()->create([
            'event_id' => $event->id,
            'status' => InvoiceStatus::MenungguValidasi,
        ]);

        // Sudah ada pembayaran aktif menunggu_validasi
        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'company_bank_account_id' => $bank->id,
            'status' => PaymentStatus::MenungguValidasi,
        ]);

        $file = UploadedFile::fake()->create('bukti2.jpg', 300, 'image/jpeg');

        // Percobaan unggah bukti kedua saat bukti pertama masih menunggu_validasi harus ditolak
        $response = $this->actingAs($klien)->post(route('klien.tagihan.pay', $invoice), [
            'company_bank_account_id' => $bank->id,
            'amount' => 5_000_000,
            'sender_name' => 'Budi',
            'transfer_date' => now()->toDateString(),
            'proof' => $file,
        ]);

        $response->assertSessionHasErrors('proof');
    }
}
