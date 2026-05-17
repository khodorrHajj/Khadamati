<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CitizenAppointmentUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Appointment $appointment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $appointment = $this->appointment->loadMissing(['governmentOffice', 'serviceRequest.service', 'timeSlot']);
        $serviceName = $appointment->serviceRequest?->service?->name ?? 'service';
        $officeName = $appointment->governmentOffice?->name ?? 'government office';
        $slotText = $appointment->timeSlot?->starts_at
            ? $appointment->timeSlot->starts_at->format('Y-m-d H:i')
            : 'to be confirmed';

        return [
            'kind' => 'citizen_appointment_updated',
            'request_id' => $appointment->service_request_id,
            'appointment_id' => $appointment->id,
            'status' => $appointment->status,
            'title' => 'Appointment updated',
            'message' => sprintf(
                'Your appointment for %s at %s was marked %s. Scheduled time: %s.',
                $serviceName,
                $officeName,
                strtolower($appointment->status),
                $slotText
            ),
            'action_url' => route('citizen.requests.show', $appointment->service_request_id) . '#appointments',
        ];
    }
}
