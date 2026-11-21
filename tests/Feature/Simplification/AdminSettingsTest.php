<?php

namespace Tests\Feature\Simplification;

use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Models\AuditLog;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\User;
use App\Models\VendorProfile;
use App\Services\InvoiceIssuer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Halaman Pengaturan Admin menyimpan parameter sistem dengan validasi
 * server dan mencatat setiap perubahan di log aktivitas.
 */
class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        return array_replace([
            'commission_percent' => 12,
            'payment_deadline_hours' => 72,
            'objection_window_hours' => 24,
            'admin_review_sla_hours' => 24,
            'unique_code_enabled' => '1',
            'admin_whatsapp' => '0812-3456-7890',
            'refund_tiers' => [
                ['min_days' => 30, 'percent' => 90],
                ['min_days' => 7, 'percent' => 50],
                ['min_days' => 0, 'percent' => 0],
                ['min_days' => '', 'percent' => ''],
            ],
            'budget_allocation' => ['venue' => 40, 'catering' => 30, 'dekorasi' => 10, 'hiburan' => 10, 'peralatan' => 5, 'cadangan' => 5],
        ], $overrides);
    }

    public function test_admin_menyimpan_pengaturan_dan_nilai_langsung_terbaca(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.pengaturan.update'), $this->payload())
            ->assertRedirect(route('admin.pengaturan'))
            ->assertSessionHasNoErrors();

        $this->assertSame(12, Setting::get('commission_percent'));
        $this->assertSame(72, Setting::get('payment_deadline_hours'));
        $this->assertTrue(Setting::get('unique_code_enabled'));
        $this->assertSame('6281234567890', Setting::get('admin_whatsapp'));
        $this->assertSame([
            ['min_days' => 30, 'percent' => 90],
            ['min_days' => 7, 'percent' => 50],
            ['min_days' => 0, 'percent' => 0],
        ], Setting::get('refund_tiers'));
        $this->assertSame(40, Setting::get('budget_allocation')['venue']);

        $this->actingAs($admin)->get(route('admin.pengaturan'))
            ->assertOk()
            ->assertSee('value="72"', false)
            ->assertSee('value="6281234567890"', false)
            ->assertSee('Perubahan komisi hanya berlaku untuk pesanan yang disetujui setelah disimpan; pesanan lama tetap memakai komisi yang sudah dikunci.')
            ->assertDontSee('Aturan sistem');
    }

    public function test_setiap_perubahan_dicatat_di_log_aktivitas(): void
    {
        $admin = User::factory()->admin()->create();
        Setting::set('commission_percent', 10);

        $this->actingAs($admin)->put(route('admin.pengaturan.update'), $this->payload());

        $log = AuditLog::where('action', 'setting_updated')->where('reason', 'like', '%commission_percent%')->first();
        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->actor_id);
        $this->assertSame('10', $log->from_status);
        $this->assertSame('12', $log->to_status);

        // Simpan ulang tanpa perubahan tidak menambah log.
        $count = AuditLog::where('action', 'setting_updated')->count();
        $this->actingAs($admin)->put(route('admin.pengaturan.update'), $this->payload());
        $this->assertSame($count, AuditLog::where('action', 'setting_updated')->count());
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'komisi > 50' => [['commission_percent' => 51], 'commission_percent'],
            'batas bayar 0' => [['payment_deadline_hours' => 0], 'payment_deadline_hours'],
            'batas bayar > 720' => [['payment_deadline_hours' => 721], 'payment_deadline_hours'],
            'keberatan > 168' => [['objection_window_hours' => 169], 'objection_window_hours'],
            'sla 0' => [['admin_review_sla_hours' => 0], 'admin_review_sla_hours'],
            'whatsapp pendek' => [['admin_whatsapp' => '0812'], 'admin_whatsapp'],
            'whatsapp bukan 62' => [['admin_whatsapp' => '+1 555 123 4567'], 'admin_whatsapp'],
            'tier terakhir bukan 0' => [['refund_tiers' => [['min_days' => 30, 'percent' => 90], ['min_days' => 7, 'percent' => 50]]], 'refund_tiers'],
            'tier tidak urut' => [['refund_tiers' => [['min_days' => 7, 'percent' => 50], ['min_days' => 30, 'percent' => 50], ['min_days' => 0, 'percent' => 0]]], 'refund_tiers'],
            'tier persen naik' => [['refund_tiers' => [['min_days' => 30, 'percent' => 50], ['min_days' => 7, 'percent' => 70], ['min_days' => 0, 'percent' => 0]]], 'refund_tiers'],
            'tier hari ganda' => [['refund_tiers' => [['min_days' => 7, 'percent' => 50], ['min_days' => 7, 'percent' => 40], ['min_days' => 0, 'percent' => 0]]], 'refund_tiers.1.min_days'],
            'tier persen > 100' => [['refund_tiers' => [['min_days' => 0, 'percent' => 101]]], 'refund_tiers.0.percent'],
            'budget bukan 100' => [['budget_allocation' => ['venue' => 50, 'catering' => 30, 'dekorasi' => 10, 'hiburan' => 10, 'peralatan' => 5, 'cadangan' => 5]], 'budget_allocation'],
            'budget negatif' => [['budget_allocation' => ['venue' => -5, 'catering' => 45, 'dekorasi' => 30, 'hiburan' => 10, 'peralatan' => 10, 'cadangan' => 10]], 'budget_allocation.venue'],
        ];
    }

    /**
     * @param  array<string, mixed>  $override
     */
    #[DataProvider('invalidPayloads')]
    public function test_input_tidak_valid_ditolak_dan_tidak_disimpan(array $override, string $errorKey): void
    {
        $admin = User::factory()->admin()->create();
        Setting::set('commission_percent', 10);

        $this->actingAs($admin)->from(route('admin.pengaturan'))
            ->put(route('admin.pengaturan.update'), $this->payload($override))
            ->assertRedirect(route('admin.pengaturan'))
            ->assertSessionHasErrors($errorKey);

        $this->assertSame(10, Setting::get('commission_percent'));
        $this->assertSame(0, AuditLog::where('action', 'setting_updated')->count());
    }

    public function test_whatsapp_kosong_diperbolehkan(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.pengaturan.update'), $this->payload(['admin_whatsapp' => '']))
            ->assertSessionHasNoErrors();

        $this->assertSame('', Setting::get('admin_whatsapp'));
    }

    public function test_pengaturan_hanya_untuk_admin(): void
    {
        foreach (['klien', 'vendor', 'venue'] as $role) {
            $user = User::factory()->{$role}()->create();
            $this->actingAs($user)->get('/admin/pengaturan')->assertForbidden();
            $this->actingAs($user)->put('/admin/pengaturan', $this->payload())->assertForbidden();
        }

        $this->assertNull(Setting::get('commission_percent'));
    }

    public function test_rekening_perusahaan_menjadi_tab_pengaturan(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/pengaturan')->assertOk()->assertSee(route('admin.rekening.index'));
        $this->actingAs($admin)->get('/admin/rekening')->assertOk()->assertSee('Rekening perusahaan')->assertSee(route('admin.pengaturan'));
    }

    public function test_batas_pembayaran_baru_dipakai_tagihan_berikutnya(): void
    {
        Carbon::setTestNow('2026-11-20 10:00:00');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.pengaturan.update'), $this->payload(['payment_deadline_hours' => 6]))
            ->assertSessionHasNoErrors();

        $event = Event::factory()->create(['status' => EventStatus::Diajukan]);
        $vendorProfile = VendorProfile::factory()->create();
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendorProfile->id, 'catalog_price' => 1_000_000]);
        OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendorProfile->id,
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
            'provider_price' => 1_000_000,
            'commission' => 100_000,
            'status' => OrderItemStatus::Disetujui,
            'approved_at' => now()->subHours(25),
            'objection_deadline' => now()->subHour(),
        ]);

        $invoice = (new InvoiceIssuer)->issueForEvent($event);

        $this->assertNotNull($invoice);
        $this->assertSame('2026-11-20 16:00:00', $invoice->due_at->format('Y-m-d H:i:s'));
        Carbon::setTestNow();
    }
}
