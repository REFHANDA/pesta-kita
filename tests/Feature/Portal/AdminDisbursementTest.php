<?php

namespace Tests\Feature\Portal;

use App\Enums\DisbursementStatus;
use App\Enums\DisbursementType;
use App\Enums\MediaKind;
use App\Enums\VerificationStatus;
use App\Models\AuditLog;
use App\Models\Disbursement;
use App\Models\ProviderBankAccount;
use App\Models\User;
use App\Notifications\DisbursementPaidNotification;
use App\Services\EventCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsPaidEvents;
use Tests\Concerns\MakesUploadedFiles;
use Tests\TestCase;

class AdminDisbursementTest extends TestCase
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
    }

    private function venueDisbursement(): Disbursement
    {
        return Disbursement::where('order_item_id', $this->ctx['venueItem']->id)->sole();
    }

    private function verifyVenueAccount(): ProviderBankAccount
    {
        return ProviderBankAccount::factory()->create([
            'owner_type' => 'venue_profile',
            'owner_id' => $this->ctx['venueItem']->provider_id,
            'bank_name' => 'Mandiri',
            'account_number' => '9876543210',
            'verification_status' => VerificationStatus::Terverifikasi,
        ]);
    }

    public function test_daftar_ringkasan_filter_dan_rekening_kosong(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.pencairan'))->assertOk();
        $response->assertSee('Rp 30.000.000')->assertSee('Rp 20.000.000')->assertSee('Rp 10.000.000')
            ->assertSee('Acara #'.$this->ctx['event']->id)->assertSee('Hak pesanan')
            ->assertSee('Penyedia belum mengisi rekening');

        $this->verifyVenueAccount();
        $this->actingAs($this->admin)->get(route('admin.pencairan', ['penyedia' => 'venue_profile:'.$this->ctx['venueItem']->provider_id]))
            ->assertSee('9876543210')->assertSee('Rp 20.000.000')->assertDontSee('<b>Rp 10.000.000</b>', false);
        $this->actingAs($this->admin)->get(route('admin.pencairan', ['jenis' => 'kompensasi']))->assertSee('Belum ada data.');
        $this->actingAs($this->admin)->get(route('admin.pencairan', ['tab' => 'cair']))->assertSee('Belum ada data.');
    }

    public function test_catat_transfer_mengubah_status_dan_tidak_bisa_dua_kali(): void
    {
        $this->verifyVenueAccount();
        $disbursement = $this->venueDisbursement();

        $this->actingAs($this->admin)->post(route('admin.pencairan.record', $disbursement), [
            'paid_at' => '2026-12-16', 'proof' => $this->pdfFile('transfer.pdf'),
        ])->assertRedirect(route('admin.pencairan', ['tab' => 'cair']));

        $fresh = $disbursement->fresh();
        $this->assertSame(DisbursementStatus::Cair, $fresh->status);
        $this->assertSame('2026-12-16', $fresh->paid_at->toDateString());
        $this->assertSame($this->admin->id, $fresh->recorded_by);
        $this->assertSame(MediaKind::BuktiPencairan, $fresh->proof->kind);
        $this->assertSame('private', $fresh->proof->disk);
        Storage::disk('private')->assertExists($fresh->proof->path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'disbursement_paid', 'subject_type' => 'disbursement', 'subject_id' => $disbursement->id]);

        $venueUser = $this->ctx['venueItem']->provider->user;
        Notification::assertSentTo($venueUser, DisbursementPaidNotification::class, function (DisbursementPaidNotification $n) use ($venueUser): bool {
            return ! str_contains(json_encode($n->toArray($venueUser)), $this->ctx['client']->name);
        });

        $this->actingAs($this->admin)->post(route('admin.pencairan.record', $disbursement), [
            'paid_at' => '2026-12-16', 'proof' => $this->pdfFile('lagi.pdf'),
        ])->assertStatus(409);
        $this->assertSame($fresh->proof_media_id, $disbursement->fresh()->proof_media_id);
        $this->assertSame(1, AuditLog::where('action', 'disbursement_paid')->count());
        $this->assertCount(1, Storage::disk('private')->allFiles('disbursements'));
    }

    public function test_rekening_kosong_memblokir(): void
    {
        ProviderBankAccount::factory()->create(['owner_type' => 'venue_profile', 'owner_id' => $this->ctx['venueItem']->provider_id, 'verification_status' => VerificationStatus::Menunggu]);
        $disbursement = $this->venueDisbursement();

        $this->actingAs($this->admin)->post(route('admin.pencairan.record', $disbursement), [
            'paid_at' => '2026-12-16', 'proof' => $this->pdfFile(),
        ])->assertSessionHas('error', 'Rekening penyedia belum tersedia');

        $this->assertSame(DisbursementStatus::BelumCair, $disbursement->fresh()->status);
        $this->assertCount(0, Storage::disk('private')->allFiles('disbursements'));
    }

    public function test_tanggal_masa_depan_ditolak(): void
    {
        $this->verifyVenueAccount();
        $disbursement = $this->venueDisbursement();

        $this->actingAs($this->admin)->post(route('admin.pencairan.record', $disbursement), [
            'paid_at' => '2026-12-17', 'proof' => $this->pdfFile(),
        ])->assertSessionHasErrors('paid_at');
        $this->assertSame(DisbursementStatus::BelumCair, $disbursement->fresh()->status);
    }

    public function test_hanya_admin(): void
    {
        $this->verifyVenueAccount();
        $disbursement = $this->venueDisbursement();

        foreach ([$this->ctx['client'], $this->ctx['venueItem']->provider->user, $this->ctx['cateringItem']->provider->user] as $user) {
            $this->actingAs($user)->get(route('admin.pencairan'))->assertForbidden();
            $this->actingAs($user)->post(route('admin.pencairan.record', $disbursement), ['paid_at' => '2026-12-16', 'proof' => $this->pdfFile()])->assertForbidden();
        }
        $this->assertSame(DisbursementStatus::BelumCair, $disbursement->fresh()->status);
        $this->assertSame(DisbursementType::HakPesanan, $disbursement->type);
    }
}
