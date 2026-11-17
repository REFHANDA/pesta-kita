<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Models\CatalogItem;
use App\Models\CompanyBankAccount;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\RefundRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoiceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_issues_a_snapshot_invoice_after_all_objection_windows_close(): void
    {
        $this->travelTo('2030-01-01 10:00:00');
        $admin = User::factory()->admin()->create();
        $event = Event::factory()->diajukan()->create(['event_date' => '2030-01-10']);
        $catalogItem = CatalogItem::factory()->create(['name' => 'Paket katering']);
        $orderItem = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $catalogItem->vendor_profile_id,
            'subject_type' => 'catalog_item',
            'subject_id' => $catalogItem->id,
            'status' => OrderItemStatus::MasaKeberatan,
            'final_price' => 1_234_567,
            'objection_deadline' => now()->subSecond(),
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.tagihan.issue', $event));
        $response->assertRedirect(route('admin.pembayaran.detail', Invoice::query()->value('id') ?? 0));
        $invoice = Invoice::query()->sole();

        $this->assertSame(123_456, $invoice->commission_total);
        $this->assertSame(0, $invoice->cleaning_total);
        $this->assertGreaterThanOrEqual(100, $invoice->unique_code);
        $this->assertLessThanOrEqual(999, $invoice->unique_code);
        $this->assertSame($invoice->unique_code, $invoice->active_code);
        $this->assertSame(1_358_023 + $invoice->unique_code, $invoice->total_amount);
        $this->assertSame('2030-01-03 23:59', $invoice->due_at->format('Y-m-d H:i'));
        $this->assertSame(EventStatus::MenungguPembayaran, $event->fresh()->status);
        $this->assertSame(OrderItemStatus::Mengikat, $orderItem->fresh()->status);
        $this->assertDatabaseHas('invoice_lines', [
            'invoice_id' => $invoice->id,
            'order_item_id' => $orderItem->id,
            'description_snapshot' => 'Paket katering',
            'provider_amount' => 1_234_567,
            'commission_amount' => 123_456,
            'total_amount' => 1_358_023,
        ]);
    }

    public function test_invoice_issued_after_h_minus_seven_is_due_in_twenty_four_hours(): void
    {
        $this->travelTo('2030-01-04 03:00:00');
        $admin = User::factory()->admin()->create();
        $event = Event::factory()->diajukan()->create(['event_date' => '2030-01-10']);
        $catalogItem = CatalogItem::factory()->create();
        OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $catalogItem->vendor_profile_id,
            'subject_type' => 'catalog_item',
            'subject_id' => $catalogItem->id,
            'status' => OrderItemStatus::MasaKeberatan,
            'final_price' => 1_000_000,
            'objection_deadline' => now()->subSecond(),
        ]);

        $this->actingAs($admin)->post(route('admin.tagihan.issue', $event))->assertRedirect();

        $this->assertSame(
            '2030-01-05 10:00',
            Invoice::query()->sole()->due_at->format('Y-m-d H:i'),
        );
    }

    public function test_client_submits_a_private_payment_proof_for_an_owned_open_invoice(): void
    {
        $this->travelTo('2030-01-01 10:00:00');
        Storage::fake('private');
        $client = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $client->id,
            'status' => EventStatus::MenungguPembayaran,
        ]);
        $invoice = $this->createInvoice($event, ['due_at' => now()->addDay()]);
        $account = CompanyBankAccount::factory()->create();

        $response = $this->actingAs($client)->post(
            route('klien.tagihan.pembayaran.store', $invoice),
            [
                'company_bank_account_id' => $account->id,
                'sender_name' => 'Nama Pengirim',
                'transferred_at' => '2030-01-01',
                'amount' => $invoice->total_amount,
                'proof' => UploadedFile::fake()->createWithContent('bukti.pdf', '%PDF-1.4 payment proof'),
            ],
        );
        $response->assertRedirect(route('klien.tagihan', $invoice));
        $payment = Payment::query()->sole();

        $this->assertSame('menunggu_verifikasi', $payment->status);
        $this->assertSame($invoice->id, $payment->active_invoice_id);
        $this->assertTrue(Storage::disk('private')->exists($payment->proof_path));
    }

    public function test_admin_cannot_approve_a_payment_with_a_mismatched_proof_amount(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::factory()->create(['status' => EventStatus::MenungguPembayaran]);
        $invoice = $this->createInvoice($event, ['total_amount' => 1_000_123]);
        $payment = Payment::query()->create([
            'invoice_id' => $invoice->id,
            'active_invoice_id' => $invoice->id,
            'bank_name_snapshot' => 'BCA',
            'account_number_snapshot' => '1234567890',
            'account_name_snapshot' => 'PT Pesta Kita',
            'sender_name' => 'Nama Pengirim',
            'amount' => 900_000,
            'transferred_at' => now(),
            'proof_disk' => 'private',
            'proof_path' => 'payment-proofs/proof.pdf',
            'status' => 'menunggu_verifikasi',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.pembayaran.detail', $invoice))
            ->post(route('admin.pembayaran.decide', [$payment, 'approve']), [
                'received_amount' => $invoice->total_amount,
            ]);

        $response->assertSessionHasErrors('received_amount');
        $this->assertSame('terbit', $invoice->fresh()->status);
        $this->assertSame('menunggu_verifikasi', $payment->fresh()->status);
        $this->assertSame(EventStatus::MenungguPembayaran, $event->fresh()->status);
    }

    public function test_expired_invoice_rejects_new_proof_without_cancelling_the_event(): void
    {
        $this->travelTo('2030-01-01 10:00:00');
        Storage::fake('private');
        $client = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $client->id,
            'status' => EventStatus::MenungguPembayaran,
        ]);
        $invoice = $this->createInvoice($event, [
            'active_code' => 321,
            'due_at' => now()->subSecond(),
        ]);
        $account = CompanyBankAccount::factory()->create();

        $response = $this->actingAs($client)
            ->from(route('klien.tagihan', $invoice))
            ->post(route('klien.tagihan.pembayaran.store', $invoice), [
                'company_bank_account_id' => $account->id,
                'sender_name' => 'Nama Pengirim',
                'transferred_at' => '2030-01-01',
                'amount' => $invoice->total_amount,
                'proof' => UploadedFile::fake()->createWithContent('bukti.pdf', '%PDF-1.4 payment proof'),
            ]);

        $response->assertSessionHasErrors('invoice');
        $this->assertSame('kedaluwarsa', $invoice->fresh()->status);
        $this->assertNull($invoice->fresh()->active_code);
        $this->assertSame(EventStatus::MenungguPembayaran, $event->fresh()->status);
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame([], Storage::disk('private')->allFiles());
    }

    public function test_client_can_submit_refund_intake_without_a_decision_or_amount(): void
    {
        $client = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $client->id,
            'status' => EventStatus::Terkonfirmasi,
        ]);
        $invoice = $this->createInvoice($event, ['status' => 'lunas', 'active_code' => null]);

        $response = $this->actingAs($client)->post(route('klien.refund.store', $event), [
            'reason' => 'Rencana acara berubah karena kondisi keluarga.',
        ]);
        $refundRequest = $event->refundRequest()->firstOrFail();

        $response->assertRedirect(route('klien.refund.create', $event));
        $this->assertSame('diajukan', $refundRequest->status);
        $this->assertSame($invoice->id, $refundRequest->invoice_id);
        $this->assertDatabaseHas('refund_requests', [
            'id' => $refundRequest->id,
            'status' => 'diajukan',
            'reason' => 'Rencana acara berubah karena kondisi keluarga.',
        ]);
    }

    public function test_admin_can_review_refund_intake_without_decision_controls(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $client->id]);
        $invoice = $this->createInvoice($event, ['status' => 'lunas', 'active_code' => null]);
        $refundRequest = RefundRequest::query()->create([
            'event_id' => $event->id,
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'reason' => 'Rencana acara berubah karena kondisi keluarga.',
            'status' => 'diajukan',
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('admin.pembatalan.detail', $refundRequest))
            ->assertOk()
            ->assertDontSee('Setujui pengajuan')
            ->assertDontSee('Persentase refund');
    }

    /**
     * Create an invoice row for request tests that do not exercise issuance.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function createInvoice(Event $event, array $attributes = []): Invoice
    {
        return $event->invoices()->create($attributes + [
            'version' => 1,
            'unique_code' => 321,
            'active_code' => 321,
            'provider_subtotal' => 1_000_000,
            'commission_total' => 100_000,
            'cleaning_total' => 0,
            'total_amount' => 1_100_321,
            'status' => 'terbit',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);
    }
}
