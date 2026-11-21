<?php

namespace Tests\Feature\Portal;

use App\Enums\CancellationCause;
use App\Enums\EventStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderItemStatus;
use App\Enums\VerificationStatus;
use App\Models\Disbursement;
use App\Models\ProviderBankAccount;
use App\Models\User;
use App\Services\CancellationService;
use App\Services\DisbursementService;
use App\Services\EventCancellationService;
use App\Services\EventCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsPaidEvents;
use Tests\Concerns\MakesUploadedFiles;
use Tests\TestCase;

/**
 * Notifikasi Tahap B memakai sistem lonceng Tahap A; payload penyedia hanya "Acara #ID".
 */
class TahapBNotificationTest extends TestCase
{
    use BuildsPaidEvents;
    use MakesUploadedFiles;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        $this->travelTo(now()->parse('2026-12-01 09:00:00'));
        $this->admin = User::factory()->admin()->create();
    }

    private function secretClient(): User
    {
        return User::factory()->klien()->create(['name' => 'Klien Rahasia Sekali', 'phone' => '081277776666']);
    }

    /**
     * @param  array<string, mixed>  $ctx
     */
    private function assertProviderPayloadsPrivate(array $ctx, User $client, int $minimum): void
    {
        $providers = [$ctx['venueItem']->provider->user, $ctx['cateringItem']->provider->user];
        $count = 0;

        foreach ($providers as $provider) {
            foreach ($provider->notifications as $notification) {
                $payload = json_encode($notification->data);
                $this->assertStringContainsString('Acara #'.$ctx['event']->id, $payload);
                foreach (['Klien Rahasia Sekali', $ctx['event']->name, '081277776666', $client->email] as $secret) {
                    $this->assertStringNotContainsString($secret, $payload);
                }
                $count++;
            }

            $this->actingAs($provider)->get(route($provider->role->value.'.notifikasi'))->assertOk()->assertDontSee('Klien Rahasia Sekali');
        }

        $this->assertGreaterThanOrEqual($minimum, $count);
    }

    public function test_alur_refund_mengirim_notifikasi_ke_semua_pihak_tanpa_bocor(): void
    {
        $client = $this->secretClient();
        $ctx = $this->paidReferenceEvent('2026-12-15', $client);
        $service = app(CancellationService::class);

        $cancellation = $service->submit($ctx['event'], $client, ['reason' => 'Keluarga berhalangan hadir.', 'refund_bank_name' => 'BCA', 'refund_account_number' => '123', 'refund_account_name' => 'Rina']);
        $service->review($cancellation, $this->admin);
        $service->approve($cancellation, $this->admin, CancellationCause::Klien, null, null);
        $service->startRefund($cancellation, $this->admin);
        $service->completeRefund($cancellation, $this->admin, now(), $this->pdfFile());

        $adminTypes = $this->admin->notifications->pluck('type')->map(fn (string $t): string => class_basename($t));
        $this->assertContains('CancellationSubmittedNotification', $adminTypes);

        $clientMessages = $client->notifications->pluck('data.message')->implode("\n");
        foreach (['diterima', 'sedang ditinjau', 'disetujui', 'sedang diproses', 'telah ditransfer'] as $fragment) {
            $this->assertStringContainsString($fragment, $clientMessages);
        }
        $this->assertStringContainsString('Rp 24.150.123', $clientMessages);

        $this->assertProviderPayloadsPrivate($ctx, $client, 2);
        $venueUser = $ctx['venueItem']->provider->user;
        $this->assertSame('CompensationRecordedNotification', class_basename($venueUser->notifications->first()->type));

        $this->actingAs($venueUser)->get(route('venue.notifikasi'))->assertSee('Kompensasi pembatalan')->assertSee('Rp 6.000.000');
        $this->actingAs($venueUser)->get(route('venue.notifikasi.buka', $venueUser->notifications->first()->id))->assertRedirect(route('venue.pencairan'));
        $this->assertNotNull($venueUser->notifications()->first()->read_at);

        $clientNotification = $client->notifications()->latest()->first();
        $this->actingAs($client)->get(route('klien.notifikasi.buka', $clientNotification->id))->assertRedirect(route('klien.acara.detail', $ctx['event']));
        $adminNotification = $this->admin->notifications()->first();
        $this->actingAs($this->admin)->get(route('admin.notifikasi.buka', $adminNotification->id))->assertRedirect(route('admin.pembatalan.show', $cancellation));
    }

    public function test_acara_selesai_dan_pencairan_dicatat_mengirim_notifikasi_privat(): void
    {
        $client = $this->secretClient();
        $ctx = $this->paidReferenceEvent('2026-11-30', $client);

        app(EventCompletionService::class)->complete($ctx['event'], $this->admin);
        ProviderBankAccount::factory()->create(['owner_type' => 'venue_profile', 'owner_id' => $ctx['venueItem']->provider_id, 'verification_status' => VerificationStatus::Terverifikasi]);
        app(DisbursementService::class)->recordTransfer(Disbursement::where('order_item_id', $ctx['venueItem']->id)->sole(), $this->admin, now(), $this->pdfFile());

        $this->assertStringContainsString('telah selesai', $client->notifications->pluck('data.message')->implode("\n"));
        $venueMessages = $ctx['venueItem']->provider->user->notifications->pluck('data.message')->implode("\n");
        $this->assertStringContainsString('selesai; hak Anda siap dicairkan', $venueMessages);
        $this->assertStringContainsString('Pencairan hak pesanan Rp 20.000.000 untuk Acara #'.$ctx['event']->id.' telah ditransfer.', $venueMessages);
        $this->assertProviderPayloadsPrivate($ctx, $client, 3);

        $this->actingAs($ctx['venueItem']->provider->user)->get(route('venue.notifikasi'))->assertSee('Pencairan ditransfer')->assertSee('Acara selesai');
    }

    public function test_pembatalan_otomatis_memberi_tahu_admin_dan_penyedia_terkait(): void
    {
        $client = $this->secretClient();
        $ctx = $this->paidReferenceEvent('2026-12-15', $client);
        $ctx['event']->update(['status' => EventStatus::MenungguPembayaran]);
        $ctx['invoice']->update(['status' => InvoiceStatus::BelumDibayar, 'paid_at' => null]);
        $ctx['venueItem']->update(['status' => OrderItemStatus::Disetujui]);
        $ctx['cateringItem']->update(['status' => OrderItemStatus::Disetujui]);

        app(EventCancellationService::class)->cancelBeforePayment($ctx['event'], $client);

        $this->assertContains('EventCancelledNotification', $this->admin->notifications->pluck('type')->map(fn (string $t): string => class_basename($t)));
        $this->assertProviderPayloadsPrivate($ctx, $client, 2);
    }
}
