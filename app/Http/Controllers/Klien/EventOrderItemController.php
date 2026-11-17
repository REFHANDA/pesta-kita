<?php

namespace App\Http\Controllers\Klien;

use App\Enums\AvailabilityStatus;
use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Enums\PricingMode;
use App\Enums\QuoteDecisionResult;
use App\Enums\QuoteRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreOrderItemRequest;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\QuoteRequest;
use App\Models\VenueRoom;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventOrderItemController extends Controller
{
    /**
     * Add an eligible fixed-price item to an owned draft.
     */
    public function store(StoreOrderItemRequest $request, Event $event): RedirectResponse
    {
        $this->ensureOwnedDraft($request, $event);
        [$subjectType, $subjectId] = explode(':', $request->validated('subject'), 2);

        $subject = match ($subjectType) {
            'catalog_item' => CatalogItem::publikUntukKlien()
                ->where('pricing_mode', PricingMode::Tetap)
                ->with('vendorProfile')
                ->find($subjectId),
            'venue_room' => VenueRoom::query()
                ->whereHas('venue', fn ($query) => $query->publikUntukKlien())
                ->with('venue')
                ->find($subjectId),
        };

        if ($subject === null) {
            throw ValidationException::withMessages([
                'subject' => 'Item sudah tidak tersedia atau memerlukan permintaan penawaran.',
            ]);
        }

        $providerType = $subject instanceof CatalogItem ? 'vendor_profile' : 'venue_profile';
        $providerId = $subject instanceof CatalogItem
            ? $subject->vendor_profile_id
            : $subject->venue->venue_profile_id;
        $price = $subject instanceof CatalogItem
            ? $subject->catalog_price
            : $subject->venue->rental_price;
        $date = $event->event_date->toDateString();

        $unavailable = $subject->availabilityDays()
            ->whereDate('date', $date)
            ->where('status', AvailabilityStatus::TidakTersedia->value)
            ->exists();
        $alreadyBooked = OrderItem::query()
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subject->id)
            ->whereHas('event', fn ($query) => $query
                ->whereDate('event_date', $date)
                ->where('status', EventStatus::Terkonfirmasi->value))
            ->exists();

        if ($unavailable || $alreadyBooked) {
            throw ValidationException::withMessages([
                'subject' => 'Item tidak tersedia pada tanggal acara.',
            ]);
        }

        DB::transaction(function () use ($event, $subject, $subjectType, $providerType, $providerId, $price): void {
            $lockedEvent = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            if ($lockedEvent->status !== EventStatus::Draft) {
                throw ValidationException::withMessages([
                    'event' => 'Item hanya dapat ditambahkan ke acara draft.',
                ]);
            }

            if ($lockedEvent->orderItems()
                ->where('subject_type', $subjectType)
                ->where('subject_id', $subject->id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'subject' => 'Item ini sudah ada di acara.',
                ]);
            }

            $lockedEvent->orderItems()->create([
                'provider_type' => $providerType,
                'provider_id' => $providerId,
                'subject_type' => $subjectType,
                'subject_id' => $subject->id,
                'catalog_price_snapshot' => $price,
                'status' => OrderItemStatus::MenungguAdmin,
            ]);
        });

        return redirect()->route('klien.acara.detail', $event)
            ->with('status', 'Item ditambahkan ke draft dengan harga yang dibaca dari katalog.');
    }

    /**
     * Remove an item from an owned draft.
     */
    public function destroy(Request $request, Event $event, OrderItem $orderItem): RedirectResponse
    {
        $this->ensureOwnedDraft($request, $event);
        abort_unless($orderItem->event_id === $event->id, 404);

        $orderItem->delete();

        return redirect()->route('klien.acara.detail', $event)
            ->with('status', 'Item dihapus dari draft acara.');
    }

    /**
     * Submit a draft after importing approved linked quotes as order items.
     */
    public function submit(Request $request, Event $event): RedirectResponse
    {
        $this->ensureOwnedDraft($request, $event);

        DB::transaction(function () use ($event): void {
            $lockedEvent = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            if ($lockedEvent->status !== EventStatus::Draft) {
                throw ValidationException::withMessages([
                    'event' => 'Hanya acara draft yang dapat diajukan.',
                ]);
            }

            $pendingQuotes = $lockedEvent->quoteRequests()
                ->whereIn('status', [
                    QuoteRequestStatus::Diajukan->value,
                    QuoteRequestStatus::DiusulkanPenyedia->value,
                ])
                ->exists();

            if ($pendingQuotes) {
                throw ValidationException::withMessages([
                    'event' => 'Selesaikan atau lepaskan permintaan penawaran yang masih menunggu sebelum mengajukan acara.',
                ]);
            }

            $approvedQuotes = $lockedEvent->quoteRequests()
                ->where('status', QuoteRequestStatus::DisetujuiAdmin->value)
                ->with([
                    'decision',
                    'subject' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                        CatalogItem::class => [],
                        VenueRoom::class => ['venue'],
                    ]),
                ])
                ->lockForUpdate()
                ->get();

            foreach ($approvedQuotes as $quoteRequest) {
                if ($lockedEvent->orderItems()->where('quote_request_id', $quoteRequest->id)->exists()) {
                    continue;
                }

                $this->createOrderItemFromApprovedQuote($lockedEvent, $quoteRequest);
            }

            if (! $lockedEvent->orderItems()->exists()) {
                throw ValidationException::withMessages([
                    'event' => 'Tambahkan minimal satu item sebelum mengajukan acara.',
                ]);
            }

            $lockedEvent->update(['status' => EventStatus::Diajukan]);
        });

        return redirect()->route('klien.acara.detail', $event)
            ->with('status', 'Acara diajukan. Pesanan menunggu review Admin per item.');
    }

    /**
     * Ensure the authenticated client owns an editable draft.
     */
    private function ensureOwnedDraft(Request $request, Event $event): void
    {
        abort_unless($event->client_id === $request->user()->id, 404);
        abort_unless($event->status === EventStatus::Draft, 404);
    }

    /**
     * Create an order item using the approved quote amount stored by Admin.
     */
    private function createOrderItemFromApprovedQuote(Event $event, QuoteRequest $quoteRequest): void
    {
        $subject = $quoteRequest->subject;
        $decision = $quoteRequest->decision;

        if ($decision?->decision !== QuoteDecisionResult::Disetujui || $decision->approved_amount === null) {
            throw ValidationException::withMessages([
                'event' => 'Permintaan penawaran belum memiliki harga yang disetujui Admin.',
            ]);
        }

        if ($subject instanceof CatalogItem) {
            $providerType = 'vendor_profile';
            $providerId = $subject->vendor_profile_id;
        } elseif ($subject instanceof VenueRoom && $subject->venue !== null) {
            $providerType = 'venue_profile';
            $providerId = $subject->venue->venue_profile_id;
        } else {
            throw ValidationException::withMessages([
                'event' => 'Subjek permintaan penawaran tidak lagi tersedia.',
            ]);
        }

        $event->orderItems()->create([
            'provider_type' => $providerType,
            'provider_id' => $providerId,
            'subject_type' => $quoteRequest->subject_type,
            'subject_id' => $quoteRequest->subject_id,
            'quote_request_id' => $quoteRequest->id,
            'catalog_price_snapshot' => $quoteRequest->catalog_price_snapshot,
            'proposed_price_snapshot' => $decision->approved_amount,
            'status' => OrderItemStatus::MenungguAdmin,
        ]);
    }
}
