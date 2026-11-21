<?php

namespace Tests\Feature\Portal;

use App\Enums\CancellationCause;
use App\Enums\CancellationStatus;
use App\Enums\DisbursementStatus;
use App\Enums\DisbursementType;
use App\Enums\EventStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderItemStatus;
use App\Models\AuditLog;
use App\Models\Cancellation;
use App\Models\Disbursement;
use App\Models\Media;
use App\Models\User;
use App\Notifications\CancellationStatusChangedNotification;
use App\Notifications\CompensationRecordedNotification;
use App\Services\CancellationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\Concerns\BuildsPaidEvents;
use Tests\Concerns\MakesUploadedFiles;
use Tests\TestCase;

class RefundCompletionEffectsTest extends TestCase
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
        $this->travelTo(now()->parse('2026-12-01 10:00:00'));
        $this->admin = User::factory()->admin()->create();
    }

    /**
     * @return array{0: Cancellation, 1: array<string, mixed>}
     */
    private function processing(CancellationCause $cause = CancellationCause::Klien): array
    {
        $ctx = $this->paidReferenceEvent('2026-12-15');
        $service = app(CancellationService::class);
        $cancellation = $service->submit($ctx['event'], $ctx['client'], [
            'reason' => 'Keluarga berhalangan hadir.', 'refund_bank_name' => 'BCA',
            'refund_account_number' => '1234567890', 'refund_account_name' => 'Rina Putri',
        ]);
        $service->approve($cancellation, $this->admin, $cause, null, $cause === CancellationCause::Klien ? null : 'Penyedia mundur.');
        $service->startRefund($cancellation, $this->admin);

        return [$cancellation->fresh(), $ctx];
    }

    private function complete(Cancellation $cancellation): TestResponse
    {
        return $this->actingAs($this->admin)->post(route('admin.pembatalan.complete-refund', $cancellation), [
            'refund_paid_at' => '2026-12-01', 'proof' => $this->pdfFile('refund.pdf'),
        ]);
    }

    public function test_refund_selesai_membatalkan_acara_dan_membuat_kompensasi(): void
    {
        [$cancellation, $ctx] = $this->processing();
        $invoiceBefore = $ctx['invoice']->fresh()->only(['status', 'grand_total', 'transfer_total', 'unique_code', 'total_commission', 'cleaning_fee', 'invoice_number', 'receipt_number']);

        $this->complete($cancellation)->assertRedirect();

        $this->assertSame(CancellationStatus::RefundSelesai, $cancellation->fresh()->status);
        $this->assertSame(EventStatus::Dibatalkan, $ctx['event']->fresh()->status);
        foreach (['venueItem', 'cateringItem'] as $key) {
            $this->assertSame(OrderItemStatus::Dibatalkan, $ctx[$key]->fresh()->status);
            $this->assertNull($ctx[$key]->fresh()->hold_key);
        }
        $this->assertSame($invoiceBefore, $ctx['invoice']->fresh()->only(array_keys($invoiceBefore)));
        $this->assertSame(InvoiceStatus::Lunas, $ctx['invoice']->fresh()->status);

        $venueComp = Disbursement::where('order_item_id', $ctx['venueItem']->id)->sole();
        $this->assertSame(DisbursementType::Kompensasi, $venueComp->type);
        $this->assertSame(6_000_000, $venueComp->amount);
        $this->assertSame(DisbursementStatus::BelumCair, $venueComp->status);
        $this->assertSame('venue_profile', $venueComp->provider_type);
        $this->assertSame($ctx['venueItem']->provider_id, $venueComp->provider_id);
        $this->assertSame(3_000_000, Disbursement::where('order_item_id', $ctx['cateringItem']->id)->sole()->amount);
        $this->assertSame('vendor_profile', Disbursement::where('order_item_id', $ctx['cateringItem']->id)->sole()->provider_type);

        $fresh = $cancellation->fresh();
        $this->assertSame(34_500_000, $fresh->refund_amount + (int) Disbursement::sum('amount') + $fresh->company_income);
        $this->assertDatabaseHas('audit_logs', ['action' => 'event_cancelled_after_refund', 'subject_type' => 'event', 'subject_id' => $ctx['event']->id]);

        Notification::assertSentTo($ctx['client'], CancellationStatusChangedNotification::class, fn ($n) => $n->cancellation->status === CancellationStatus::RefundSelesai);
        $venueUser = $ctx['venueItem']->provider->user;
        Notification::assertSentTo($venueUser, CompensationRecordedNotification::class, function (CompensationRecordedNotification $n) use ($venueUser, $ctx): bool {
            $payload = json_encode($n->toArray($venueUser));

            return str_contains($payload, 'Acara #'.$ctx['event']->id) && ! str_contains($payload, $ctx['client']->name);
        });
        Notification::assertSentTo($ctx['cateringItem']->provider->user, CompensationRecordedNotification::class);
    }

    public function test_kompensasi_nol_tidak_membuat_baris_pencairan(): void
    {
        [$cancellation, $ctx] = $this->processing(CancellationCause::Vendor);

        $this->complete($cancellation)->assertRedirect();

        $this->assertSame(EventStatus::Dibatalkan, $ctx['event']->fresh()->status);
        $this->assertSame(0, Disbursement::count());
        Notification::assertNotSentTo($ctx['venueItem']->provider->user, CompensationRecordedNotification::class);
    }

    public function test_pemanggilan_ganda_tidak_menggandakan_efek(): void
    {
        [$cancellation] = $this->processing();

        $this->complete($cancellation)->assertRedirect();
        $this->complete($cancellation)->assertStatus(409);

        $this->assertSame(2, Disbursement::count());
        $this->assertSame(1, Media::where('owner_type', 'cancellation')->count());

        $method = new \ReflectionMethod(CancellationService::class, 'applyRefundCompletedEffects');
        $method->invoke(app(CancellationService::class), $cancellation->fresh(), $this->admin, null);
        $this->assertSame(2, Disbursement::count());
        $this->assertSame(1, AuditLog::where('action', 'event_cancelled_after_refund')->count());
    }

    public function test_gagal_di_tengah_transaksi_rollback_seluruhnya(): void
    {
        [$cancellation, $ctx] = $this->processing();
        Disbursement::creating(function (Disbursement $disbursement): void {
            if ($disbursement->order_item_id === $GLOBALS['__failOrderItem']) {
                throw new RuntimeException('gagal simulasi');
            }
        });
        $GLOBALS['__failOrderItem'] = $ctx['cateringItem']->id;

        try {
            app(CancellationService::class)->completeRefund($cancellation, $this->admin, now(), $this->pdfFile());
            $this->fail('Harus gagal.');
        } catch (RuntimeException $e) {
            $this->assertSame('gagal simulasi', $e->getMessage());
        } finally {
            unset($GLOBALS['__failOrderItem']);
        }

        $this->assertSame(CancellationStatus::RefundDiproses, $cancellation->fresh()->status);
        $this->assertNull($cancellation->fresh()->refund_proof_media_id);
        $this->assertSame(EventStatus::Terkonfirmasi, $ctx['event']->fresh()->status);
        $this->assertSame(OrderItemStatus::Terkonfirmasi, $ctx['venueItem']->fresh()->status);
        $this->assertNotNull($ctx['venueItem']->fresh()->hold_key);
        $this->assertSame(0, Disbursement::count());
        $this->assertSame(0, Media::where('owner_type', 'cancellation')->count());
        Storage::disk('private')->assertDirectoryEmpty('refunds');
    }
}
