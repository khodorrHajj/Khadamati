<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RequestAssignedToMunicipalityNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ServiceRequest $serviceRequest,
        private readonly string $actorLabel
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $request = $this->serviceRequest->loadMissing(['service.governmentOffice', 'user']);

        return [
            'kind' => 'request_assigned_to_municipality',
            'request_id' => $request->id,
            'tracking_code' => $request->tracking_code,
            'title' => 'Request assigned to you',
            'message' => sprintf(
                '%s returned %s to municipality follow-up and assigned it to you.',
                $this->actorLabel,
                $request->tracking_code ?? ('request #' . $request->id)
            ),
            'action_url' => route('municipality.requests.show', $request),
        ];
    }
}
