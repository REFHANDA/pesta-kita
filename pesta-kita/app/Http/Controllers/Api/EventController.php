<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json($request->user()->events()->latest()->paginate(15));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->role === 'client', 403, 'Hanya klien yang dapat membuat acara.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'event_date' => ['nullable', 'date', 'after_or_equal:today'],
            'guest_count' => ['nullable', 'integer', 'min:1'],
            'theme' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
        ]);

        $event = $request->user()->events()->create([...$validated, 'status' => 'draft']);

        return response()->json($event, 201);
    }

    public function show(Request $request, Event $event): JsonResponse
    {
        $this->authorizeOwner($request, $event);

        return response()->json($event->load('orders.catalogItem', 'orders.invoice'));
    }

    public function update(Request $request, Event $event): JsonResponse
    {
        $this->authorizeOwner($request, $event);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'event_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:today'],
            'guest_count' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'theme' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'budget' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'status' => ['sometimes', Rule::in(['draft', 'planning', 'confirmed', 'completed', 'cancelled'])],
        ]);

        $event->update($validated);

        return response()->json($event->refresh());
    }

    public function destroy(Request $request, Event $event): JsonResponse
    {
        $this->authorizeOwner($request, $event);
        abort_if($event->orders()->exists(), 409, 'Acara yang sudah memiliki pesanan tidak dapat dihapus.');

        $event->delete();

        return response()->json(['message' => 'Acara berhasil dihapus.']);
    }

    private function authorizeOwner(Request $request, Event $event): void
    {
        abort_unless($event->user_id === $request->user()->id, 404);
    }
}
