<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventChecklistItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChecklistTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_manages_checklist_and_provider_only_reads_assigned_event(): void
    {
        $client = User::factory()->klien()->create();
        $provider = User::factory()->vendor()->create();
        $event = Event::factory()->create(['client_id' => $client->id]);
        $event->orderItems()->create([
            'provider_type' => 'vendor_profile',
            'provider_id' => $provider->vendorProfile->id,
            'subject_type' => 'catalog_item',
            'subject_id' => \App\Models\CatalogItem::factory()->create([
                'vendor_profile_id' => $provider->vendorProfile->id,
            ])->id,
            'catalog_price_snapshot' => 100_000,
            'status' => \App\Enums\OrderItemStatus::MenungguAdmin,
        ]);
        $item = EventChecklistItem::create([
            'event_id' => $event->id,
            'created_by' => $client->id,
            'title' => 'Konfirmasi dekorasi',
        ]);

        $this->actingAs($client)
            ->put("/klien/acara/{$event->id}/checklist/{$item->id}", [
                'title' => $item->title,
                'is_completed' => '1',
            ])
            ->assertRedirect(route('klien.checklist', $event));

        $this->assertTrue($item->fresh()->is_completed);

        $this->actingAs($provider)
            ->get("/vendor/checklist/{$event->id}")
            ->assertOk()
            ->assertSee('Konfirmasi dekorasi')
            ->assertDontSee('Simpan');

        $this->actingAs($provider)
            ->post("/klien/acara/{$event->id}/checklist", ['title' => 'Tidak berwenang'])
            ->assertForbidden();
    }

    public function test_client_cannot_access_another_clients_checklist(): void
    {
        $owner = User::factory()->klien()->create();
        $otherClient = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $owner->id]);

        $this->actingAs($otherClient)
            ->get("/klien/acara/{$event->id}/checklist")
            ->assertNotFound();
    }
}
