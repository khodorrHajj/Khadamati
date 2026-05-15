<?php

namespace App\Services;

use App\Models\RequestMessage;
use App\Models\User;

class CitizenNavbarDataService
{
    public function forUser(?User $user): array
    {
        if (!$user) {
            return [
                'citizenUnreadCount' => 0,
                'citizenUnreadMessageCount' => 0,
                'citizenUnreadNotifications' => collect(),
            ];
        }

        return [
            'citizenUnreadCount' => $user->unreadNotifications()->count(),
            'citizenUnreadMessageCount' => RequestMessage::query()
                ->unread()
                ->where('sender_id', '!=', $user->id)
                ->whereHas('serviceRequest', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->count(),
            'citizenUnreadNotifications' => $user->unreadNotifications()
                ->latest()
                ->take(5)
                ->get(),
        ];
    }
}
