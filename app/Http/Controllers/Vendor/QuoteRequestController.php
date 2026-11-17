<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\QuoteRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreQuoteProposalRequest;
use App\Models\CatalogItem;
use App\Models\QuoteProposal;
use App\Models\QuoteRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * BE-4: daftar, detail, dan usulan harga untuk permintaan katalog milik Vendor.
 */
class QuoteRequestController extends Controller
{
    /**
     * Peta tab status halaman ke enum QuoteRequestStatus.
     */
    protected const STATUS_FILTERS = [
        'baru' => [QuoteRequestStatus::Diajukan],
        'ditinjau' => [QuoteRequestStatus::DiusulkanPenyedia],
        'disetujui' => [QuoteRequestStatus::DisetujuiAdmin],
        'ditolak' => [QuoteRequestStatus::DitolakAdmin],
    ];

    /**
     * Daftar permintaan penawaran atas item katalog milik vendor login.
     */
    public function index(Request $request): View
    {
        $statuses = self::STATUS_FILTERS[$request->query('status')] ?? null;

        return view('vendor.permintaan-penawaran', [
            'requests' => $this->ownedRequests($request)
                ->when($statuses, fn (Builder $q) => $q->whereIn('status', array_column($statuses, 'value')))
                ->with([
                    'subject' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                        CatalogItem::class => ['vendorProfile'],
                    ]),
                ])
                ->latest()
                ->get(),
        ]);
    }

    /**
     * Detail satu permintaan penawaran atas item milik vendor login.
     */
    public function show(Request $request, QuoteRequest $quoteRequest): View
    {
        abort_unless(
            $this->ownedRequests($request)->whereKey($quoteRequest->id)->exists(),
            404,
        );

        return view('vendor.permintaan-penawaran-detail', [
            'quoteRequest' => $quoteRequest->load([
                'subject',
                'proposals' => fn ($query) => $query->orderByDesc('submitted_at')->orderByDesc('id'),
                'decision',
            ]),
        ]);
    }

    /**
     * Catat usulan Vendor untuk permintaan yang masih menunggu usulan.
     */
    public function store(StoreQuoteProposalRequest $request, QuoteRequest $quoteRequest): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $quoteRequest, $data): void {
            $ownedRequest = $this->ownedRequests($request)
                ->whereKey($quoteRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($ownedRequest->status !== QuoteRequestStatus::Diajukan) {
                throw ValidationException::withMessages([
                    'quote_request' => 'Permintaan ini tidak lagi menunggu usulan Vendor.',
                ]);
            }

            QuoteProposal::create([
                'quote_request_id' => $ownedRequest->id,
                'proposed_amount' => $data['proposed_amount'],
                'breakdown' => null,
                'notes' => $data['notes'] ?? null,
                'submitted_at' => now(),
            ]);

            $ownedRequest->update(['status' => QuoteRequestStatus::DiusulkanPenyedia]);
        });

        return redirect()->route('vendor.penawaran.detail', $quoteRequest)
            ->with('status', 'Usulan harga tersimpan dan diteruskan kepada Admin untuk ditinjau.');
    }

    /**
     * Query dasar permintaan penawaran dengan subject item katalog milik
     * profil vendor akun login (profil bisa belum ada -> collection kosong).
     */
    protected function ownedRequests(Request $request): Builder
    {
        $itemIds = $request->user()->vendorProfile?->catalogItems()->pluck('id') ?? collect();

        return QuoteRequest::query()
            ->where('subject_type', 'catalog_item')
            ->whereIn('subject_id', $itemIds)
            ->with([
                'subject',
                'proposals' => fn ($query) => $query->orderByDesc('submitted_at')->orderByDesc('id'),
                'decision',
            ]);
    }
}
