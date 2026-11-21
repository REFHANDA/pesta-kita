<?php

namespace Tests\Feature\Portal;

use App\Enums\CancellationStatus;
use App\Enums\DisbursementStatus;
use App\Enums\DisbursementType;
use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Models\AuditLog;
use App\Models\Cancellation;
use App\Models\Disbursement;
use App\Models\User;
use App\Notifications\EventCompletedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsPaidEvents;
use Tests\TestCase;

class AdminEventCompletionTest extends TestCase
{
    use BuildsPaidEvents;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->travelTo(now()->parse('2026-12-16 09:00:00'));
        $this->admin = User::factory()->admin()->create();
    }

    public function test_acara_lewat_ditandai_selesai_dan_pencairan_hak_pesanan_dibuat(): void
    {
        $ctx = $this->paidReferenceEvent('2026-12-15');

        $this->actingAs($this->admin)->get(route('admin.acara.detail', $ctx['event']))->assertOk()->assertSee('Tandai selesai');
        $this->actingAs($this->admin)->post(route('admin.acara.selesai', $ctx['event']))->assertSessionHas('success');

        $this->assertSame(EventStatus::Selesai, $ctx['event']->fresh()->status);
        $this->assertSame(OrderItemStatus::Selesai, $ctx['venueItem']->fresh()->status);
        $this->assertSame(OrderItemStatus::Selesai, $ctx['cateringItem']->fresh()->status);

        $this->assertSame(2, Disbursement::where('type', DisbursementType::HakPesanan)->where('status', DisbursementStatus::BelumCair)->count());
        $this->assertSame(20_000_000, Disbursement::where('order_item_id', $ctx['venueItem']->id)->sole()->amount);
        $this->assertSame(10_000_000, Disbursement::where('order_item_id', $ctx['cateringItem']->id)->sole()->amount);
        $this->assertSame(30_000_000, (int) Disbursement::sum('amount'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'event_completed', 'subject_type' => 'event', 'subject_id' => $ctx['event']->id, 'actor_id' => $this->admin->id]);

        $venueUser = $ctx['venueItem']->provider->user;
        Notification::assertSentTo($venueUser, EventCompletedNotification::class, function (EventCompletedNotification $n) use ($venueUser, $ctx): bool {
            $payload = json_encode($n->toArray($venueUser));

            return str_contains($payload, 'Acara #'.$ctx['event']->id.' selesai; hak Anda siap dicairkan.') && ! str_contains($payload, $ctx['client']->name);
        });
        Notification::assertSentTo($ctx['client'], EventCompletedNotification::class);
    }

    public function test_tanggal_belum_lewat_ditolak(): void
    {
        $ctx = $this->paidReferenceEvent('2026-12-16');

        $this->actingAs($this->admin)->post(route('admin.acara.selesai', $ctx['event']))
            ->assertSessionHas('error', fn (string $m): bool => str_contains($m, 'setelah tanggal acara'));
        $this->assertSame(EventStatus::Terkonfirmasi, $ctx['event']->fresh()->status);
        $this->assertSame(0, Disbursement::count());
    }

    public function test_pembatalan_aktif_ditolak(): void
    {
        $ctx = $this->paidReferenceEvent('2026-12-15');
        Cancellation::factory()->create(['event_id' => $ctx['event']->id, 'invoice_id' => $ctx['invoice']->id, 'status' => CancellationStatus::RefundDiproses]);

        $this->actingAs($this->admin)->post(route('admin.acara.selesai', $ctx['event']))
            ->assertSessionHas('error', fn (string $m): bool => str_contains($m, 'pembatalan'));
        $this->assertSame(EventStatus::Terkonfirmasi, $ctx['event']->fresh()->status);
    }

    public function test_status_selain_terkonfirmasi_ditolak(): void
    {
        $ctx = $this->paidReferenceEvent('2026-12-15');
        $ctx['event']->update(['status' => EventStatus::MenungguPembayaran]);

        $this->actingAs($this->admin)->post(route('admin.acara.selesai', $ctx['event']))
            ->assertSessionHas('error', 'Hanya acara terkonfirmasi yang dapat ditandai selesai.');
        $this->assertSame(0, Disbursement::count());
    }

    public function test_dua_kali_klik_tidak_menggandakan(): void
    {
        $ctx = $this->paidReferenceEvent('2026-12-15');

        $this->actingAs($this->admin)->post(route('admin.acara.selesai', $ctx['event']))->assertSessionHas('success');
        $this->actingAs($this->admin)->post(route('admin.acara.selesai', $ctx['event']))->assertStatus(409);

        $this->assertSame(2, Disbursement::count());
        $this->assertSame(1, AuditLog::where('action', 'event_completed')->count());
        $this->actingAs($this->admin)->get(route('admin.acara.detail', $ctx['event']))->assertDontSee('id="tandai-selesai"', false);
    }

    public function test_bukan_admin_403(): void
    {
        $ctx = $this->paidReferenceEvent('2026-12-15');

        $this->actingAs($ctx['client'])->post(route('admin.acara.selesai', $ctx['event']))->assertForbidden();
        $this->actingAs($ctx['venueItem']->provider->user)->post(route('admin.acara.selesai', $ctx['event']))->assertForbidden();
        $this->assertSame(EventStatus::Terkonfirmasi, $ctx['event']->fresh()->status);
    }
}
