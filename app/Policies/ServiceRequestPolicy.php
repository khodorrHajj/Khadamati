<?php

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ServiceRequestPolicy
{
    public function viewCitizen(User $user, ServiceRequest $serviceRequest): Response
    {
        return $this->ownsRequest($user, $serviceRequest);
    }

    public function uploadDocument(User $user, ServiceRequest $serviceRequest): Response
    {
        return $this->ownsRequest($user, $serviceRequest);
    }

    public function viewMunicipality(User $user, ServiceRequest $serviceRequest): Response
    {
        return $this->belongsToUsersOffice($user, $serviceRequest);
    }

    public function updateMunicipality(User $user, ServiceRequest $serviceRequest): Response
    {
        return $this->belongsToUsersOffice($user, $serviceRequest);
    }

    private function ownsRequest(User $user, ServiceRequest $serviceRequest): Response
    {
        if ($serviceRequest->user_id !== $user->id) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }

    private function belongsToUsersOffice(User $user, ServiceRequest $serviceRequest): Response
    {
        if (!$serviceRequest->service || $serviceRequest->service->government_office_id !== $user->government_office_id) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }
}
