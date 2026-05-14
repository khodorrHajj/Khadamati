<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewServiceRequestNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ServiceRequest $serviceRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $request = $this->serviceRequest->loadMissing(['service.governmentOffice', 'user']);

        return [
            'kind' => 'new_service_request',
            'request_id' => $request->id,
            'tracking_code' => $request->tracking_code,
            'service_name' => $request->service?->name,
            'office_name' => $request->service?->governmentOffice?->name,
            'citizen_name' => $request->user?->name,
            'title' => 'New service request',
            'message' => sprintf(
                '%s submitted a request for %s.',
                $request->user?->name ?? 'A citizen',
                $request->service?->name ?? 'a service'
            ),
        ];
    }
}
