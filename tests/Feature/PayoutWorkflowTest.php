<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Enums\VerificationStatus;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\Payout;
use App\Models\ProviderBankAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayoutWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_records_one_full_provider_payout_after_event_completion(): void
    {
        $this->travelTo('2030-01-01 10:00:00');
        $admin = User::factory()->admin()->create();
        [$vendor, $orderItem] = $this->createPayableOrder();
        $account = ProviderBankAccount::query()->create([
            'owner_type' => 'vendor_profile',
            'owner_id' => $vendor->vendorProfile->id,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'Usaha Vendor',
            'verification_status' => VerificationStatus::Terverifikasi,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.pencairan.store', $orderItem), [
            'provider_bank_account_id' => $account->id,
            'transferred_at' => '2030-01-01',
            'notes' => 'Transfer melalui bank.',
        ]);
        $response->assertRedirect(route('admin.pencairan.detail', $orderItem));
        $payout = Payout::query()->sole();

        $this->assertSame(2_500_000, $payout->amount);
        $this->assertSame('BCA', $payout->bank_name_snapshot);
        $this->assertSame('1234567890', $payout->account_number_snapshot);
        $this->assertDatabaseHas('payouts', [
            'order_item_id' => $orderItem->id,
            'recorded_by' => $admin->id,
            'amount' => 2_500_000,
        ]);

        $this->post(route('admin.pencairan.store', $orderItem), [
            'provider_bank_account_id' => $account->id,
            'transferred_at' => '2030-01-01',
        ])->assertSessionHasErrors('order_item');
        $this->assertDatabaseCount('payouts', 1);
    }

    public function test_payout_details_are_visible_only_to_the_owning_provider(): void
    {
        $this->travelTo('2030-01-01 10:00:00');
        [$vendor, $orderItem] = $this->createPayableOrder();
        $payout = $orderItem->payout()->create([
            'bank_name_snapshot' => 'BCA',
            'account_number_snapshot' => '1234567890',
            'account_name_snapshot' => 'Usaha Vendor',
            'amount' => 2_500_000,
            'transferred_at' => '2030-01-01',
            'proof_disk' => 'private',
            'recorded_by' => User::factory()->admin()->create()->id,
        ]);
        $otherVendor = User::factory()->vendor()->create();

        $this->actingAs($vendor)->get(route('vendor.pencairan.detail', $payout))
            ->assertOk()
            ->assertSee('2.500.000')
            ->assertDontSee('1234567890');

        $this->actingAs($otherVendor)->get(route('vendor.pencairan.detail', $payout))
            ->assertNotFound();
    }

    /**
     * Create an event whose locked Vendor order is eligible for payout.
     *
     * @return array{User, OrderItem}
     */
    private function createPayableOrder(): array
    {
        $vendor = User::factory()->vendor()->create();
        $profile = $vendor->vendorProfile()->firstOrFail();
        $event = Event::factory()->create([
            'status' => EventStatus::Selesai,
            'event_date' => '2029-12-01',
        ]);
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $profile->id]);
        $orderItem = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $profile->id,
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
            'status' => OrderItemStatus::Mengikat,
            'final_price' => 2_500_000,
        ]);
        $event->invoices()->create([
            'version' => 1,
            'unique_code' => 321,
            'active_code' => null,
            'provider_subtotal' => 2_500_000,
            'commission_total' => 250_000,
            'cleaning_total' => 0,
            'total_amount' => 2_750_321,
            'status' => 'lunas',
            'issued_at' => now()->subDays(10),
            'due_at' => now()->subDays(3),
        ]);

        return [$vendor, $orderItem];
    }
}
