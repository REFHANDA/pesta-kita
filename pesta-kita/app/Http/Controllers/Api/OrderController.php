<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\Order;
use App\Notifications\OrderStatusChanged;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['client', 'vendor', 'venue'], true), 403);

        $orders = Order::query()
            ->where($user->role === 'client' ? 'client_id' : 'vendor_id', $user->id)
            ->with(['event:id,name,event_date', 'catalogItem:id,name,type,category', 'client:id,name', 'vendor:id,name', 'invoice'])
            ->latest()
            ->paginate(15);

        return response()->json($orders);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->role === 'client', 403, 'Hanya klien yang dapat membuat pesanan.');

        $validated = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'catalog_item_id' => ['required', 'integer', 'exists:catalog_items,id'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $event = Event::findOrFail($validated['event_id']);
        abort_unless($event->user_id === $request->user()->id, 404, 'Acara tidak ditemukan.');
        $quantity = $validated['quantity'] ?? 1;

        $order = DB::transaction(function () use ($request, $validated, $event, $quantity): Order {
            $item = CatalogItem::query()->lockForUpdate()->findOrFail($validated['catalog_item_id']);
            abort_unless($item->is_available, 422, 'Item katalog sedang tidak tersedia.');
            abort_if($item->user_id === $request->user()->id, 422, 'Anda tidak dapat memesan item milik sendiri.');

            [$whole, $fraction] = array_pad(explode('.', (string) $item->price, 2), 2, '0');
            $unitPriceInCents = ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
            $totalInCents = $unitPriceInCents * $quantity;
            abort_if($totalInCents > 999999999999, 422, 'Total pesanan melebihi batas nilai yang didukung.');
            $totalAmount = intdiv($totalInCents, 100).'.'.str_pad((string) ($totalInCents % 100), 2, '0', STR_PAD_LEFT);

            $order = Order::create([
                'event_id' => $event->id,
                'client_id' => $request->user()->id,
                'catalog_item_id' => $item->id,
                'vendor_id' => $item->user_id,
                'quantity' => $quantity,
                'unit_price' => $item->price,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'payment_status' => 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            $order->invoice()->create([
                'invoice_number' => 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(10)),
                'amount' => $order->total_amount,
                'status' => 'pending',
                'due_at' => now()->addDays(7),
            ]);

            return $order;
        });

        $order->vendor->notify(new OrderStatusChanged($order));

        return response()->json($order->load(['event', 'catalogItem', 'invoice']), 201);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorizeParticipant($request, $order);

        return response()->json($order->load([
            'event', 'catalogItem', 'client:id,name,email,phone', 'vendor:id,name,email,phone', 'invoice', 'review',
        ]));
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        $this->authorizeParticipant($request, $order);
        $validated = $request->validate([
            'status' => ['required', Rule::in(['confirmed', 'in_progress', 'completed', 'cancelled'])],
        ]);
        $nextStatus = $validated['status'];

        if ($user->id === $order->client_id) {
            abort_unless($nextStatus === 'cancelled' && $order->status === 'pending', 422,
                'Klien hanya dapat membatalkan pesanan yang masih menunggu konfirmasi.');
            abort_unless($order->payment_status === 'pending', 422,
                'Pesanan yang sudah dibayar tidak dapat dibatalkan melalui API ini.');
        } else {
            $transitions = [
                'pending' => ['confirmed'],
                'confirmed' => ['in_progress', 'cancelled'],
                'in_progress' => ['completed', 'cancelled'],
            ];
            abort_unless(in_array($nextStatus, $transitions[$order->status] ?? [], true), 422,
                'Perubahan status pesanan tidak valid.');
        }

        $order->update(['status' => $nextStatus]);
        if ($nextStatus === 'cancelled') {
            $order->invoice()->update(['status' => 'cancelled']);
        }

        $recipient = $user->id === $order->client_id ? $order->vendor : $order->client;
        $recipient->notify(new OrderStatusChanged($order));

        return response()->json($order->refresh()->load(['invoice', 'catalogItem', 'event']));
    }

    private function authorizeParticipant(Request $request, Order $order): void
    {
        abort_unless(in_array($request->user()->id, [$order->client_id, $order->vendor_id], true), 404);
    }
}
