<?php

namespace App\Http\Controllers\Klien;

use App\Enums\EventStatus;
use App\Enums\PricingMode;
use App\Enums\QuoteRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreQuoteRequest;
use App\Models\CatalogItem;
use App\Models\AuditLog;
use App\Models\QuoteRequest;
use App\Models\VenueRoom;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * BE-4: pengajuan dan pembacaan permintaan penawaran milik Klien.
 */
class QuoteRequestController extends Controller
{
    /**
     * Peta tab status halaman ke enum QuoteRequestStatus.
     */
    protected const STATUS_FILTERS = [
        'menunggu' => [QuoteRequestStatus::Diajukan],
        'ditinjau' => [QuoteRequestStatus::DiusulkanPenyedia],
        'disetujui' => [QuoteRequestStatus::DisetujuiAdmin],
        'ditolak' => [QuoteRequestStatus::DitolakAdmin],
    ];

    /**
     * Daftar permintaan penawaran milik Klien login.
     */
    public function index(Request $request): View
    {
        $statuses = self::STATUS_FILTERS[$request->query('status')] ?? null;

        return view('klien.permintaan-penawaran', [
            'requests' => $request->user()->quoteRequests()
                ->when($statuses, fn ($q) => $q->whereIn('status', array_column($statuses, 'value')))
                ->with([
                    'subject' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                        CatalogItem::class => ['vendorProfile'],
                        VenueRoom::class => ['venue.venueProfile'],
                    ]),
                    'event',
                    'proposals' => fn ($query) => $query->orderByDesc('submitted_at')->orderByDesc('id'),
                    'decision',
                ])
                ->latest()
                ->get(),
            'catalogItems' => CatalogItem::publikUntukKlien()
                ->where('pricing_mode', PricingMode::PerluPenawaran)
                ->with('vendorProfile')
                ->orderBy('name')
                ->get(),
            'venueRooms' => VenueRoom::query()
                ->whereHas('venue', fn (Builder $query) => $query->publikUntukKlien())
                ->with('venue.venueProfile')
                ->orderBy('name')
                ->get(),
            'draftEvents' => $request->user()->events()
                ->where('status', EventStatus::Draft)
                ->orderBy('event_date')
                ->get(),
        ]);
    }

    /**
     * Simpan permintaan dengan harga katalog yang dibaca dari subjek publik.
     */
    public function store(StoreQuoteRequest $request): RedirectResponse
    {
        $data = $request->validated();
        [$subjectType, $subjectId] = explode(':', $data['subject'], 2);

        $subject = match ($subjectType) {
            'catalog_item' => CatalogItem::publikUntukKlien()
                ->where('pricing_mode', PricingMode::PerluPenawaran)
                ->find($subjectId),
            'venue_room' => VenueRoom::query()
                ->whereHas('venue', fn (Builder $query) => $query->publikUntukKlien())
                ->with('venue')
                ->find($subjectId),
            default => null,
        };

        if ($subject === null) {
            throw ValidationException::withMessages([
                'subject' => 'Layanan atau ruangan venue sudah tidak tersedia untuk permintaan penawaran.',
            ]);
        }

        $quoteRequest = DB::transaction(function () use ($request, $data, $subject, $subjectType): QuoteRequest {
            $catalogPrice = $subject instanceof CatalogItem
                ? $subject->catalog_price
                : $subject->venue->rental_price;

            $quoteRequest = QuoteRequest::create([
                'client_id' => $request->user()->id,
                'event_id' => $data['event_id'] ?? null,
                'subject_type' => $subjectType,
                'subject_id' => $subject->id,
                'event_date' => $data['event_date'],
                'guest_count' => $data['guest_count'] ?? null,
                'budget' => $data['budget'] ?? null,
                'notes' => $data['notes'] ?? null,
                'catalog_price_snapshot' => $catalogPrice,
                'status' => QuoteRequestStatus::Diajukan,
            ]);

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'quote_request_submitted',
                'subject_type' => $quoteRequest->getMorphClass(),
                'subject_id' => $quoteRequest->id,
                'from_status' => null,
                'to_status' => QuoteRequestStatus::Diajukan->value,
                'ip' => $request->ip(),
            ]);

            return $quoteRequest;
        });

        return redirect()->route('klien.penawaran.detail', $quoteRequest)
            ->with('status', 'Permintaan penawaran tersimpan dan diteruskan kepada penyedia melalui Pesta Kita.');
    }

    /**
     * Detail satu permintaan penawaran milik Klien login.
     */
    public function show(Request $request, QuoteRequest $quoteRequest): View
    {
        abort_unless($quoteRequest->client_id === $request->user()->id, 404);

        return view('klien.permintaan-penawaran-detail', [
            'quoteRequest' => $quoteRequest->load([
                'subject',
                'event',
                'proposals' => fn ($query) => $query->orderByDesc('submitted_at')->orderByDesc('id'),
                'decision',
            ]),
        ]);
    }
}
