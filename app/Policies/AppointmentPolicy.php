<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AppointmentPolicy
{
    public function createForCitizen(User $user, ServiceRequest $serviceRequest): Response
    {
        if ($serviceRequest->user_id !== $user->id) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }

    public function updateMunicipality(User $user, Appointment $appointment): Response
    {
        if ($appointment->government_office_id !== $user->government_office_id) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }
}
