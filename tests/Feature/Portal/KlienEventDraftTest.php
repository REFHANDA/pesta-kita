<?php

namespace Tests\Feature\Portal;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KlienEventDraftTest extends TestCase
{
    use RefreshDatabase;

    public function test_klien_can_view_create_event_page(): void
    {
        $klien = User::factory()->klien()->create();

        $response = $this->actingAs($klien)->get(route('klien.acara.baru'));

        $response->assertOk()
            ->assertSee('Buat konsep acara')
            ->assertSee('Nama acara')
            ->assertSee('Anggaran');
    }

    public function test_klien_can_create_draft_event_with_budget(): void
    {
        $klien = User::factory()->klien()->create();

        $payload = [
            'name' => 'Ulang Tahun Ke-7 Kayla',
            'event_date' => now()->addDays(20)->format('Y-m-d'),
            'guest_count' => 50,
            'theme' => 'Unicorn Rainbow',
            'budget' => 15_000_000,
            'description' => 'Pesta ulang tahun anak di taman belakang.',
        ];

        $response = $this->actingAs($klien)->post(route('klien.acara.store'), $payload);

        $event = Event::where('name', 'Ulang Tahun Ke-7 Kayla')->first();
        $this->assertNotNull($event);
        $this->assertEquals($klien->id, $event->client_id);
        $this->assertEquals(EventStatus::Draft, $event->status);
        $this->assertEquals(15_000_000, $event->budget);
        $this->assertEquals(50, $event->guest_count);

        $response->assertRedirect(route('klien.acara.detail', $event));
    }

    public function test_create_event_validates_required_fields_including_budget(): void
    {
        $klien = User::factory()->klien()->create();

        $response = $this->actingAs($klien)->post(route('klien.acara.store'), [
            'name' => '',
            'event_date' => '',
            'guest_count' => '',
            'budget' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'event_date', 'guest_count', 'budget']);
    }

    public function test_klien_can_view_and_update_draft_event(): void
    {
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $klien->id,
            'status' => EventStatus::Draft,
            'budget' => 10_000_000,
        ]);

        $this->actingAs($klien)->get(route('klien.acara.edit', $event))
            ->assertOk()
            ->assertSee($event->name);

        $response = $this->actingAs($klien)->put(route('klien.acara.update', $event), [
            'name' => 'Nama Acara Diperbarui',
            'event_date' => now()->addDays(25)->format('Y-m-d'),
            'guest_count' => 60,
            'theme' => 'Tema Baru',
            'budget' => 20_000_000,
            'description' => 'Deskripsi baru.',
        ]);

        $response->assertRedirect(route('klien.acara.detail', $event));

        $event->refresh();
        $this->assertEquals('Nama Acara Diperbarui', $event->name);
        $this->assertEquals(20_000_000, $event->budget);
        $this->assertEquals(60, $event->guest_count);
    }

    public function test_non_draft_event_cannot_be_updated_or_deleted(): void
    {
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->diajukan()->create([
            'client_id' => $klien->id,
        ]);

        $this->actingAs($klien)->get(route('klien.acara.edit', $event))
            ->assertForbidden();

        $this->actingAs($klien)->put(route('klien.acara.update', $event), [
            'name' => 'Coba Ubah',
            'event_date' => now()->addDays(10)->format('Y-m-d'),
            'guest_count' => 10,
            'budget' => 5_000_000,
        ])->assertForbidden();

        $this->actingAs($klien)->delete(route('klien.acara.destroy', $event))
            ->assertForbidden();
    }

    public function test_klien_can_delete_draft_event(): void
    {
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $klien->id,
            'status' => EventStatus::Draft,
        ]);

        $response = $this->actingAs($klien)->delete(route('klien.acara.destroy', $event));

        $response->assertRedirect(route('klien.acara'));
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }
}
