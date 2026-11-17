<?php

namespace Tests\Feature\Portal;

use App\Enums\QuoteDecisionResult;
use App\Enums\QuoteRequestStatus;
use App\Models\CatalogItem;
use App\Models\QuoteProposal;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminQuoteRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_before_deciding_a_quote(): void
    {
        [$quoteRequest] = $this->quoteWithProposal();

        $this->post(route('admin.penawaran.decide', [$quoteRequest, 'approve']), [])
            ->assertRedirect('/login');
        $this->assertDatabaseCount('quote_decisions', 0);
    }

    public function test_admin_approval_uses_saved_proposal_amount_and_writes_audit_log(): void
    {
        $admin = User::factory()->admin()->create();
        [$quoteRequest] = $this->quoteWithProposal();
        $proposal = QuoteProposal::create([
            'quote_request_id' => $quoteRequest->id,
            'proposed_amount' => 7_650_000,
            'notes' => 'Proposal terbaru tersimpan.',
            'submitted_at' => now()->addMinute(),
        ]);

        $response = $this->actingAs($admin)->post(
            route('admin.penawaran.decide', [$quoteRequest, 'approve']),
            ['approved_amount' => 1, 'decision' => 'ditolak', 'reason' => 'Harga disetujui.'],
        );

        $response->assertRedirect(route('admin.penawaran.detail', $quoteRequest))
            ->assertSessionHas('status');
        $this->assertDatabaseHas('quote_decisions', [
            'quote_request_id' => $quoteRequest->id,
            'admin_id' => $admin->id,
            'decision' => QuoteDecisionResult::Disetujui->value,
            'approved_amount' => $proposal->proposed_amount,
            'reason' => 'Harga disetujui.',
        ]);
        $this->assertDatabaseHas('quote_requests', [
            'id' => $quoteRequest->id,
            'status' => QuoteRequestStatus::DisetujuiAdmin->value,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'quote_approved',
            'subject_type' => 'quote_request',
            'subject_id' => $quoteRequest->id,
            'from_status' => QuoteRequestStatus::DiusulkanPenyedia->value,
            'to_status' => QuoteRequestStatus::DisetujuiAdmin->value,
        ]);

        $this->actingAs(User::query()->findOrFail($quoteRequest->client_id))
            ->get(route('klien.penawaran.detail', $quoteRequest))
            ->assertOk()
            ->assertSee('Rp '.number_format($proposal->proposed_amount, 0, ',', '.'));
    }

    public function test_admin_rejection_requires_and_records_a_reason(): void
    {
        $admin = User::factory()->admin()->create();
        [$quoteRequest] = $this->quoteWithProposal();
        $url = route('admin.penawaran.decide', [$quoteRequest, 'reject']);

        $invalidResponse = $this->actingAs($admin)
            ->from(route('admin.penawaran.detail', $quoteRequest))
            ->post($url, []);

        $invalidResponse->assertSessionHasErrors('reason');
        $this->assertSame('Alasan penolakan wajib diisi.', session('errors')->first('reason'));
        $this->assertDatabaseCount('quote_decisions', 0);
        $this->assertDatabaseHas('quote_requests', [
            'id' => $quoteRequest->id,
            'status' => QuoteRequestStatus::DiusulkanPenyedia->value,
        ]);

        $shortReasonResponse = $this->actingAs($admin)
            ->from(route('admin.penawaran.detail', $quoteRequest))
            ->post($url, ['reason' => 'No']);
        $shortReasonResponse->assertSessionHasErrors('reason');
        $this->assertSame('Alasan keputusan minimal 5 karakter.', session('errors')->first('reason'));
        $this->assertDatabaseCount('quote_decisions', 0);

        $reason = '<script>alert(1)</script>';
        $this->actingAs($admin)->post($url, ['reason' => $reason])
            ->assertRedirect(route('admin.penawaran.detail', $quoteRequest));

        $this->assertDatabaseHas('quote_decisions', [
            'quote_request_id' => $quoteRequest->id,
            'admin_id' => $admin->id,
            'decision' => QuoteDecisionResult::Ditolak->value,
            'approved_amount' => null,
            'reason' => $reason,
        ]);
        $this->assertDatabaseHas('quote_requests', [
            'id' => $quoteRequest->id,
            'status' => QuoteRequestStatus::DitolakAdmin->value,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'quote_rejected',
            'subject_id' => $quoteRequest->id,
            'reason' => $reason,
        ]);

        $clientResponse = $this->actingAs(User::query()->findOrFail($quoteRequest->client_id))
            ->get(route('klien.penawaran.detail', $quoteRequest));
        $clientResponse->assertOk()->assertSee($reason);
        $clientResponse->assertDontSee($reason, false);
    }

    public function test_admin_cannot_decide_the_same_request_twice(): void
    {
        $admin = User::factory()->admin()->create();
        [$quoteRequest] = $this->quoteWithProposal();
        $url = route('admin.penawaran.decide', [$quoteRequest, 'approve']);

        $this->actingAs($admin)->post($url, [])->assertRedirect(route('admin.penawaran.detail', $quoteRequest));
        $response = $this->actingAs($admin)
            ->from(route('admin.penawaran.detail', $quoteRequest))
            ->post(route('admin.penawaran.decide', [$quoteRequest, 'reject']), ['reason' => 'Tidak sesuai.']);

        $response->assertSessionHasErrors('quote_request');
        $this->assertSame(
            'Hanya permintaan dengan usulan penyedia yang belum diputuskan dapat diproses.',
            session('errors')->first('quote_request'),
        );
        $this->assertDatabaseCount('quote_decisions', 1);
        $this->assertDatabaseCount('audit_logs', 1);
        $this->assertDatabaseHas('quote_requests', [
            'id' => $quoteRequest->id,
            'status' => QuoteRequestStatus::DisetujuiAdmin->value,
        ]);
    }

    public function test_non_admin_cannot_decide_a_quote_request(): void
    {
        $vendor = User::factory()->vendor()->create();
        [$quoteRequest] = $this->quoteWithProposal();

        $this->actingAs($vendor)
            ->post(route('admin.penawaran.decide', [$quoteRequest, 'approve']), [])
            ->assertForbidden();

        $this->assertDatabaseCount('quote_decisions', 0);
    }

    public function test_admin_queue_shows_real_requests_and_uses_allowlisted_filters(): void
    {
        $admin = User::factory()->admin()->create();
        [$waitingRequest] = $this->quoteWithProposal('Proposal Antrean Nyata');
        $pendingRequest = QuoteRequest::factory()->create([
            'status' => QuoteRequestStatus::Diajukan,
        ]);

        $this->actingAs($admin)->get(route('admin.penawaran'))
            ->assertOk()
            ->assertSee('Proposal Antrean Nyata')
            ->assertDontSee($pendingRequest->subject->name)
            ->assertDontSee('Graha Melati Ballroom');

        $this->actingAs($admin)->get(route('admin.penawaran', ['status' => 'semua']))
            ->assertOk()
            ->assertSee('Proposal Antrean Nyata')
            ->assertSee($pendingRequest->subject->name);

        $this->actingAs($admin)->get('/admin/permintaan-penawaran?status%5B%5D=ditolak')
            ->assertOk()
            ->assertSee('Proposal Antrean Nyata')
            ->assertDontSee('Graha Melati Ballroom');

        $this->assertDatabaseHas('quote_requests', [
            'id' => $waitingRequest->id,
            'status' => QuoteRequestStatus::DiusulkanPenyedia->value,
        ]);
    }

    /**
     * @return array{QuoteRequest, QuoteProposal}
     */
    private function quoteWithProposal(string $itemName = 'Item Penawaran Nyata'): array
    {
        $item = CatalogItem::factory()->create(['name' => $itemName]);
        $client = User::factory()->klien()->create();
        $quoteRequest = QuoteRequest::create([
            'client_id' => $client->id,
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
            'event_date' => '2030-02-01',
            'catalog_price_snapshot' => 8_250_000,
            'notes' => 'Kebutuhan nyata untuk permintaan.',
            'status' => QuoteRequestStatus::DiusulkanPenyedia,
        ]);
        $proposal = QuoteProposal::create([
            'quote_request_id' => $quoteRequest->id,
            'proposed_amount' => 7_900_000,
            'notes' => 'Harga proposal nyata.',
            'submitted_at' => now(),
        ]);

        return [$quoteRequest, $proposal];
    }
}
