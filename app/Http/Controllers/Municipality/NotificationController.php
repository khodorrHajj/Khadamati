<?php

namespace App\Http\Controllers\Municipality;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function unreadCount(): JsonResponse
    {
        return response()->json([
            'unread_count' => Auth::user()->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(DatabaseNotification $notification): RedirectResponse
    {
        abort_if($notification->notifiable_id !== Auth::id() || $notification->notifiable_type !== Auth::user()::class, 404);

        if (!$notification->read_at) {
            $notification->markAsRead();
        }

        return redirect()->back();
    }
}
