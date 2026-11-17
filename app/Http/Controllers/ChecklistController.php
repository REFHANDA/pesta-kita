<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventChecklistItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChecklistController extends Controller
{
    /**
     * Show and manage an event checklist for its client.
     */
    public function clientIndex(Request $request, Event $event): View
    {
        $this->authorizeClient($request, $event);

        return $this->view($event, 'klien', $event->closed_at === null);
    }

    /**
     * Add an item to an event checklist.
     */
    public function store(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeClient($request, $event);
        abort_if($event->closed_at !== null, 403);
        $data = $request->validate(['title' => ['required', 'string', 'max:255']]);

        $event->checklistItems()->create($data + ['created_by' => $request->user()->id]);

        return redirect()->route('klien.checklist', $event)->with('status', 'Item checklist ditambahkan.');
    }

    /**
     * Update a checklist item owned by the client's event.
     */
    public function update(Request $request, Event $event, EventChecklistItem $item): RedirectResponse
    {
        $this->authorizeClient($request, $event);
        abort_if($event->closed_at !== null, 403);
        abort_unless($item->event_id === $event->id, 404);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'is_completed' => ['required', 'boolean'],
        ]);
        $item->update($data);

        return redirect()->route('klien.checklist', $event)->with('status', 'Checklist diperbarui.');
    }

    /**
     * Remove an item from the client's event checklist.
     */
    public function destroy(Request $request, Event $event, EventChecklistItem $item): RedirectResponse
    {
        $this->authorizeClient($request, $event);
        abort_if($event->closed_at !== null, 403);
        abort_unless($item->event_id === $event->id, 404);
        $item->delete();

        return redirect()->route('klien.checklist', $event)->with('status', 'Item checklist dihapus.');
    }

    /**
     * Show a read-only checklist to a provider assigned to the event.
     */
    public function providerIndex(Request $request, Event $event): View
    {
        $profile = $request->user()->providerProfile();
        abort_unless($profile !== null && $event->orderItems()
            ->where('provider_type', $profile->getMorphClass())
            ->where('provider_id', $profile->id)
            ->exists(), 404);

        return $this->view($event, $request->user()->role->value, false);
    }

    /**
     * Show a read-only checklist to Admin.
     */
    public function adminIndex(Event $event): View
    {
        return $this->view($event, 'admin', false);
    }

    private function authorizeClient(Request $request, Event $event): void
    {
        abort_unless($event->client_id === $request->user()->id, 404);
    }

    private function view(Event $event, string $role, bool $canManage): View
    {
        return view('portal.checklist', [
            'event' => $event->load('checklistItems'),
            'role' => $role,
            'canManage' => $canManage,
        ]);
    }
}
