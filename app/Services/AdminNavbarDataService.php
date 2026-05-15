<?php

namespace App\Services;

use App\Models\User;

class AdminNavbarDataService
{
    public function forUser(?User $user): array
    {
        if (!$user) {
            return [
                'adminUnreadCount' => 0,
                'adminUnreadNotifications' => collect(),
            ];
        }

        return [
            'adminUnreadCount' => $user->unreadNotifications()->count(),
            'adminUnreadNotifications' => $user->unreadNotifications()
                ->latest()
                ->take(5)
                ->get(),
        ];
    }
}
