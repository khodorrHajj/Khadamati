<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CitizenRequestUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ServiceRequest $serviceRequest,
        private readonly ?string $previousStatus,
        private readonly bool $officialResponseUploaded,
        private readonly string $actorLabel,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        $request = $this->serviceRequest->loadMissing(['service.governmentOffice', 'user']);
        $statusChanged = $this->previousStatus !== $request->status;
        [$title, $message] = $this->summary($request, $statusChanged);

        return [
            'kind' => 'citizen_request_updated',
            'request_id' => $request->id,
            'tracking_code' => $request->tracking_code,
            'service_name' => $request->service?->name,
            'office_name' => $request->service?->governmentOffice?->name,
            'status' => $request->status,
            'previous_status' => $this->previousStatus,
            'official_response_uploaded' => $this->officialResponseUploaded,
            'title' => $title,
            'message' => $message,
            'action_url' => route('citizen.requests.show', $request),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->serviceRequest->loadMissing(['service.governmentOffice', 'user']);
        $statusChanged = $this->previousStatus !== $request->status;
        [$title, $message] = $this->summary($request, $statusChanged);

        $mail = (new MailMessage)
            ->subject($title . ' - ' . ($request->tracking_code ?? 'Request'))
            ->greeting('Hello ' . ($notifiable->name ?? 'there') . ',')
            ->line($message)
            ->line('Tracking code: ' . ($request->tracking_code ?? '-'));

        if ($statusChanged) {
            $mail->line('Current status: ' . $request->status);
        }

        if ($this->officialResponseUploaded) {
            $mail->line('A new official response document is now available in your request details.');
        }

        return $mail
            ->action('Open My Request', route('citizen.requests.show', $request))
            ->line('Thank you for using the E-Services platform.');
    }

    private function summary(ServiceRequest $request, bool $statusChanged): array
    {
        $serviceName = $request->service?->name ?? 'your service request';
        $officeName = $request->service?->governmentOffice?->name ?? $this->actorLabel;

        if ($statusChanged && $this->officialResponseUploaded) {
            return [
                'Request updated with official response',
                sprintf(
                    'Your request for %s is now %s, and %s uploaded an official response document.',
                    $serviceName,
                    $request->status,
                    $officeName
                ),
            ];
        }

        if ($statusChanged) {
            return [
                'Request status updated',
                sprintf(
                    'Your request for %s was updated to %s by %s.',
                    $serviceName,
                    $request->status,
                    $officeName
                ),
            ];
        }

        return [
            'Official response available',
            sprintf(
                '%s uploaded an official response document for your request for %s.',
                $officeName,
                $serviceName
            ),
        ];
    }
}
