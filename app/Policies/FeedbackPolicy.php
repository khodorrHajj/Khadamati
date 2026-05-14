<?php

namespace App\Policies;

use App\Models\Feedback;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FeedbackPolicy
{
    public function createForCitizen(User $user, ServiceRequest $serviceRequest): Response
    {
        if ($serviceRequest->user_id !== $user->id) {
            return Response::denyAsNotFound();
        }

        if ($serviceRequest->status !== ServiceRequest::STATUS_COMPLETED) {
            return Response::denyWithStatus(403);
        }

        return Response::allow();
    }

    public function updateMunicipality(User $user, Feedback $feedback): Response
    {
        if (
            !$feedback->serviceRequest ||
            !$feedback->serviceRequest->service ||
            $feedback->serviceRequest->service->government_office_id !== $user->government_office_id
        ) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }
}
