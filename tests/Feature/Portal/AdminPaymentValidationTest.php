<?php

namespace Tests\Feature\Portal;

use App\Enums\EventStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderItemStatus;
use App\Enums\PaymentStatus;
use App\Models\CompanyBankAccount;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\OrderConfirmedNotification;
use App\Notifications\PaymentRejectedNotification;
use App\Notifications\PaymentValidatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminPaymentValidationTest extends TestCase
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

    public function test_admin_can_view_payment_list_with_tabs_and_metrics(): void
    {
        $admin = User::factory()->admin()->create();

        $invoice1 = Invoice::factory()->create([
            'status' => InvoiceStatus::MenungguValidasi,
            'transfer_total' => 10_000_123,
        ]);
        $invoice2 = Invoice::factory()->create([
            'status' => InvoiceStatus::BelumDibayar,
            'transfer_total' => 5_000_456,
        ]);
        $invoice3 = Invoice::factory()->create([
            'status' => InvoiceStatus::Lunas,
            'paid_at' => now(),
            'transfer_total' => 8_000_789,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.pembayaran.index'));

        $response->assertOk();
        $response->assertSee('Menunggu pembayaran');
        $response->assertSee('Bukti perlu validasi');
        $response->assertSee('Lunas bulan ini');
        $response->assertSee($invoice1->invoice_number);
        $response->assertSee($invoice2->invoice_number);
        $response->assertSee($invoice3->invoice_number);
    }

    public function test_admin_can_view_payment_detail(): void
    {
        $admin = User::factory()->admin()->create();
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $klien->id]);

        $bank = CompanyBankAccount::factory()->create(['bank_name' => 'BCA']);
        $invoice = Invoice::factory()->create([
            'event_id' => $event->id,
            'status' => InvoiceStatus::MenungguValidasi,
            'transfer_total' => 11_500_342,
        ]);

        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'company_bank_account_id' => $bank->id,
            'amount' => 11_500_342,
            'sender_name' => 'Rina Putri',
            'status' => PaymentStatus::MenungguValidasi,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.pembayaran.show', $invoice));

        $response->assertOk();
        $response->assertSee($invoice->invoice_number);
        $response->assertSee('11.500.342');
        $response->assertSee('Rina Putri');
        $response->assertSee('BCA');
    }

    public function test_admin_cannot_validate_payment_if_amount_does_not_match_transfer_total(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $klien->id]);

        $invoice = Invoice::factory()->create([
            'event_id' => $event->id,
            'status' => InvoiceStatus::MenungguValidasi,
            'transfer_total' => 11_500_342,
        ]);

        // Klien mentransfer kurang (11.500.000 tanpa kode unik 342)
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 11_500_000,
            'status' => PaymentStatus::MenungguValidasi,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.pembayaran.validate', $payment), [
            'action' => 'valid',
        ]);

        $response->assertSessionHasErrors('amount');

        // Status pembayaran dan tagihan harus tetap menunggu_validasi
        $this->assertEquals(PaymentStatus::MenungguValidasi, $payment->fresh()->status);
        $this->assertEquals(InvoiceStatus::MenungguValidasi, $invoice->fresh()->status);

        Notification::assertNothingSent();
    }

    public function test_admin_validates_matching_payment_successfully(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $klien->id,
            'status' => EventStatus::MenungguPembayaran,
        ]);

        $vendorUser = User::factory()->vendor()->create();
        $vendorProfile = $vendorUser->vendorProfile;

        $item = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendorProfile->id,
            'status' => OrderItemStatus::Disetujui,
            'provider_price' => 5_000_000,
            'commission' => 500_000,
        ]);

        $invoice = Invoice::factory()->create([
            'event_id' => $event->id,
            'status' => InvoiceStatus::MenungguValidasi,
            'grand_total' => 5_500_000,
            'unique_code' => 123,
            'transfer_total' => 5_500_123,
        ]);

        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 5_500_123,
            'status' => PaymentStatus::MenungguValidasi,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.pembayaran.validate', $payment), [
            'action' => 'valid',
        ]);

        $response->assertRedirect(route('admin.pembayaran.show', $invoice));
        $response->assertSessionHas('success');

        // Status pembayaran -> valid
        $this->assertEquals(PaymentStatus::Valid, $payment->fresh()->status);
        $this->assertEquals($admin->id, $payment->fresh()->confirmed_by);
        $this->assertNotNull($payment->fresh()->confirmed_at);

        // Status tagihan -> lunas
        $this->assertEquals(InvoiceStatus::Lunas, $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->paid_at);

        // Status acara -> terkonfirmasi
        $this->assertEquals(EventStatus::Terkonfirmasi, $event->fresh()->status);

        // Status pesanan disetujui -> terkonfirmasi
        $this->assertEquals(OrderItemStatus::Terkonfirmasi, $item->fresh()->status);

        // Audit log ditulis
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'payment_validated',
            'subject_id' => $payment->id,
        ]);

        // Notifikasi ke klien
        Notification::assertSentTo($klien, PaymentValidatedNotification::class);

        // Notifikasi ke penyedia
        Notification::assertSentTo($vendorUser, OrderConfirmedNotification::class);
    }

    public function test_admin_rejects_payment_with_note(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $klien->id,
            'status' => EventStatus::MenungguPembayaran,
        ]);

        $invoice = Invoice::factory()->create([
            'event_id' => $event->id,
            'status' => InvoiceStatus::MenungguValidasi,
            'transfer_total' => 10_000_123,
        ]);

        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 10_000_000,
            'status' => PaymentStatus::MenungguValidasi,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.pembayaran.validate', $payment), [
            'action' => 'reject',
            'admin_note' => 'Nominal kurang Rp 123. Pastikan kode unik ikut ditransfer.',
        ]);

        $response->assertRedirect(route('admin.pembayaran.show', $invoice));
        $response->assertSessionHas('success');

        // Payment status -> ditolak
        $this->assertEquals(PaymentStatus::Ditolak, $payment->fresh()->status);
        $this->assertEquals('Nominal kurang Rp 123. Pastikan kode unik ikut ditransfer.', $payment->fresh()->admin_note);
        $this->assertEquals($admin->id, $payment->fresh()->confirmed_by);

        // Tagihan kembali ke belum_dibayar agar klien dapat unggah ulang
        $this->assertEquals(InvoiceStatus::BelumDibayar, $invoice->fresh()->status);

        // Audit log ditulis
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'payment_rejected',
            'subject_id' => $payment->id,
        ]);

        // Notifikasi ke klien
        Notification::assertSentTo($klien, PaymentRejectedNotification::class);
    }

    public function test_admin_can_view_print_invoice_and_receipt(): void
    {
        $admin = User::factory()->admin()->create();
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $klien->id]);

        $invoice = Invoice::factory()->create([
            'event_id' => $event->id,
            'status' => InvoiceStatus::Lunas,
            'paid_at' => now(),
        ]);

        $respPrint = $this->actingAs($admin)->get(route('admin.tagihan.print', $invoice));
        $respPrint->assertOk();

        $respReceipt = $this->actingAs($admin)->get(route('admin.tagihan.receipt', $invoice));
        $respReceipt->assertOk();
        $respReceipt->assertSee('NOTA LUNAS');
    }

    public function test_duplicate_payment_validation_does_not_process_twice(): void
    {
        $admin = User::factory()->admin()->create();
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $klien->id]);

        $invoice = Invoice::factory()->create([
            'event_id' => $event->id,
            'status' => InvoiceStatus::Lunas,
            'transfer_total' => 5_000_123,
            'paid_at' => now(),
        ]);

        // Pembayaran sudah valid sebelumnya
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 5_000_123,
            'status' => PaymentStatus::Valid,
            'confirmed_by' => $admin->id,
            'confirmed_at' => now(),
        ]);

        // Percobaan validasi kedua pada pembayaran yang sama harus ditolak
        $response = $this->actingAs($admin)->post(route('admin.pembayaran.validate', $payment), [
            'action' => 'valid',
        ]);

        $response->assertSessionHasErrors('action');
    }

    public function test_nota_lunas_receives_unique_receipt_number_different_from_invoice_number(): void
    {
        $admin = User::factory()->admin()->create();
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $klien->id,
            'status' => EventStatus::MenungguPembayaran,
        ]);

        $invoice = Invoice::factory()->create([
            'event_id' => $event->id,
            'invoice_number' => 'PK/2026/11/0001',
            'status' => InvoiceStatus::MenungguValidasi,
            'transfer_total' => 7_500_000,
        ]);

        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 7_500_000,
            'status' => PaymentStatus::MenungguValidasi,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.pembayaran.validate', $payment), [
            'action' => 'valid',
        ]);

        $response->assertSessionHas('success');

        $refreshedInvoice = $invoice->fresh();
        $this->assertEquals(InvoiceStatus::Lunas, $refreshedInvoice->status);
        $this->assertNotNull($refreshedInvoice->receipt_number);
        $this->assertNotEquals($refreshedInvoice->invoice_number, $refreshedInvoice->receipt_number);
        $this->assertMatchesRegularExpression('/^PK\/\d{4}\/\d{2}\/\d{4}$/', $refreshedInvoice->receipt_number);
    }

    public function test_rejected_payment_extends_due_at_by_at_least_24_hours(): void
    {
        $admin = User::factory()->admin()->create();
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $klien->id,
            'status' => EventStatus::MenungguPembayaran,
        ]);

        // Tagihan dengan due_at yang sudah lewat (misal 2 jam lalu)
        $pastDueAt = now()->subHours(2);
        $invoice = Invoice::factory()->create([
            'event_id' => $event->id,
            'status' => InvoiceStatus::MenungguValidasi,
            'transfer_total' => 10_000_123,
            'due_at' => $pastDueAt,
        ]);

        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 10_000_000,
            'status' => PaymentStatus::MenungguValidasi,
        ]);

        $this->actingAs($admin)->post(route('admin.pembayaran.validate', $payment), [
            'action' => 'reject',
            'admin_note' => 'Nominal kurang Rp 123. Silakan transfer ulang kekurangannya.',
        ]);

        $refreshedInvoice = $invoice->fresh();
        $this->assertEquals(InvoiceStatus::BelumDibayar, $refreshedInvoice->status);
        // due_at baru harus minimal now + 24 jam (paling tidak 23 jam ke depan dari sekarang)
        $this->assertTrue($refreshedInvoice->due_at->gte(now()->addHours(23)));
    }

    public function test_rejection_extension_limit_max_two_times(): void
    {
        $admin = User::factory()->admin()->create();
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $klien->id]);

        $invoice = Invoice::factory()->create([
            'event_id' => $event->id,
            'status' => InvoiceStatus::MenungguValidasi,
            'transfer_total' => 5_000_123,
            'due_at' => now()->subHour(),
            'issued_at' => now()->subDays(1),
        ]);

        // Penolakan 1: diperpanjang (1 kali)
        $p1 = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 5_000_000,
            'status' => PaymentStatus::MenungguValidasi,
        ]);
        $this->actingAs($admin)->post(route('admin.pembayaran.validate', $p1), [
            'action' => 'reject',
            'admin_note' => 'Penolakan ke-1',
        ]);
        $dueAtAfter1 = $invoice->fresh()->due_at;
        $this->assertTrue($dueAtAfter1->gte(now()->addHours(23)));

        // Set due_at kembali ke masa lalu dan simulasikan unggah bukti ke-2
        Invoice::where('id', $invoice->id)->update([
            'status' => InvoiceStatus::MenungguValidasi,
            'due_at' => now()->subHour(),
        ]);
        $p2 = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 5_000_000,
            'status' => PaymentStatus::MenungguValidasi,
        ]);
        // Penolakan 2: diperpanjang (2 kali)
        $this->actingAs($admin)->post(route('admin.pembayaran.validate', $p2), [
            'action' => 'reject',
            'admin_note' => 'Penolakan ke-2',
        ]);
        $dueAtAfter2 = $invoice->fresh()->due_at;
        $this->assertTrue($dueAtAfter2->gte(now()->addHours(23)));

        // Set due_at kembali ke masa lalu dan simulasikan unggah bukti ke-3
        $overdueTime = now()->subHour()->startOfSecond();
        Invoice::where('id', $invoice->id)->update([
            'status' => InvoiceStatus::MenungguValidasi,
            'due_at' => $overdueTime,
        ]);
        $p3 = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 5_000_000,
            'status' => PaymentStatus::MenungguValidasi,
        ]);
        // Penolakan 3: SUDAH MELEWATI MAKSIMAL 2 KALI -> TIDAK DIPERPANJANG LAGI
        $this->actingAs($admin)->post(route('admin.pembayaran.validate', $p3), [
            'action' => 'reject',
            'admin_note' => 'Penolakan ke-3 (Batas tercapai)',
        ]);

        // due_at tidak diperpanjang, tetap di waktu lampau sehingga tagihan bisa kedaluwarsa
        $dueAtAfter3 = $invoice->fresh()->due_at;
        $this->assertEquals($overdueTime->toDateTimeString(), $dueAtAfter3->toDateTimeString());
    }

    public function test_rejection_extension_capped_at_seven_days_from_issued_at(): void
    {
        $admin = User::factory()->admin()->create();
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $klien->id]);

        // Tagihan diterbitkan 6 hari 12 jam yang lalu (hanya tersisa 12 jam sebelum genap 7 hari sejak issued_at)
        $issuedAt = now()->subDays(6)->subHours(12);
        $maxCap = $issuedAt->copy()->addDays(7); // 12 jam dari sekarang

        $invoice = Invoice::factory()->create([
            'event_id' => $event->id,
            'status' => InvoiceStatus::MenungguValidasi,
            'transfer_total' => 8_000_123,
            'due_at' => now()->subHour(),
            'issued_at' => $issuedAt,
        ]);

        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 8_000_000,
            'status' => PaymentStatus::MenungguValidasi,
        ]);

        // Walau normalnya ditambah 24 jam, batas perpanjangan terbentur plafon 7 hari sejak issued_at
        $this->actingAs($admin)->post(route('admin.pembayaran.validate', $payment), [
            'action' => 'reject',
            'admin_note' => 'Nominal salah',
        ]);

        $dueAt = $invoice->fresh()->due_at;
        // due_at harus dicap pada issued_at + 7 hari (tidak boleh melebihi maxCap)
        $this->assertEquals($maxCap->toDateTimeString(), $dueAt->toDateTimeString());
    }
}
