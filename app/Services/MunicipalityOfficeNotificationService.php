<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\User;

class MunicipalityOfficeNotificationService
{
    public function notifyForServiceRequest(ServiceRequest $serviceRequest, object $notification): void
    {
        $officeId = $serviceRequest->service?->government_office_id;

        if (!$officeId) {
            return;
        }

        User::where('government_office_id', $officeId)
            ->where('is_active', true)
            ->where('status', '!=', 'inactive')
            ->whereHas('role', function ($query) {
                $query->where('role', 'municipality');
            })
            ->get()
            ->each(function (User $user) use ($notification) {
                $user->notify(clone $notification);
            });
    }
}
