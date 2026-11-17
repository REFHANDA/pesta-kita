<?php

namespace App\Http\Controllers\Klien;

use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreEventReviewRequest;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventReview;
use App\Models\OrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventReviewController extends Controller
{
    /**
     * Submit a provider review after a paid event has been completed.
     */
    public function store(
        StoreEventReviewRequest $request,
        Event $event,
        OrderItem $orderItem,
    ): RedirectResponse {
        abort_unless($event->client_id === $request->user()->id, 404);
        abort_unless($orderItem->event_id === $event->id, 404);

        DB::transaction(function () use ($request, $event, $orderItem): void {
            $lockedEvent = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            $lockedItem = OrderItem::query()->whereKey($orderItem->id)->lockForUpdate()->firstOrFail();

            if (! in_array($lockedEvent->status, [EventStatus::Selesai, EventStatus::Dinilai], true)
                || ! $lockedEvent->invoices()->where('status', 'lunas')->exists()) {
                throw ValidationException::withMessages([
                    'event' => 'Ulasan hanya dapat diberikan setelah acara selesai dan tagihan lunas.',
                ]);
            }

            if ($lockedItem->event_id !== $lockedEvent->id || $lockedItem->status !== OrderItemStatus::Mengikat) {
                throw ValidationException::withMessages([
                    'order_item' => 'Ulasan hanya berlaku untuk item penyedia yang mengikat.',
                ]);
            }

            if ($lockedItem->review()->exists()) {
                throw ValidationException::withMessages([
                    'order_item' => 'Ulasan untuk item ini sudah dikirim.',
                ]);
            }

            $review = EventReview::query()->create($request->validated() + [
                'event_id' => $lockedEvent->id,
                'order_item_id' => $lockedItem->id,
                'client_id' => $request->user()->id,
            ]);

            $allReviewed = $lockedEvent->orderItems()
                ->where('status', OrderItemStatus::Mengikat->value)
                ->whereDoesntHave('review')
                ->doesntExist();
            if ($allReviewed) {
                $lockedEvent->update(['status' => EventStatus::Dinilai]);
            }

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'provider_review_submitted',
                'subject_type' => $review->getMorphClass(),
                'subject_id' => $review->id,
                'from_status' => null,
                'to_status' => 'submitted',
                'reason' => null,
                'ip' => $request->ip(),
            ]);
        });

        return redirect()->route('klien.acara.detail', $event)
            ->with('status', 'Ulasan Anda tersimpan secara privat.');
    }
}
