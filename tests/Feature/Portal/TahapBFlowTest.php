<?php

namespace Tests\Feature\Portal;

use App\Enums\CancellationStatus;
use App\Enums\DisbursementStatus;
use App\Enums\DisbursementType;
use App\Enums\EventStatus;
use App\Enums\VerificationStatus;
use App\Models\Cancellation;
use App\Models\Disbursement;
use App\Models\Media;
use App\Models\ProviderBankAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsPaidEvents;
use Tests\Concerns\MakesUploadedFiles;
use Tests\TestCase;

/**
 * Tes lintas fitur Tahap B (B11): alur penuh, konservasi uang, idempotensi, dan hak akses.
 */
class TahapBFlowTest extends TestCase
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

    public function test_alur_penuh_pembatalan_lewat_http(): void
    {
        $ctx = $this->paidReferenceEvent('2026-12-15');

        $this->actingAs($ctx['client'])->get(route('klien.acara.detail', $ctx['event']))->assertSee('Perkiraan')->assertSee('24.150.123');
        $this->actingAs($ctx['client'])->post(route('klien.acara.pembatalan.store', $ctx['event']), [
            'reason' => 'Keluarga berhalangan hadir.', 'refund_bank_name' => 'BCA',
            'refund_account_number' => '1234567890', 'refund_account_name' => 'Rina Putri', 'acknowledge' => '1',
        ])->assertRedirect();
        $cancellation = Cancellation::sole();

        $this->actingAs($this->admin)->post(route('admin.pembatalan.review', $cancellation));
        $this->actingAs($this->admin)->post(route('admin.pembatalan.approve', $cancellation), ['cause' => 'klien']);
        $this->actingAs($this->admin)->post(route('admin.pembatalan.start-refund', $cancellation));
        $this->actingAs($this->admin)->post(route('admin.pembatalan.complete-refund', $cancellation), ['refund_paid_at' => '2026-12-01', 'proof' => $this->pdfFile()]);

        $cancellation->refresh();
        $this->assertSame(CancellationStatus::RefundSelesai, $cancellation->status);
        $this->assertSame(EventStatus::Dibatalkan, $ctx['event']->fresh()->status);
        $this->assertNull($ctx['venueItem']->fresh()->hold_key);
        $this->assertSame(24_150_123, $cancellation->refundTotal());
        $this->assertSame([6_000_000, 3_000_000], Disbursement::orderBy('id')->pluck('amount')->all());
        $this->assertSame(1_350_000, $cancellation->company_income);
        $this->assertSame(34_500_000, $cancellation->refund_amount + $cancellation->provider_compensation_total + $cancellation->company_income);
        $this->assertSame((int) Disbursement::sum('amount'), $cancellation->provider_compensation_total);

        $this->actingAs($ctx['client'])->get(route('klien.acara.detail', $ctx['event']))->assertSee('Refund selesai')->assertSee(route('media.show', $cancellation->refund_proof_media_id), false);
    }

    public function test_alur_penuh_selesai_dan_konservasi_hak_pesanan(): void
    {
        $ctx = $this->paidReferenceEvent('2026-11-30');
        ProviderBankAccount::factory()->create(['owner_type' => 'venue_profile', 'owner_id' => $ctx['venueItem']->provider_id, 'verification_status' => VerificationStatus::Terverifikasi]);
        ProviderBankAccount::factory()->create(['owner_type' => 'vendor_profile', 'owner_id' => $ctx['cateringItem']->provider_id, 'verification_status' => VerificationStatus::Terverifikasi]);

        $this->actingAs($this->admin)->post(route('admin.acara.selesai', $ctx['event']))->assertSessionHas('success');
        foreach (Disbursement::all() as $disbursement) {
            $this->actingAs($this->admin)->post(route('admin.pencairan.record', $disbursement), ['paid_at' => '2026-12-01', 'proof' => $this->pdfFile()])->assertRedirect();
        }

        $this->assertSame(2, Disbursement::where('status', DisbursementStatus::Cair)->where('type', DisbursementType::HakPesanan)->count());
        $invoice = $ctx['invoice']->fresh();
        $this->assertSame((int) $invoice->grand_total, (int) Disbursement::sum('amount') + (int) $invoice->total_commission + (int) $invoice->cleaning_fee);
        $this->actingAs($ctx['client'])->get(route('klien.acara.detail', $ctx['event']))->assertDontSee('id="pembatalan"', false)->assertDontSee('Ajukan pembatalan');
    }

    public function test_aksi_uang_ganda_tidak_menggandakan(): void
    {
        $ctx = $this->paidReferenceEvent('2026-12-15');
        $payload = ['reason' => 'Keluarga berhalangan hadir.', 'refund_bank_name' => 'BCA', 'refund_account_number' => '123', 'refund_account_name' => 'Rina', 'acknowledge' => '1'];

        $this->actingAs($ctx['client'])->post(route('klien.acara.pembatalan.store', $ctx['event']), $payload);
        $this->actingAs($ctx['client'])->post(route('klien.acara.pembatalan.store', $ctx['event']), $payload);
        $this->assertSame(1, Cancellation::count());
        $cancellation = Cancellation::sole();

        foreach (['approve' => ['cause' => 'klien'], 'start-refund' => []] as $action => $data) {
            $this->actingAs($this->admin)->post(route("admin.pembatalan.{$action}", $cancellation), $data)->assertRedirect();
            $this->actingAs($this->admin)->post(route("admin.pembatalan.{$action}", $cancellation), $data)->assertStatus(409);
        }
        $this->actingAs($this->admin)->post(route('admin.pembatalan.complete-refund', $cancellation), ['refund_paid_at' => '2026-12-01', 'proof' => $this->pdfFile()])->assertRedirect();
        $this->actingAs($this->admin)->post(route('admin.pembatalan.complete-refund', $cancellation), ['refund_paid_at' => '2026-12-01', 'proof' => $this->pdfFile()])->assertStatus(409);

        $this->assertSame(2, Disbursement::count());
        $this->assertSame(9_000_000, (int) Disbursement::sum('amount'));
        $this->assertSame(1, Media::where('owner_type', 'cancellation')->count());
    }

    public function test_semua_route_baru_wajib_auth_dan_role(): void
    {
        $ctx = $this->paidReferenceEvent('2026-12-15');
        $cancellation = Cancellation::factory()->create(['event_id' => $ctx['event']->id, 'invoice_id' => $ctx['invoice']->id]);
        $disbursement = Disbursement::create(['order_item_id' => $ctx['venueItem']->id, 'provider_type' => 'venue_profile', 'provider_id' => $ctx['venueItem']->provider_id, 'type' => DisbursementType::HakPesanan, 'amount' => 1, 'status' => DisbursementStatus::BelumCair]);

        $routes = [
            'admin' => [
                ['GET', route('admin.pembatalan')], ['GET', route('admin.pembatalan.show', $cancellation)],
                ['POST', route('admin.pembatalan.review', $cancellation)], ['POST', route('admin.pembatalan.reject', $cancellation)],
                ['POST', route('admin.pembatalan.approve', $cancellation)], ['POST', route('admin.pembatalan.start-refund', $cancellation)],
                ['POST', route('admin.pembatalan.complete-refund', $cancellation)], ['POST', route('admin.acara.selesai', $ctx['event'])],
                ['GET', route('admin.pencairan')], ['POST', route('admin.pencairan.record', $disbursement)],
            ],
            'klien' => [['POST', route('klien.acara.batalkan', $ctx['event'])], ['POST', route('klien.acara.pembatalan.store', $ctx['event'])]],
            'vendor' => [['GET', route('vendor.pencairan')]],
            'venue' => [['GET', route('venue.pencairan')]],
        ];

        foreach ($routes as $owner => $list) {
            foreach ($list as [$method, $url]) {
                $this->call($method, $url)->assertRedirect(route('login'));
                foreach (array_diff(['admin', 'klien', 'vendor', 'venue'], [$owner]) as $role) {
                    $this->actingAs(User::factory()->{$role}()->create())->call($method, $url)->assertForbidden();
                }
                auth()->logout();
            }
        }

        foreach (['admin.pembatalan', 'admin.pencairan', 'admin.acara.selesai', 'klien.acara.batalkan', 'klien.acara.pembatalan.store', 'vendor.pencairan', 'venue.pencairan'] as $name) {
            $middleware = Route::getRoutes()->getByName($name)->gatherMiddleware();
            $this->assertContains('auth', $middleware, $name);
            $this->assertTrue(collect($middleware)->contains(fn (string $m): bool => str_starts_with($m, 'role:')), $name);
        }
        $this->assertSame(CancellationStatus::Diajukan, $cancellation->fresh()->status);
        $this->assertSame(EventStatus::Terkonfirmasi, $ctx['event']->fresh()->status);
    }
}
