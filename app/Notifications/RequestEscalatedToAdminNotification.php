<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RequestEscalatedToAdminNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ServiceRequest $serviceRequest,
        private readonly string $actorLabel,
        private readonly string $reason
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
            'kind' => 'request_escalated_to_admin',
            'request_id' => $request->id,
            'tracking_code' => $request->tracking_code,
            'title' => 'Request escalated to admin',
            'message' => sprintf(
                '%s escalated %s for admin review. Reason: %s',
                $this->actorLabel,
                $request->tracking_code ?? ('request #' . $request->id),
                $this->reason
            ),
            'action_url' => route('admin.requests.show', $request),
        ];
    }
}
