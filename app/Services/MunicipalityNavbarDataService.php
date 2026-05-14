<?php

namespace App\Services;

use App\Models\RequestMessage;
use App\Models\User;

class MunicipalityNavbarDataService
{
    public function forUser(?User $user): array
    {
        if (!$user) {
            return [
                'municipalityUnreadCount' => 0,
                'municipalityUnreadMessageCount' => 0,
                'municipalityUnreadNotifications' => collect(),
            ];
        }

        $officeId = $user->government_office_id;

        return [
            'municipalityUnreadCount' => $user->unreadNotifications()->count(),
            'municipalityUnreadMessageCount' => $officeId
                ? RequestMessage::query()
                    ->forMunicipalityOffice($officeId)
                    ->fromCitizens()
                    ->unread()
                    ->count()
                : 0,
            'municipalityUnreadNotifications' => $user->unreadNotifications()
                ->latest()
                ->take(5)
                ->get(),
        ];
    }
}
