<?php

namespace Tests\Feature\Portal;

use App\Enums\CancellationCause;
use App\Enums\CancellationStatus;
use App\Enums\MediaKind;
use App\Models\AuditLog;
use App\Models\Cancellation;
use App\Models\User;
use App\Notifications\CancellationStatusChangedNotification;
use App\Services\CancellationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsPaidEvents;
use Tests\Concerns\MakesUploadedFiles;
use Tests\TestCase;

class AdminCancellationTest extends TestCase
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
    private function submitted(string $eventDate = '2026-12-15'): array
    {
        $ctx = $this->paidReferenceEvent($eventDate);
        $cancellation = app(CancellationService::class)->submit($ctx['event'], $ctx['client'], [
            'reason' => 'Keluarga berhalangan hadir.',
            'refund_bank_name' => 'BCA',
            'refund_account_number' => '1234567890',
            'refund_account_name' => 'Rina Putri',
        ]);

        return [$cancellation, $ctx];
    }

    public function test_daftar_tab_dan_detail(): void
    {
        $this->actingAs($this->admin)->get(route('admin.pembatalan'))->assertOk()->assertSee('Belum ada data.');

        [$cancellation, $ctx] = $this->submitted();

        $this->actingAs($this->admin)->get(route('admin.pembatalan'))->assertOk()
            ->assertSee('Acara #'.$ctx['event']->id)->assertSee($ctx['client']->name)->assertSee('24.150.123')->assertSee('70%');
        $this->actingAs($this->admin)->get(route('admin.pembatalan', ['tab' => 'selesai']))->assertOk()->assertSee('Belum ada data.');
        $this->actingAs($this->admin)->get(route('admin.pembatalan.show', $cancellation))->assertOk()
            ->assertSee('6.000.000')->assertSee('3.000.000')->assertSee('1.350.000')->assertSee('1234567890')->assertSee('14 hari');
    }

    public function test_alur_sah_tinjau_setujui_proses_selesai(): void
    {
        [$cancellation, $ctx] = $this->submitted();

        $this->actingAs($this->admin)->post(route('admin.pembatalan.review', $cancellation))->assertRedirect(route('admin.pembatalan.show', $cancellation));
        $this->assertSame(CancellationStatus::Ditinjau, $cancellation->fresh()->status);
        $this->assertSame($this->admin->id, $cancellation->fresh()->reviewed_by);

        // Persetujuan seminggu kemudian tetap memakai sisa hari tanggal pengajuan.
        $this->travelTo(now()->parse('2026-12-09 10:00:00'));
        $this->actingAs($this->admin)->post(route('admin.pembatalan.approve', $cancellation), ['cause' => 'klien'])->assertRedirect();
        $fresh = $cancellation->fresh();
        $this->assertSame(CancellationStatus::Disetujui, $fresh->status);
        $this->assertSame(70, $fresh->refund_percent);
        $this->assertSame(14, $fresh->policy_snapshot['remaining_days']);

        $this->actingAs($this->admin)->post(route('admin.pembatalan.start-refund', $cancellation))->assertRedirect();
        $this->assertSame(CancellationStatus::RefundDiproses, $cancellation->fresh()->status);
        $this->actingAs($this->admin)->get(route('admin.pembatalan.show', $cancellation))->assertSee('24.150.123');

        $this->actingAs($this->admin)->post(route('admin.pembatalan.complete-refund', $cancellation), [
            'refund_paid_at' => '2026-12-09',
            'proof' => $this->pdfFile('bukti-refund.pdf'),
        ])->assertRedirect();

        $fresh = $cancellation->fresh();
        $this->assertSame(CancellationStatus::RefundSelesai, $fresh->status);
        $this->assertSame('2026-12-09', $fresh->refund_paid_at->toDateString());
        $this->assertSame(MediaKind::BuktiRefund, $fresh->refundProof->kind);
        $this->assertSame('cancellation', $fresh->refundProof->owner_type);
        Storage::disk('private')->assertExists($fresh->refundProof->path);

        foreach (['cancellation_reviewed', 'cancellation_approved', 'cancellation_refund_started', 'cancellation_refund_completed'] as $action) {
            $this->assertDatabaseHas('audit_logs', ['action' => $action, 'subject_type' => 'cancellation', 'subject_id' => $cancellation->id, 'actor_id' => $this->admin->id]);
        }
        Notification::assertSentToTimes($ctx['client'], CancellationStatusChangedNotification::class, 5);

        // Klien pemilik boleh mengunduh bukti; klien lain dan penyedia tidak.
        $this->actingAs($ctx['client'])->get(route('media.show', $fresh->refundProof))->assertOk();
        $this->actingAs(User::factory()->klien()->create())->get(route('media.show', $fresh->refundProof))->assertForbidden();
        $this->actingAs($ctx['venueItem']->provider->user)->get(route('media.show', $fresh->refundProof))->assertForbidden();
    }

    public function test_transisi_tidak_sah_ditolak_tanpa_mengubah_data(): void
    {
        [$cancellation] = $this->submitted();

        $this->actingAs($this->admin)->post(route('admin.pembatalan.start-refund', $cancellation))->assertStatus(409);
        $this->actingAs($this->admin)->post(route('admin.pembatalan.complete-refund', $cancellation), ['refund_paid_at' => '2026-12-01', 'proof' => $this->pdfFile()])->assertStatus(409);
        $this->assertSame(CancellationStatus::Diajukan, $cancellation->fresh()->status);
        $this->assertNull($cancellation->fresh()->refund_proof_media_id);
        Storage::disk('private')->assertDirectoryEmpty('refunds');

        $this->actingAs($this->admin)->post(route('admin.pembatalan.reject', $cancellation), ['decision_reason' => 'Dokumen tidak lengkap.'])->assertRedirect();
        $this->assertSame(CancellationStatus::Ditolak, $cancellation->fresh()->status);
        $this->actingAs($this->admin)->get(route('admin.pembatalan.show', $cancellation))
            ->assertSee('Tidak ada refund (pengajuan ditolak)')->assertDontSee('Total ditransfer ke klien');

        $this->actingAs($this->admin)->post(route('admin.pembatalan.approve', $cancellation), ['cause' => 'klien'])->assertStatus(409);
        $this->actingAs($this->admin)->post(route('admin.pembatalan.review', $cancellation))->assertStatus(409);
        $this->assertSame(CancellationStatus::Ditolak, $cancellation->fresh()->status);
        $this->assertSame(1, AuditLog::where('subject_type', 'cancellation')->where('action', '!=', 'cancellation_submitted')->count());
    }

    public function test_alasan_wajib_untuk_tolak_override_dan_penyebab_non_klien(): void
    {
        [$cancellation] = $this->submitted();

        $this->actingAs($this->admin)->post(route('admin.pembatalan.reject', $cancellation), [])->assertSessionHasErrors('decision_reason');
        $this->actingAs($this->admin)->post(route('admin.pembatalan.approve', $cancellation), ['cause' => 'klien', 'refund_percent' => 80])->assertSessionHasErrors('decision_reason');
        $this->actingAs($this->admin)->post(route('admin.pembatalan.approve', $cancellation), ['cause' => 'vendor'])->assertSessionHasErrors('decision_reason');
        $this->actingAs($this->admin)->post(route('admin.pembatalan.approve', $cancellation), ['cause' => 'klien', 'refund_percent' => 101, 'decision_reason' => 'x'])->assertSessionHasErrors('refund_percent');
        $this->assertSame(CancellationStatus::Diajukan, $cancellation->fresh()->status);
    }

    public function test_override_persen_ditandai_di_snapshot_dan_audit(): void
    {
        [$cancellation] = $this->submitted();

        $this->actingAs($this->admin)->post(route('admin.pembatalan.approve', $cancellation), [
            'cause' => 'klien', 'refund_percent' => 50, 'decision_reason' => 'Kesepakatan khusus.',
        ])->assertRedirect();

        $fresh = $cancellation->fresh();
        $this->assertSame(50, $fresh->refund_percent);
        $this->assertSame(17_250_000, $fresh->refund_amount);
        $this->assertSame(15_000_000, $fresh->provider_compensation_total);
        $this->assertSame(2_250_000, $fresh->company_income);
        $this->assertSame('override_admin', $fresh->policy_snapshot['percent_source']);
        $this->assertSame(70, $fresh->policy_snapshot['tier_percent']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'cancellation_approved', 'subject_id' => $cancellation->id, 'reason' => 'Penyebab: klien; persen 50% (override dari tier 70%). Kesepakatan khusus.']);
    }

    public function test_penyebab_vendor_memaksa_refund_penuh(): void
    {
        [$cancellation] = $this->submitted();

        $this->actingAs($this->admin)->post(route('admin.pembatalan.approve', $cancellation), [
            'cause' => 'force_majeure', 'refund_percent' => 10, 'decision_reason' => 'Bencana banjir di lokasi.',
        ])->assertRedirect();

        $fresh = $cancellation->fresh();
        $this->assertSame(CancellationCause::ForceMajeure, $fresh->cause);
        $this->assertSame(100, $fresh->refund_percent);
        $this->assertSame(34_500_123, $fresh->refundTotal());
        $this->assertSame(0, $fresh->provider_compensation_total);
        $this->assertSame(0, $fresh->company_income);
    }

    public function test_tanggal_transfer_masa_depan_dan_bukti_wajib(): void
    {
        [$cancellation] = $this->submitted();
        $cancellation->update(['status' => CancellationStatus::RefundDiproses]);

        $this->actingAs($this->admin)->post(route('admin.pembatalan.complete-refund', $cancellation), ['refund_paid_at' => '2026-12-02', 'proof' => $this->pdfFile()])
            ->assertSessionHasErrors('refund_paid_at');
        $this->actingAs($this->admin)->post(route('admin.pembatalan.complete-refund', $cancellation), ['refund_paid_at' => '2026-12-01'])
            ->assertSessionHasErrors('proof');
        $this->assertSame(CancellationStatus::RefundDiproses, $cancellation->fresh()->status);
    }

    public function test_bukan_admin_ditolak(): void
    {
        [$cancellation, $ctx] = $this->submitted();

        foreach ([$ctx['client'], $ctx['venueItem']->provider->user, User::factory()->vendor()->create()] as $user) {
            $this->actingAs($user)->get(route('admin.pembatalan'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.pembatalan.show', $cancellation))->assertForbidden();
            $this->actingAs($user)->post(route('admin.pembatalan.review', $cancellation))->assertForbidden();
            $this->actingAs($user)->post(route('admin.pembatalan.approve', $cancellation), ['cause' => 'klien'])->assertForbidden();
        }
        $this->assertSame(CancellationStatus::Diajukan, $cancellation->fresh()->status);
    }
}
