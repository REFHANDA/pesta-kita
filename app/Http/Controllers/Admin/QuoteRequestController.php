<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuoteDecisionResult;
use App\Enums\QuoteRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DecideQuoteRequest;
use App\Models\AuditLog;
use App\Models\CatalogItem;
use App\Models\QuoteRequest;
use App\Models\VenueRoom;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * BE-4: Admin meninjau dan memutuskan proposal harga yang sudah diajukan
 * penyedia. Harga yang disetujui diambil dari proposal terakhir di server.
 */
class QuoteRequestController extends Controller
{
    /**
     * Daftar permintaan penawaran dengan filter status yang diizinkan.
     */
    public function index(Request $request): View
    {
        $statusFilter = $this->statusFilter($request);
        $query = QuoteRequest::query()
            ->with([
                'subject' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                    CatalogItem::class => ['vendorProfile'],
                    VenueRoom::class => ['venue.venueProfile'],
                ]),
                'event',
                'proposals' => fn ($query) => $query->orderByDesc('submitted_at')->orderByDesc('id'),
                'decision',
                'client',
            ])
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if ($statusFilter !== 'semua') {
            $query->where('status', $this->statusForFilter($statusFilter));
        }

        return view('admin.permintaan-penawaran', [
            'requests' => $query->paginate(20)->withQueryString(),
            'statusFilter' => $statusFilter,
            'waitingCount' => QuoteRequest::where('status', QuoteRequestStatus::DiusulkanPenyedia)->count(),
        ]);
    }

    /**
     * Render halaman kompatibilitas tanpa ID sebagai empty state.
     */
    public function empty(): View
    {
        return view('admin.permintaan-penawaran-detail', ['quoteRequest' => null]);
    }

    /**
     * Detail satu permintaan dan seluruh proposalnya untuk keputusan Admin.
     */
    public function show(QuoteRequest $quoteRequest): View
    {
        return view('admin.permintaan-penawaran-detail', [
            'quoteRequest' => $quoteRequest->load([
                'subject' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                    CatalogItem::class => ['vendorProfile'],
                    VenueRoom::class => ['venue.venueProfile'],
                ]),
                'event',
                'client',
                'proposals' => fn ($query) => $query->orderByDesc('submitted_at')->orderByDesc('id'),
                'decision.admin',
            ]),
        ]);
    }

    /**
     * Setujui atau tolak proposal terakhir; hanya satu keputusan final per request.
     */
    public function decide(DecideQuoteRequest $request, QuoteRequest $quoteRequest, string $action): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $quoteRequest, $action, $data): void {
            $lockedRequest = QuoteRequest::query()
                ->whereKey($quoteRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== QuoteRequestStatus::DiusulkanPenyedia) {
                throw ValidationException::withMessages([
                    'quote_request' => 'Hanya permintaan dengan usulan penyedia yang belum diputuskan dapat diproses.',
                ]);
            }

            $proposal = $lockedRequest->proposals()
                ->orderByDesc('submitted_at')
                ->orderByDesc('id')
                ->first();

            if ($proposal === null) {
                throw ValidationException::withMessages([
                    'quote_request' => 'Permintaan ini belum memiliki usulan harga yang dapat diputuskan.',
                ]);
            }

            $approved = $action === 'approve';
            $fromStatus = $lockedRequest->status;
            $toStatus = $approved
                ? QuoteRequestStatus::DisetujuiAdmin
                : QuoteRequestStatus::DitolakAdmin;
            $decision = $approved
                ? QuoteDecisionResult::Disetujui
                : QuoteDecisionResult::Ditolak;
            $reason = $data['reason'] ?? null;

            $lockedRequest->decision()->create([
                'admin_id' => $request->user()->id,
                'decision' => $decision,
                'approved_amount' => $approved ? $proposal->proposed_amount : null,
                'reason' => $reason,
                'decided_at' => now(),
            ]);
            $lockedRequest->update(['status' => $toStatus]);

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => $approved ? 'quote_approved' : 'quote_rejected',
                'subject_type' => $lockedRequest->getMorphClass(),
                'subject_id' => $lockedRequest->id,
                'from_status' => $fromStatus->value,
                'to_status' => $toStatus->value,
                'reason' => $reason,
                'ip' => $request->ip(),
            ]);
        });

        $message = $action === 'approve'
            ? 'Usulan disetujui. Harga proposal terakhir dicatat sebagai harga yang disetujui Admin.'
            : 'Usulan ditolak. Alasan tersimpan; permintaan ini telah selesai dan tidak dapat diputuskan ulang.';

        return redirect()->route('admin.penawaran.detail', $quoteRequest)
            ->with('status', $message);
    }

    /**
     * Normalize query-string filters without using user input as a status.
     */
    protected function statusFilter(Request $request): string
    {
        $allowed = ['menunggu', 'disetujui', 'ditolak', 'semua'];
        $status = $request->query('status', 'menunggu');

        return is_string($status) && in_array($status, $allowed, true) ? $status : 'menunggu';
    }

    /**
     * Map the visible Admin tab to its stored enum value.
     */
    protected function statusForFilter(string $statusFilter): string
    {
        return match ($statusFilter) {
            'disetujui' => QuoteRequestStatus::DisetujuiAdmin->value,
            'ditolak' => QuoteRequestStatus::DitolakAdmin->value,
            default => QuoteRequestStatus::DiusulkanPenyedia->value,
        };
    }
}
