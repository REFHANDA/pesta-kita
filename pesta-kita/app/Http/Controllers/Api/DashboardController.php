<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'client') {
            $orders = $user->clientOrders();

            return response()->json([
                'events_count' => $user->events()->count(),
                'orders_count' => (clone $orders)->count(),
                'pending_orders_count' => (clone $orders)->where('status', 'pending')->count(),
                'unread_notifications_count' => $user->unreadNotifications()->count(),
                'recent_events' => $user->events()->latest()->limit(5)->get(),
                'recent_orders' => (clone $orders)->with(['catalogItem:id,name', 'invoice'])->latest()->limit(5)->get(),
            ]);
        }

        abort_unless(in_array($user->role, ['vendor', 'venue'], true), 403);
        $orders = $user->vendorOrders();

        return response()->json([
            'catalog_items_count' => $user->catalogItems()->count(),
            'orders_count' => (clone $orders)->count(),
            'pending_orders_count' => (clone $orders)->where('status', 'pending')->count(),
            'unread_notifications_count' => $user->unreadNotifications()->count(),
            'recent_orders' => (clone $orders)->with(['catalogItem:id,name', 'client:id,name', 'invoice'])->latest()->limit(5)->get(),
        ]);
    }
}
