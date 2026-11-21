<?php

namespace Tests\Feature\Rundown;

use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\User;
use App\Notifications\EvaluationSubmittedNotification;
use App\Notifications\RundownUpdatedNotification;
use App\Services\EvaluationService;
use App\Services\RundownService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Notifikasi rundown dan evaluasi (Tahap C6): penggabungan, privasi payload,
 * dan tautan yang benar.
 */
class RundownNotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{client: User, vendor: User, venue: User, event: Event, vendorOrder: OrderItem}
     */
    protected function fixture(): array
    {
        $client = User::factory()->klien()->create(['name' => 'Klien Rahasia']);
        $vendor = User::factory()->vendor()->create();
        $venue = User::factory()->venue()->create();

        $event = Event::factory()->create([
            'client_id' => $client->id,
            'status' => EventStatus::Terkonfirmasi,
            'event_date' => now()->addDays(7)->format('Y-m-d'),
        ]);

        $vendorOrder = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendor->vendorProfile->id,
            'status' => OrderItemStatus::Terkonfirmasi,
            'provider_price' => 1_000_000,
        ]);

        OrderItem::factory()->untukVenueRoom()->create([
            'event_id' => $event->id,
            'provider_type' => 'venue_profile',
            'provider_id' => $venue->venueProfile->id,
            'status' => OrderItemStatus::Terkonfirmasi,
            'provider_price' => 2_000_000,
        ]);

        return compact('client', 'vendor', 'venue', 'event', 'vendorOrder');
    }

    public function test_perubahan_klien_digabung_untuk_penyedia_dan_tanpa_nama_klien(): void
    {
        $f = $this->fixture();
        $service = app(RundownService::class);

        $first = $service->add($f['event'], $f['client'], ['start_time' => '09:00', 'activity' => 'Pembukaan']);
        $service->update($first, $f['client'], ['start_time' => '09:15', 'activity' => 'Pembukaan']);
        $service->add($f['event'], $f['client'], ['start_time' => '10:00', 'activity' => 'Makan malam']);

        $this->assertSame(1, $f['vendor']->unreadNotifications()->count());
        $this->assertSame(1, $f['venue']->unreadNotifications()->count());

        $payload = $f['vendor']->notifications()->first();
        $this->assertSame(RundownUpdatedNotification::class, $payload->type);
        $this->assertStringContainsString('Acara #'.$f['event']->id, $payload->data['message']);
        $this->assertStringNotContainsString('Klien Rahasia', json_encode($payload->data));
        $this->assertSame(route('vendor.rundown.show', $f['event']), $payload->data['url']);

        // Setelah dibaca, perubahan berikutnya membuat notifikasi baru.
        $f['vendor']->unreadNotifications()->update(['read_at' => now()]);
        $service->add($f['event'], $f['client'], ['start_time' => '11:00', 'activity' => 'Penutupan']);

        $this->assertSame(1, $f['vendor']->unreadNotifications()->count());
        $this->assertSame(2, $f['vendor']->notifications()->count());
    }

    public function test_perubahan_vendor_ke_klien_tanpa_kontak_vendor(): void
    {
        $f = $this->fixture();
        $service = app(RundownService::class);

        $service->add($f['event'], $f['vendor'], [
            'start_time' => '08:00',
            'activity' => 'Persiapan katering',
            'order_item_id' => $f['vendorOrder']->id,
        ]);

        $this->assertSame(1, $f['client']->unreadNotifications()->count());

        $payload = $f['client']->notifications()->first();
        $this->assertSame(RundownUpdatedNotification::class, $payload->type);
        $this->assertStringContainsString('diperbarui oleh vendor', $payload->data['message']);
        $this->assertStringNotContainsString($f['vendor']->phone ?? '@', (string) json_encode($payload->data));
        $this->assertSame(route('klien.acara.detail', $f['event']).'#rundown', $payload->data['url']);
    }

    public function test_evaluasi_memberi_notifikasi_ke_admin_dan_penyedia_tanpa_nama_atau_komentar(): void
    {
        $f = $this->fixture();
        $f['event']->update(['status' => EventStatus::Selesai]);
        $f['vendorOrder']->update(['status' => OrderItemStatus::Selesai]);
        $f['event']->orderItems()->where('provider_type', 'venue_profile')->update(['status' => OrderItemStatus::Selesai]);

        $admin = User::factory()->admin()->create();
        $vendorId = $f['vendor']->vendorProfile->id;
        $venueId = $f['venue']->venueProfile->id;

        app(EvaluationService::class)->submit($f['event'], $f['client'], [
            'venue' => ['rating' => 5, 'comment' => 'Komentar rahasia venue'],
            'vendor:'.$vendorId => ['rating' => 4, 'comment' => 'Komentar rahasia vendor'],
            'admin' => ['rating' => 5],
        ], 'Masukan sistem rahasia');

        $this->assertSame(1, $admin->unreadNotifications()->count());
        $this->assertSame(1, $f['vendor']->unreadNotifications()->count());
        $this->assertSame(1, $f['venue']->unreadNotifications()->count());

        foreach ([$f['vendor'], $f['venue']] as $provider) {
            $payload = $provider->notifications()->first();
            $this->assertSame(EvaluationSubmittedNotification::class, $payload->type);
            $json = json_encode($payload->data);
            $this->assertStringContainsString('Acara #'.$f['event']->id, $json);
            $this->assertStringNotContainsString('Klien Rahasia', $json);
            $this->assertStringNotContainsString('Komentar rahasia', $json);
            $this->assertStringNotContainsString('Masukan sistem rahasia', $json);
        }

        $adminPayload = $admin->notifications()->first();
        $this->assertStringContainsString('Acara #'.$f['event']->id, $adminPayload->data['message']);
        $this->assertSame(route('admin.acara.detail', $f['event']).'#evaluasi', $adminPayload->data['url']);
    }

    public function test_membuka_notifikasi_menandai_dibaca_dan_mengarahkan(): void
    {
        $f = $this->fixture();

        app(RundownService::class)->add($f['event'], $f['client'], [
            'start_time' => '09:00',
            'activity' => 'Pembukaan',
        ]);

        $notification = $f['vendor']->notifications()->first();

        $this->actingAs($f['vendor'])
            ->get(route('vendor.notifikasi.buka', $notification->id))
            ->assertRedirect(route('vendor.rundown.show', $f['event']));

        $this->assertNotNull($notification->fresh()->read_at);
    }
}
