<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\RequestAssignedToMunicipalityNotification;
use App\Notifications\RequestEscalatedToAdminNotification;

class RequestWorkflowNotificationService
{
    public function notifyEscalatedToAdmin(ServiceRequest $serviceRequest, string $actorLabel, string $reason): void
    {
        User::query()
            ->where('is_active', true)
            ->where('status', '!=', 'inactive')
            ->whereHas('role', function ($query) {
                $query->where('role', 'admin');
            })
            ->get()
            ->each(function (User $admin) use ($serviceRequest, $actorLabel, $reason) {
                $admin->notify(new RequestEscalatedToAdminNotification($serviceRequest, $actorLabel, $reason));
            });
    }

    public function notifyAssignedToMunicipality(ServiceRequest $serviceRequest, User $assignee, string $actorLabel): void
    {
        $assignee->notify(new RequestAssignedToMunicipalityNotification($serviceRequest, $actorLabel));
    }
}
