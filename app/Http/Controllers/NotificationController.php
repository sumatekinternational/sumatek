<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/** In-app notification inbox for the authenticated user (§6.7). */
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'notifications' => $request->user()->notifications()->paginate(20),
        ]);
    }

    public function markRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => 'ok']);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'ok']);
    }
}
