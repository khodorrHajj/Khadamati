<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $notifications = Auth::user()
            ->notifications()
            ->latest()
            ->paginate(15);

        return view('Admin.notifications.index', compact('notifications'));
    }

    public function unreadCount(): JsonResponse
    {
        return response()->json([
            'unread_count' => Auth::user()->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(DatabaseNotification $notification): JsonResponse|RedirectResponse
    {
        abort_if($notification->notifiable_id !== Auth::id() || $notification->notifiable_type !== Auth::user()::class, 404);

        if (!$notification->read_at) {
            $notification->markAsRead();
        }

        if (request()->expectsJson()) {
            return response()->json([
                'read' => true,
                'redirect_to' => request('redirect_to'),
                'unread_count' => Auth::user()->fresh()->unreadNotifications()->count(),
            ]);
        }

        return redirect()->to(request('redirect_to', url()->previous()));
    }
}
