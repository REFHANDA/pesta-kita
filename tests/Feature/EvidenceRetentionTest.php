<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EvidenceRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_payment_and_payout_proofs_are_removed_only_after_five_years_closed(): void
    {
        $this->travelTo('2036-01-01 10:00:00');
        Storage::fake('private');
        $admin = User::factory()->admin()->create();
        $oldEvent = Event::factory()->create([
            'status' => EventStatus::Selesai,
            'event_date' => '2030-01-01',
            'closed_at' => now()->subYears(5)->subDay(),
        ]);
        $recentEvent = Event::factory()->create([
            'status' => EventStatus::Selesai,
            'event_date' => '2035-01-01',
            'closed_at' => now()->subYears(5)->addDay(),
        ]);
        $oldInvoice = $this->createPaidInvoice($oldEvent);
        $recentInvoice = $this->createPaidInvoice($recentEvent);
        $oldPayment = $this->createPayment($oldInvoice, 'payment-proofs/old.pdf');
        $recentPayment = $this->createPayment($recentInvoice, 'payment-proofs/recent.pdf');

        $catalogItem = CatalogItem::factory()->create();
        $oldOrderItem = OrderItem::factory()->create([
            'event_id' => $oldEvent->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $catalogItem->vendor_profile_id,
            'subject_type' => 'catalog_item',
            'subject_id' => $catalogItem->id,
            'status' => OrderItemStatus::Mengikat,
            'final_price' => 100_000,
        ]);
        $oldPayout = $oldOrderItem->payout()->create([
            'bank_name_snapshot' => 'BCA',
            'account_number_snapshot' => '1234567890',
            'account_name_snapshot' => 'Vendor',
            'amount' => 100_000,
            'transferred_at' => '2030-01-02',
            'proof_disk' => 'private',
            'proof_path' => 'payout-proofs/old.pdf',
            'recorded_by' => $admin->id,
        ]);
        Storage::disk('private')->put('payment-proofs/old.pdf', 'old');
        Storage::disk('private')->put('payment-proofs/recent.pdf', 'recent');
        Storage::disk('private')->put('payout-proofs/old.pdf', 'old');

        $this->artisan('evidence:prune')->assertExitCode(0);

        $this->assertNull($oldPayment->fresh()->proof_path);
        $this->assertSame('payment-proofs/recent.pdf', $recentPayment->fresh()->proof_path);
        $this->assertNull($oldPayout->fresh()->proof_path);
        Storage::disk('private')->assertMissing('payment-proofs/old.pdf');
        Storage::disk('private')->assertExists('payment-proofs/recent.pdf');
        Storage::disk('private')->assertMissing('payout-proofs/old.pdf');
        $this->actingAs($admin)->get(route('admin.pembayaran.bukti', $oldPayment))
            ->assertNotFound();
    }

    private function createPaidInvoice(Event $event): Invoice
    {
        return $event->invoices()->create([
            'version' => 1,
            'unique_code' => 321,
            'active_code' => null,
            'provider_subtotal' => 100_000,
            'commission_total' => 10_000,
            'cleaning_total' => 0,
            'total_amount' => 110_321,
            'status' => 'lunas',
            'issued_at' => now()->subDays(10),
            'due_at' => now()->subDays(3),
        ]);
    }

    private function createPayment(Invoice $invoice, string $proofPath): Payment
    {
        Storage::disk('private')->put($proofPath, 'proof');

        return $invoice->payments()->create([
            'bank_name_snapshot' => 'BCA',
            'account_number_snapshot' => '1234567890',
            'account_name_snapshot' => 'PT Pesta Kita',
            'sender_name' => 'Nama Pengirim',
            'amount' => $invoice->total_amount,
            'transferred_at' => now(),
            'proof_disk' => 'private',
            'proof_path' => $proofPath,
            'status' => 'lunas',
        ]);
    }
}
