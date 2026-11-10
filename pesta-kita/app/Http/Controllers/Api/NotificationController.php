<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'notifications' => $request->user()->notifications()->latest()->paginate(20),
        ]);
    }

    public function markAsRead(Request $request, string $notification): JsonResponse
    {
        $record = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();

        return response()->json(['message' => 'Notifikasi ditandai telah dibaca.']);
    }
}
