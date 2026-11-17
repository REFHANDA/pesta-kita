<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalNotificationController extends Controller
{
    /**
     * Show the signed-in user's notification inbox.
     */
    public function index(Request $request): View
    {
        return view('portal.notifications', [
            'role' => $request->user()->role->value,
            'notifications' => $request->user()->notifications()->latest()->paginate(20),
            'unreadCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark one of the signed-in user's notifications as read.
     */
    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->findOrFail($notification)->markAsRead();

        return redirect()->route($request->user()->role->value.'.notifikasi');
    }

    /**
     * Mark all of the signed-in user's notifications as read.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return redirect()->route($request->user()->role->value.'.notifikasi')
            ->with('status', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
