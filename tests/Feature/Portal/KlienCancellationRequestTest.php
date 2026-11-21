<?php

namespace Tests\Feature\Portal;

use App\Enums\CancellationCause;
use App\Enums\CancellationStatus;
use App\Enums\EventStatus;
use App\Enums\InvoiceStatus;
use App\Models\Cancellation;
use App\Models\User;
use App\Notifications\CancellationStatusChangedNotification;
use App\Notifications\CancellationSubmittedNotification;
use App\Services\RefundCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsPaidEvents;
use Tests\TestCase;

class KlienCancellationRequestTest extends TestCase
{
    use BuildsPaidEvents;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->parse('2026-12-01 10:00:00'));
    }

    /**
     * @return array<string, string>
     */
    private function payload(): array
    {
        return [
            'reason' => 'Keluarga berhalangan hadir pada tanggal tersebut.',
            'refund_bank_name' => 'BCA',
            'refund_account_number' => '1234567890',
            'refund_account_name' => 'Rina Putri',
            'acknowledge' => '1',
        ];
    }

    public function test_preview_tampil_dengan_label_perkiraan(): void
    {
        ['client' => $client, 'event' => $event] = $this->paidReferenceEvent('2026-12-15');

        $this->actingAs($client)->get(route('klien.acara.detail', $event))
            ->assertOk()
            ->assertSee('Perkiraan; nominal final ditetapkan Admin')
            ->assertSee('14 hari')
            ->assertSee('24.150.123')
            ->assertSee('Perubahan sebagian ditangani Admin; hubungi Admin lewat tombol WhatsApp.');

        $this->assertSame(0, Cancellation::count());
    }

    public function test_pengajuan_menyimpan_snapshot_sama_dengan_kalkulator(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        ['client' => $client, 'event' => $event, 'invoice' => $invoice, 'venueItem' => $venueItem] = $this->paidReferenceEvent('2026-12-15');
        $expected = app(RefundCalculator::class)->forInvoice($event, $invoice, CancellationCause::Klien, now());

        $this->actingAs($client)->post(route('klien.acara.pembatalan.store', $event), $this->payload())
            ->assertRedirect(route('klien.acara.detail', $event))
            ->assertSessionHas('success');

        $cancellation = Cancellation::sole();
        $this->assertSame(CancellationStatus::Diajukan, $cancellation->status);
        $this->assertSame(CancellationCause::Klien, $cancellation->cause);
        $this->assertSame($invoice->id, $cancellation->invoice_id);
        $this->assertSame(70, $cancellation->refund_percent);
        $this->assertSame(24_150_000, $cancellation->refund_amount);
        $this->assertSame(123, $cancellation->unique_code_refund);
        $this->assertSame(9_000_000, $cancellation->provider_compensation_total);
        $this->assertSame(1_350_000, $cancellation->company_income);
        $this->assertSame($expected['policy_snapshot'], $cancellation->policy_snapshot);
        $this->assertSame('1234567890', $cancellation->refund_account_number);

        $this->assertSame(EventStatus::Terkonfirmasi, $event->fresh()->status);
        $this->assertSame(InvoiceStatus::Lunas, $invoice->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'cancellation_submitted', 'subject_type' => 'cancellation', 'subject_id' => $cancellation->id, 'actor_id' => $client->id]);

        Notification::assertSentTo($admin, CancellationSubmittedNotification::class);
        Notification::assertSentTo($client, CancellationStatusChangedNotification::class);
        Notification::assertNothingSentTo($venueItem->provider->user);

        $this->actingAs($client)->get(route('klien.acara.detail', $event))->assertSee('Pengajuan pembatalan #'.$cancellation->id)->assertDontSee('Kirim pengajuan pembatalan');
    }

    public function test_satu_pengajuan_aktif_dan_boleh_ulang_setelah_ditolak(): void
    {
        Notification::fake();
        ['client' => $client, 'event' => $event] = $this->paidReferenceEvent('2026-12-15');

        $this->actingAs($client)->post(route('klien.acara.pembatalan.store', $event), $this->payload())->assertSessionHas('success');
        $this->actingAs($client)->post(route('klien.acara.pembatalan.store', $event), $this->payload())
            ->assertSessionHas('error', 'Acara ini sudah memiliki pengajuan pembatalan yang sedang diproses.');
        $this->assertSame(1, Cancellation::count());

        Cancellation::sole()->update(['status' => CancellationStatus::Ditolak, 'decision_reason' => 'Tidak memenuhi syarat.']);
        $this->actingAs($client)->get(route('klien.acara.detail', $event))->assertSee('Tidak memenuhi syarat.')->assertSee('Kirim pengajuan pembatalan');

        $this->actingAs($client)->post(route('klien.acara.pembatalan.store', $event), $this->payload())->assertSessionHas('success');
        $this->assertSame(2, Cancellation::count());
    }

    public function test_syarat_status_acara_dan_tagihan(): void
    {
        ['client' => $client, 'event' => $event, 'invoice' => $invoice] = $this->paidReferenceEvent('2026-12-15');

        $invoice->update(['status' => InvoiceStatus::MenungguValidasi]);
        $this->actingAs($client)->post(route('klien.acara.pembatalan.store', $event), $this->payload())->assertSessionHas('error', 'Tagihan acara ini belum lunas.');

        $invoice->update(['status' => InvoiceStatus::Lunas]);
        $event->update(['status' => EventStatus::MenungguPembayaran]);
        $this->actingAs($client)->post(route('klien.acara.pembatalan.store', $event), $this->payload())->assertSessionHas('error');
        $this->actingAs($client)->get(route('klien.acara.detail', $event))->assertDontSee('Kirim pengajuan pembatalan');

        $this->assertSame(0, Cancellation::count());
    }

    public function test_tanggal_lewat_ditolak_tetapi_hari_h_masih_boleh(): void
    {
        Notification::fake();
        ['client' => $client, 'event' => $past] = $this->paidReferenceEvent('2026-11-30');
        $this->actingAs($client)->post(route('klien.acara.pembatalan.store', $past), $this->payload())
            ->assertSessionHas('error', 'Tanggal acara sudah lewat; pembatalan tidak diterima.');

        ['event' => $today] = $this->paidReferenceEvent('2026-12-01', $client);
        $this->actingAs($client)->post(route('klien.acara.pembatalan.store', $today), $this->payload())->assertSessionHas('success');
        $this->assertSame(0, Cancellation::sole()->refund_percent);
        $this->assertSame(123, Cancellation::sole()->refundTotal());
    }

    public function test_validasi_form_berbahasa_indonesia(): void
    {
        ['client' => $client, 'event' => $event] = $this->paidReferenceEvent('2026-12-15');

        $this->actingAs($client)->post(route('klien.acara.pembatalan.store', $event), ['reason' => 'pendek'])
            ->assertSessionHasErrors([
                'reason' => 'Alasan pembatalan minimal 10 karakter.',
                'refund_bank_name' => 'Nama bank tujuan refund wajib diisi.',
                'refund_account_number' => 'Nomor rekening tujuan refund wajib diisi.',
                'refund_account_name' => 'Nama pemilik rekening wajib diisi.',
                'acknowledge' => 'Centang pernyataan bahwa nominal akhir ditetapkan Admin.',
            ]);
        $this->assertSame(0, Cancellation::count());
    }

    public function test_hanya_pemilik(): void
    {
        ['event' => $event] = $this->paidReferenceEvent('2026-12-15');

        $this->actingAs(User::factory()->klien()->create())->post(route('klien.acara.pembatalan.store', $event), $this->payload())->assertNotFound();
        $this->actingAs(User::factory()->vendor()->create())->post(route('klien.acara.pembatalan.store', $event), $this->payload())->assertForbidden();
        $this->assertSame(0, Cancellation::count());
    }
}
