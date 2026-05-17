<?php

namespace App\Broadcasting;

use App\Models\ServiceRequest;
use App\Models\User;

class RequestChatChannel
{
    public function join(User $user, ServiceRequest $serviceRequest): bool
    {
        if ($user->hasRole('citizen')) {
            return (int) $serviceRequest->user_id === (int) $user->id;
        }

        if ($user->hasRole('municipality')) {
            $officeId = $user->government_office_id ?? $user->governmentOffice?->id;

            return (int) $serviceRequest->service?->government_office_id === (int) $officeId;
        }

        return false;
    }
}
