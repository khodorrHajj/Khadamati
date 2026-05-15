<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\ServiceRequest;
use App\Notifications\CitizenRequestSubmittedNotification;
use App\Notifications\CitizenAppointmentUpdatedNotification;
use App\Notifications\CitizenRequestUpdatedNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

class CitizenNotificationService
{
    public function notifyRequestSubmitted(ServiceRequest $serviceRequest): void
    {
        $citizen = $serviceRequest->loadMissing(['user', 'service.governmentOffice'])->user;

        if (!$citizen) {
            return;
        }

        $this->sendSafely(
            $citizen,
            new CitizenRequestSubmittedNotification($serviceRequest),
            'Citizen request submission email failed.'
        );
    }

    public function notifyRequestUpdated(
        ServiceRequest $serviceRequest,
        ?string $previousStatus,
        bool $officialResponseUploaded,
        string $actorLabel
    ): void {
        $statusChanged = $previousStatus !== $serviceRequest->status;

        if (!$statusChanged && !$officialResponseUploaded) {
            return;
        }

        $citizen = $serviceRequest->loadMissing(['user', 'service.governmentOffice'])->user;

        if (!$citizen) {
            return;
        }

        $this->sendSafely(
            $citizen,
            new CitizenRequestUpdatedNotification(
                $serviceRequest,
                $previousStatus,
                $officialResponseUploaded,
                $actorLabel
            ),
            'Citizen request update notification failed.',
            [
                'service_request_id' => $serviceRequest->id,
            ]
        );
    }

    public function notifyAppointmentUpdated(Appointment $appointment): void
    {
        $citizen = $appointment->loadMissing('user')->user;

        if (!$citizen) {
            return;
        }

        $this->sendSafely(
            $citizen,
            new CitizenAppointmentUpdatedNotification($appointment),
            'Citizen appointment notification failed.',
            [
                'appointment_id' => $appointment->id,
            ]
        );
    }

    private function sendSafely(
        object $notifiable,
        object $notification,
        string $message,
        array $context = []
    ): void {
        try {
            $notifiable->notify($notification);
        } catch (Throwable $exception) {
            Log::warning($message, array_merge($context, [
                'user_id' => $notifiable->id ?? null,
                'error' => $exception->getMessage(),
            ]));
        }
    }
}
