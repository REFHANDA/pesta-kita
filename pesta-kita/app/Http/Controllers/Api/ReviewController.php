<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Order;
use App\Models\Review;
use App\Notifications\ReviewReceived;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(CatalogItem $catalogItem): JsonResponse
    {
        $reviews = Review::query()
            ->whereHas('order', fn ($query) => $query->where('catalog_item_id', $catalogItem->id))
            ->with('client:id,name')
            ->latest()
            ->paginate(15);

        return response()->json($reviews);
    }

    public function store(Request $request, Order $order): JsonResponse
    {
        abort_unless($request->user()->role === 'client' && $order->client_id === $request->user()->id, 404);
        abort_unless($order->status === 'completed', 422, 'Pesanan harus selesai sebelum dapat dievaluasi.');
        abort_if($order->review()->exists(), 409, 'Pesanan ini sudah memiliki evaluasi.');

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:5000'],
            'suggestion' => ['nullable', 'string', 'max:5000'],
        ]);

        $review = $order->review()->create([
            ...$validated,
            'client_id' => $order->client_id,
            'vendor_id' => $order->vendor_id,
        ]);
        $order->vendor->notify(new ReviewReceived($review));

        return response()->json($review, 201);
    }
}
