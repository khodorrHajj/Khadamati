<?php

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RequestMessagePolicy
{
    public function createAsCitizen(User $user, ServiceRequest $serviceRequest): Response
    {
        if ($serviceRequest->user_id !== $user->id) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }

    public function createAsMunicipality(User $user, ServiceRequest $serviceRequest): Response
    {
        if (!$serviceRequest->service || $serviceRequest->service->government_office_id !== $user->government_office_id) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }
}
