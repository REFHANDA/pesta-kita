<?php

namespace Tests\Feature\Api;

use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BackendApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_registration_creates_profile_and_api_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Vendor Kita',
            'email' => 'vendor@example.test',
            'phone' => '08123456789',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
            'role' => 'vendor',
            'business_name' => 'Dekorasi Kita',
            'category' => 'Dekorasi',
            'address' => 'Bandung',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.role', 'vendor')
            ->assertJsonPath('user.vendor_profile.business_name', 'Dekorasi Kita')
            ->assertJsonStructure(['token']);
        $this->assertDatabaseHas('users', ['email' => 'vendor@example.test', 'role' => 'vendor']);
    }

    public function test_demo_seeder_creates_idempotent_accounts_for_each_role(): void
    {
        foreach ([
            'client.demo@pestakita.test' => 'client',
            'admin.demo@pestakita.test' => 'admin',
            'vendor.demo@pestakita.test' => 'vendor',
            'venue.demo@pestakita.test' => 'venue',
        ] as $email => $role) {
            User::create([
                'name' => 'Old demo user',
                'email' => $email,
                'password' => 'old-password',
                'role' => $role,
            ]);
        }

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 4);

        foreach ([
            'client@pestakita.test' => 'client',
            'admin@pestakita.test' => 'admin',
            'vendor@pestakita.test' => 'vendor',
            'venue@pestakita.test' => 'venue',
        ] as $email => $role) {
            $user = User::query()->where('email', $email)->firstOrFail();

            $this->assertSame($role, $user->role);
            $this->assertTrue(Hash::check('password123', $user->password));

            $this->postJson('/api/login', [
                'email' => $email,
                'password' => 'password123',
            ])->assertOk()
                ->assertJsonPath('user.role', $role)
                ->assertJsonStructure(['token']);
        }

        $this->assertDatabaseHas('vendor_profiles', [
            'user_id' => User::query()->where('email', 'vendor@pestakita.test')->value('id'),
            'business_name' => 'Pesta Kita Dekorasi',
        ]);
        $this->assertDatabaseHas('vendor_profiles', [
            'user_id' => User::query()->where('email', 'venue@pestakita.test')->value('id'),
            'business_name' => 'Pesta Kita Venue',
        ]);

        foreach ([
            'client.demo@pestakita.test',
            'admin.demo@pestakita.test',
            'vendor.demo@pestakita.test',
            'venue.demo@pestakita.test',
        ] as $oldEmail) {
            $this->assertDatabaseMissing('users', ['email' => $oldEmail]);
        }
    }

    public function test_public_registration_cannot_create_an_admin_account(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Admin Palsu',
            'email' => 'not-admin@example.test',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
            'role' => 'admin',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'not-admin@example.test']);
    }

    public function test_clients_can_only_access_their_own_events(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $otherClient = User::factory()->create(['role' => 'client']);
        $event = Event::create(['user_id' => $otherClient->id, 'name' => 'Acara privat']);

        $this->actingAs($client, 'sanctum')
            ->getJson("/api/events/{$event->id}")
            ->assertNotFound();

        $this->actingAs($client, 'sanctum')
            ->postJson('/api/events', ['name' => 'Pesta Kita', 'guest_count' => 80])
            ->assertCreated()
            ->assertJsonPath('status', 'draft');
    }

    public function test_ordering_creates_invoice_and_notifies_vendor(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $vendor = User::factory()->create(['role' => 'vendor']);
        $event = Event::create(['user_id' => $client->id, 'name' => 'Pernikahan']);
        $item = CatalogItem::create([
            'user_id' => $vendor->id,
            'type' => 'vendor',
            'name' => 'Paket dekorasi',
            'category' => 'Dekorasi',
            'price' => '125000.25',
            'is_available' => true,
        ]);

        $this->actingAs($client, 'sanctum')
            ->postJson('/api/orders', [
                'event_id' => $event->id,
                'catalog_item_id' => $item->id,
                'quantity' => 2,
            ])
            ->assertCreated()
            ->assertJsonPath('total_amount', '250000.50')
            ->assertJsonPath('invoice.status', 'pending');

        $this->assertDatabaseHas('orders', ['client_id' => $client->id, 'total_amount' => '250000.50']);
        $this->assertDatabaseHas('invoices', ['amount' => '250000.50', 'status' => 'pending']);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $vendor->id]);
    }

    public function test_order_status_transitions_and_review_require_completed_order(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $vendor = User::factory()->create(['role' => 'vendor']);
        $event = Event::create(['user_id' => $client->id, 'name' => 'Acara']);
        $item = CatalogItem::create([
            'user_id' => $vendor->id,
            'type' => 'vendor',
            'name' => 'Katering',
            'category' => 'Katering',
            'price' => '100000.00',
        ]);
        $order = Order::create([
            'event_id' => $event->id,
            'client_id' => $client->id,
            'catalog_item_id' => $item->id,
            'vendor_id' => $vendor->id,
            'quantity' => 1,
            'unit_price' => '100000.00',
            'total_amount' => '100000.00',
        ]);

        $this->actingAs($client, 'sanctum')
            ->postJson("/api/orders/{$order->id}/reviews", ['rating' => 5])
            ->assertUnprocessable();

        $this->actingAs($vendor, 'sanctum')
            ->patchJson("/api/orders/{$order->id}/status", ['status' => 'in_progress'])
            ->assertUnprocessable();

        $order->update(['status' => 'completed']);
        $this->actingAs($client, 'sanctum')
            ->postJson("/api/orders/{$order->id}/reviews", ['rating' => 5, 'comment' => 'Bagus'])
            ->assertCreated();
    }
}
