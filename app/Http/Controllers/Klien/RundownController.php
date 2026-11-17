<?php

namespace App\Http\Controllers\Klien;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreRundownItemRequest;
use App\Http\Requests\Client\UpdateRundownItemRequest;
use App\Models\Event;
use App\Models\RundownItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RundownController extends Controller
{
    /**
     * Show event participants the rundown.
     */
    public function show(Request $request, Event $event): View
    {
        abort_unless($event->client_id === $request->user()->id, 404);

        return view('klien.rundown', [
            'event' => $event->load(['rundownItems.orderItem.subject', 'orderItems.subject', 'invoices']),
            'canEditRundown' => $event->status === EventStatus::Terkonfirmasi,
        ]);
    }

    /**
     * Add a client-managed segment.
     */
    public function store(StoreRundownItemRequest $request, Event $event): RedirectResponse
    {
        abort_unless($event->client_id === $request->user()->id, 404);
        $this->ensureConfirmed($event);

        $event->rundownItems()->create($request->validated() + [
            'created_by' => $request->user()->id,
            'order_item_id' => null,
        ]);

        return redirect()->route('klien.rundown', $event)->with('status', 'Segmen ditambahkan.');
    }

    /**
     * Update a segment in the client's event.
     */
    public function update(
        UpdateRundownItemRequest $request,
        Event $event,
        RundownItem $rundownItem,
    ): RedirectResponse {
        abort_unless($event->client_id === $request->user()->id, 404);
        abort_unless($rundownItem->event_id === $event->id, 404);
        $this->ensureConfirmed($event);

        $rundownItem->update($request->validated());

        return redirect()->route('klien.rundown', $event)->with('status', 'Segmen diperbarui.');
    }

    /**
     * Remove a segment from the client's event.
     */
    public function destroy(Request $request, Event $event, RundownItem $rundownItem): RedirectResponse
    {
        abort_unless($event->client_id === $request->user()->id, 404);
        abort_unless($rundownItem->event_id === $event->id, 404);
        $this->ensureConfirmed($event);

        $rundownItem->delete();

        return redirect()->route('klien.rundown', $event)->with('status', 'Segmen dihapus.');
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
