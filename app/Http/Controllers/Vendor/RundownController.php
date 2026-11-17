<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StoreRundownItemRequest;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\RundownItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RundownController extends Controller
{
    /**
     * List confirmed events containing this Vendor's locked order items.
     */
    public function index(Request $request): View
    {
        $profile = $request->user()->vendorProfile;
        abort_unless($profile !== null, 404);

        return view('vendor.rundown-index', [
            'events' => Event::query()
                ->whereIn('status', [
                    EventStatus::Terkonfirmasi->value,
                    EventStatus::Selesai->value,
                    EventStatus::Dinilai->value,
                ])
                ->whereHas('orderItems', fn ($query) => $query
                    ->where('provider_type', 'vendor_profile')
                    ->where('provider_id', $profile->id)
                    ->where('status', OrderItemStatus::Mengikat->value))
                ->with(['orderItems' => fn ($query) => $query
                    ->where('provider_type', 'vendor_profile')
                    ->where('provider_id', $profile->id)
                    ->where('status', OrderItemStatus::Mengikat->value)
                    ->with(['subject', 'rundownItems' => fn ($rundownQuery) => $rundownQuery
                        ->where('created_by', $request->user()->id)
                        ->orderBy('starts_at')])])
                ->orderBy('event_date')
                ->paginate(20),
        ]);
    }

    /**
     * Show only the segments and order items belonging to this Vendor.
     */
    public function show(Request $request, Event $event): View
    {
        $profile = $request->user()->vendorProfile;
        abort_unless($profile !== null && $event->orderItems()
            ->where('provider_type', 'vendor_profile')
            ->where('provider_id', $profile->id)
            ->where('status', OrderItemStatus::Mengikat->value)
            ->exists(), 404);

        return view('vendor.rundown', [
            'event' => $event,
            'orderItems' => $event->orderItems()
                ->where('provider_type', 'vendor_profile')
                ->where('provider_id', $profile->id)
                ->where('status', OrderItemStatus::Mengikat->value)
                ->with(['subject', 'rundownItems' => fn ($query) => $query
                    ->where('created_by', $request->user()->id)
                    ->orderBy('starts_at')])
                ->get(),
            'canEditRundown' => $event->status === EventStatus::Terkonfirmasi,
        ]);
    }

    /**
     * Add a segment for one of the Vendor's locked order items.
     */
    public function store(StoreRundownItemRequest $request, Event $event): RedirectResponse
    {
        $orderItem = $this->ownedLockedItem($request, $event, $request->validated('order_item_id'));
        $this->ensureConfirmed($event);

        $event->rundownItems()->create($request->safe()->except('order_item_id') + [
            'order_item_id' => $orderItem->id,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('vendor.rundown', $event)->with('status', 'Segmen layanan ditambahkan ke rundown.');
    }

    /**
     * Update a segment created by this Vendor for an owned order item.
     */
    public function update(
        StoreRundownItemRequest $request,
        Event $event,
        RundownItem $rundownItem,
    ): RedirectResponse {
        abort_unless($rundownItem->event_id === $event->id
            && $rundownItem->created_by === $request->user()->id, 404);
        $this->ownedLockedItem($request, $event, $request->validated('order_item_id'));
        $this->ensureConfirmed($event);

        $rundownItem->update($request->safe()->except('order_item_id'));

        return redirect()->route('vendor.rundown', $event)->with('status', 'Segmen layanan diperbarui.');
    }

    /**
     * Remove a segment created by this Vendor.
     */
    public function destroy(Request $request, Event $event, RundownItem $rundownItem): RedirectResponse
    {
        abort_unless($rundownItem->event_id === $event->id
            && $rundownItem->created_by === $request->user()->id, 404);
        $this->ownedLockedItem($request, $event, $rundownItem->order_item_id);
        $this->ensureConfirmed($event);

        $rundownItem->delete();

        return redirect()->route('vendor.rundown', $event)->with('status', 'Segmen layanan dihapus.');
    }

    /**
     * Confirm the order item is owned by this Vendor and locked.
     */
    private function ownedLockedItem(Request $request, Event $event, int|string|null $orderItemId): OrderItem
    {
        $profile = $request->user()->vendorProfile;
        $orderItem = OrderItem::query()->whereKey($orderItemId)
            ->where('event_id', $event->id)
            ->where('provider_type', 'vendor_profile')
            ->where('provider_id', $profile?->id)
            ->where('status', OrderItemStatus::Mengikat->value)
            ->first();

        abort_unless($profile !== null && $orderItem !== null, 404);

        return $orderItem;
    }

    /**
     * Keep editing disabled once an event has ended.
     */
    private function ensureConfirmed(Event $event): void
    {
        if ($event->status !== EventStatus::Terkonfirmasi) {
            throw ValidationException::withMessages([
                'event' => 'Rundown hanya dapat diubah sebelum acara selesai.',
            ]);
        }
    }
}
