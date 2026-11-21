<?php

namespace Tests\Feature\Rundown;

use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\RundownItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rundown di detail acara Klien (Tahap C2).
 */
class KlienRundownTest extends TestCase
{
    use RefreshDatabase;

    protected function confirmedEvent(User $client): Event
    {
        return Event::factory()->create([
            'client_id' => $client->id,
            'status' => EventStatus::Terkonfirmasi,
            'event_date' => now()->addDays(10)->format('Y-m-d'),
        ]);
    }

    public function test_klien_menambah_mengubah_dan_menghapus_item_rundown(): void
    {
        $client = User::factory()->klien()->create();
        $event = $this->confirmedEvent($client);

        $this->actingAs($client)
            ->post(route('klien.acara.rundown.store', $event), [
                'start_time' => '09:00',
                'end_time' => '09:30',
                'activity' => 'Registrasi tamu',
                'description' => 'Meja registrasi di lobi',
            ])
            ->assertRedirect(route('klien.acara.detail', $event).'#rundown');

        $item = RundownItem::where('event_id', $event->id)->firstOrFail();
        $this->assertSame('Registrasi tamu', $item->activity);
        $this->assertSame($client->id, $item->created_by);

        $this->actingAs($client)
            ->put(route('klien.acara.rundown.update', [$event, $item]), [
                'start_time' => '09:15',
                'end_time' => '09:45',
                'activity' => 'Registrasi ulang tamu',
            ])
            ->assertRedirect(route('klien.acara.detail', $event).'#rundown');

        $this->assertSame('Registrasi ulang tamu', $item->fresh()->activity);

        $this->actingAs($client)
            ->delete(route('klien.acara.rundown.destroy', [$event, $item]))
            ->assertRedirect(route('klien.acara.detail', $event).'#rundown');

        $this->assertDatabaseMissing('rundown_items', ['id' => $item->id]);
    }

    public function test_validasi_jam_ditolak_dengan_pesan(): void
    {
        $client = User::factory()->klien()->create();
        $event = $this->confirmedEvent($client);

        $this->actingAs($client)
            ->post(route('klien.acara.rundown.store', $event), [
                'start_time' => '9:00',
                'activity' => 'Registrasi',
            ])
            ->assertSessionHasErrors('start_time');

        $this->actingAs($client)
            ->post(route('klien.acara.rundown.store', $event), [
                'start_time' => '10:00',
                'end_time' => '09:00',
                'activity' => 'Registrasi',
            ])
            ->assertSessionHasErrors('end_time');

        $this->assertSame(0, RundownItem::where('event_id', $event->id)->count());
    }

    public function test_klien_lain_mendapat_403(): void
    {
        $client = User::factory()->klien()->create();
        $other = User::factory()->klien()->create();
        $event = $this->confirmedEvent($client);
        $item = RundownItem::factory()->create(['event_id' => $event->id, 'created_by' => $client->id]);

        $this->actingAs($other)->get(route('klien.acara.detail', $event))->assertNotFound();

        $this->actingAs($other)->post(route('klien.acara.rundown.store', $event), [
            'start_time' => '10:00',
            'activity' => 'Percobaan',
        ])->assertForbidden();

        $this->actingAs($other)->put(route('klien.acara.rundown.update', [$event, $item]), [
            'start_time' => '10:00',
            'activity' => 'Percobaan',
        ])->assertForbidden();

        $this->actingAs($other)->delete(route('klien.acara.rundown.destroy', [$event, $item]))->assertForbidden();
    }

    public function test_rundown_terkunci_setelah_acara_selesai(): void
    {
        $client = User::factory()->klien()->create();
        $event = $this->confirmedEvent($client);
        $event->update(['status' => EventStatus::Selesai]);
        $item = RundownItem::factory()->create(['event_id' => $event->id, 'created_by' => $client->id]);

        $this->actingAs($client)->get(route('klien.acara.detail', $event))
            ->assertOk()
            ->assertSee('Rundown dikunci karena acara sudah selesai.')
            ->assertDontSee(route('klien.acara.rundown.store', $event), false);

        $this->actingAs($client)->post(route('klien.acara.rundown.store', $event), [
            'start_time' => '10:00',
            'activity' => 'Percobaan',
        ])->assertForbidden();

        $this->actingAs($client)->put(route('klien.acara.rundown.update', [$event, $item]), [
            'start_time' => '10:00',
            'activity' => 'Percobaan',
        ])->assertForbidden();
    }

    public function test_rundown_tidak_tampil_saat_acara_dibatalkan_atau_belum_terkonfirmasi(): void
    {
        $client = User::factory()->klien()->create();

        foreach ([EventStatus::Draft, EventStatus::Diajukan, EventStatus::MenungguPembayaran, EventStatus::Dibatalkan] as $status) {
            $event = Event::factory()->create([
                'client_id' => $client->id,
                'status' => $status,
                'event_date' => now()->addDays(5)->format('Y-m-d'),
            ]);

            $response = $this->actingAs($client)->get(route('klien.acara.detail', $event))->assertOk();
            $response->assertDontSee(route('klien.acara.rundown.store', $event), false);
            $response->assertSee('Rundown');
        }
    }

    public function test_url_lama_rundown_klien_404(): void
    {
        $client = User::factory()->klien()->create();

        $this->actingAs($client)->get('/klien/rundown')->assertNotFound();
    }

    public function test_halaman_menandai_item_vendor_dan_menampilkan_nama_usaha(): void
    {
        $client = User::factory()->klien()->create();
        $event = $this->confirmedEvent($client);

        $vendor = User::factory()->vendor()->create();
        $orderItem = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendor->vendorProfile->id,
            'status' => OrderItemStatus::Terkonfirmasi,
            'provider_price' => 1_000_000,
        ]);

        RundownItem::factory()->create([
            'event_id' => $event->id,
            'created_by' => $vendor->id,
            'order_item_id' => $orderItem->id,
            'activity' => 'Persiapan katering',
        ]);

        $this->actingAs($client)->get(route('klien.acara.detail', $event))
            ->assertOk()
            ->assertSee('Dibuat oleh vendor')
            ->assertSee($vendor->vendorProfile->business_name);
    }
}
