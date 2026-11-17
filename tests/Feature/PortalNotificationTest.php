<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PortalUpdateNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbox_lists_and_marks_only_the_signed_in_users_notifications(): void
    {
        $client = User::factory()->klien()->create();
        $otherClient = User::factory()->klien()->create();
        $client->notify(new PortalUpdateNotification('Tagihan terbit', 'Tagihan baru tersedia.', '/klien'));
        $otherClient->notify(new PortalUpdateNotification('Rahasia', 'Notifikasi milik akun lain.', '/klien'));

        $notification = $client->notifications()->firstOrFail();
        $otherNotification = $otherClient->notifications()->firstOrFail();

        $this->actingAs($client)->get('/klien/notifikasi')
            ->assertOk()
            ->assertSee('Tagihan terbit')
            ->assertDontSee('Notifikasi milik akun lain.');

        $this->actingAs($client)->post("/klien/notifikasi/{$notification->id}/baca")
            ->assertRedirect(route('klien.notifikasi'));
        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($client)->post("/klien/notifikasi/{$otherNotification->id}/baca")
            ->assertNotFound();
        $this->assertNull($otherNotification->fresh()->read_at);
    }
}
