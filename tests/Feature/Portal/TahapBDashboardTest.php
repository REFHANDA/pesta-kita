<?php

namespace Tests\Feature\Portal;

use App\Enums\CancellationCause;
use App\Enums\InvoiceStatus;
use App\Models\User;
use App\Services\CancellationService;
use App\Services\EventCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsPaidEvents;
use Tests\Concerns\MakesUploadedFiles;
use Tests\TestCase;

class TahapBDashboardTest extends TestCase
{
    use BuildsPaidEvents;
    use MakesUploadedFiles;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('private');
        $this->travelTo(now()->parse('2026-12-01 09:00:00'));
        $this->admin = User::factory()->admin()->create();
    }

    public function test_pendapatan_acuan_70_persen_berkontribusi_1350000_bukan_4500000(): void
    {
        $ctx = $this->paidReferenceEvent('2026-12-15');
        $this->actingAs($this->admin)->get('/admin')->assertSee('Rp 4.500.000');

        $service = app(CancellationService::class);
        $cancellation = $service->submit($ctx['event'], $ctx['client'], ['reason' => 'Keluarga berhalangan hadir.', 'refund_bank_name' => 'BCA', 'refund_account_number' => '123', 'refund_account_name' => 'Rina']);
        $this->actingAs($this->admin)->get('/admin')->assertSee('Rp 4.500.000')->assertSee('Pembatalan perlu ditinjau');

        $service->approve($cancellation, $this->admin, CancellationCause::Klien, null, null);
        $service->startRefund($cancellation, $this->admin);
        $service->completeRefund($cancellation, $this->admin, now(), $this->pdfFile());

        $this->assertSame(InvoiceStatus::Lunas, $ctx['invoice']->fresh()->status);
        $response = $this->actingAs($this->admin)->get('/admin')->assertOk();
        $response->assertSee('Rp 1.350.000')->assertDontSee('Rp 4.500.000')->assertDontSee('Rp 5.850.000');
        $response->assertSee('Pencairan belum cair')->assertSee('Rp 9.000.000');

        $this->actingAs($ctx['venueItem']->provider->user)->get('/venue')->assertOk()->assertSee('Belum cair')->assertSee('Rp 6.000.000');
        $this->actingAs($ctx['cateringItem']->provider->user)->get('/vendor')->assertOk()->assertSee('Belum cair')->assertSee('Rp 3.000.000');
        $this->actingAs($ctx['client'])->get('/klien')->assertOk()->assertSee('Dibatalkan · refund selesai');
    }

    public function test_kartu_antrean_admin_dan_status_pembatalan_klien(): void
    {
        $ctx = $this->paidReferenceEvent('2026-12-15');
        app(CancellationService::class)->submit($ctx['event'], $ctx['client'], ['reason' => 'Keluarga berhalangan hadir.', 'refund_bank_name' => 'BCA', 'refund_account_number' => '123', 'refund_account_name' => 'Rina']);

        $this->actingAs($this->admin)->get('/admin')->assertOk()
            ->assertSee(route('admin.pembatalan'), false)->assertSee(route('admin.pencairan'), false);
        $this->actingAs($ctx['client'])->get('/klien')->assertOk()->assertSee('Pembatalan diproses · Diajukan');
    }

    public function test_belum_cair_penyedia_rp_0_bila_kosong_dan_hak_pesanan_terhitung(): void
    {
        $ctx = $this->paidReferenceEvent('2026-11-30');
        $venueUser = $ctx['venueItem']->provider->user;
        $this->actingAs($venueUser)->get('/venue')->assertOk()->assertSee('Belum cair')->assertSee('Rp 0');

        app(EventCompletionService::class)->complete($ctx['event'], $this->admin);
        $this->actingAs($venueUser)->get('/venue')->assertSee('Rp 20.000.000');
        $this->actingAs($this->admin)->get('/admin')->assertSee('Rp 4.500.000')->assertSee('Rp 30.000.000');
    }
}
