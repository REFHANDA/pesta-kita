<?php

namespace Tests\Feature\Portal;

use App\Enums\CancellationCause;
use App\Enums\VerificationStatus;
use App\Models\Disbursement;
use App\Models\ProviderBankAccount;
use App\Models\User;
use App\Services\CancellationService;
use App\Services\DisbursementService;
use App\Services\EventCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsPaidEvents;
use Tests\Concerns\MakesUploadedFiles;
use Tests\TestCase;

class ProviderDisbursementPageTest extends TestCase
{
    use BuildsPaidEvents;
    use MakesUploadedFiles;
    use RefreshDatabase;

    private User $admin;

    /** @var array<string, mixed> */
    private array $ctx;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('private');
        $this->travelTo(now()->parse('2026-12-16 09:00:00'));
        $this->admin = User::factory()->admin()->create();
        $this->ctx = $this->paidReferenceEvent('2026-12-15');
        app(EventCompletionService::class)->complete($this->ctx['event'], $this->admin);

        foreach (['venueItem' => 'venue_profile', 'cateringItem' => 'vendor_profile'] as $key => $type) {
            ProviderBankAccount::factory()->create(['owner_type' => $type, 'owner_id' => $this->ctx[$key]->provider_id, 'verification_status' => VerificationStatus::Terverifikasi]);
            app(DisbursementService::class)->recordTransfer(Disbursement::where('order_item_id', $this->ctx[$key]->id)->sole(), $this->admin, now(), $this->pdfFile());
        }
    }

    private function venueUser(): User
    {
        return $this->ctx['venueItem']->provider->user;
    }

    private function vendorUser(): User
    {
        return $this->ctx['cateringItem']->provider->user;
    }

    public function test_penyedia_hanya_melihat_pencairan_miliknya_tanpa_nama_klien(): void
    {
        $this->actingAs($this->venueUser())->get(route('venue.pencairan'))->assertOk()
            ->assertSee('Acara #'.$this->ctx['event']->id)->assertSee('Rp 20.000.000')->assertDontSee('Rp 10.000.000')
            ->assertSee('Unduh bukti')->assertDontSee($this->ctx['client']->name)->assertDontSee($this->ctx['client']->email)
            ->assertDontSee('catat-transfer')->assertDontSee('enctype="multipart/form-data"', false);

        $this->actingAs($this->vendorUser())->get(route('vendor.pencairan'))->assertOk()
            ->assertSee('Rp 10.000.000')->assertDontSee('Rp 20.000.000')->assertDontSee($this->ctx['client']->name);

        $this->actingAs(User::factory()->vendor()->create())->get(route('vendor.pencairan'))->assertOk()->assertSee('Belum ada data.');
    }

    public function test_bukti_pencairan_hanya_untuk_penerima(): void
    {
        $venueProof = Disbursement::where('order_item_id', $this->ctx['venueItem']->id)->sole()->proof;
        $vendorProof = Disbursement::where('order_item_id', $this->ctx['cateringItem']->id)->sole()->proof;

        $this->actingAs($this->venueUser())->get(route('media.show', $venueProof))->assertOk();
        $this->actingAs($this->venueUser())->get(route('media.show', $vendorProof))->assertForbidden();
        $this->actingAs($this->vendorUser())->get(route('media.show', $venueProof))->assertForbidden();
        $this->actingAs($this->ctx['client'])->get(route('media.show', $venueProof))->assertForbidden();
        $this->actingAs($this->admin)->get(route('media.show', $venueProof))->assertOk();
    }

    public function test_penyedia_tidak_bisa_mengunduh_bukti_refund(): void
    {
        $ctx = $this->paidReferenceEvent('2026-12-30');
        $service = app(CancellationService::class);
        $cancellation = $service->submit($ctx['event'], $ctx['client'], ['reason' => 'Keluarga berhalangan hadir.', 'refund_bank_name' => 'BCA', 'refund_account_number' => '123', 'refund_account_name' => 'Rina']);
        $service->approve($cancellation, $this->admin, CancellationCause::Klien, null, null);
        $service->startRefund($cancellation, $this->admin);
        $proof = $service->completeRefund($cancellation, $this->admin, now(), $this->pdfFile())->refundProof;

        $this->actingAs($ctx['venueItem']->provider->user)->get(route('media.show', $proof))->assertForbidden();
        $this->actingAs($ctx['cateringItem']->provider->user)->get(route('media.show', $proof))->assertForbidden();
        $this->actingAs($ctx['client'])->get(route('media.show', $proof))->assertOk();

        $this->actingAs($ctx['venueItem']->provider->user)->get(route('venue.pencairan'))->assertSee('Kompensasi pembatalan')->assertDontSee($ctx['client']->name);
    }

    public function test_vendor_dan_venue_tidak_saling_mengakses_route(): void
    {
        $this->actingAs($this->vendorUser())->get(route('venue.pencairan'))->assertForbidden();
        $this->actingAs($this->venueUser())->get(route('vendor.pencairan'))->assertForbidden();
        $this->actingAs($this->ctx['client'])->get(route('vendor.pencairan'))->assertForbidden();
    }
}
