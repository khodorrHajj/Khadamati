<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CitizenRequestSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ServiceRequest $serviceRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->serviceRequest->loadMissing(['service.governmentOffice']);
        $serviceName = $request->service?->name ?? 'your requested service';
        $officeName = $request->service?->governmentOffice?->name ?? 'the assigned office';

        return (new MailMessage)
            ->subject('Request submitted - ' . ($request->tracking_code ?? 'Request'))
            ->greeting('Hello ' . ($notifiable->name ?? 'there') . ',')
            ->line("Your request for {$serviceName} was submitted successfully.")
            ->line("Assigned office: {$officeName}")
            ->line('Tracking code: ' . ($request->tracking_code ?? '-'))
            ->action('Open My Request', route('citizen.requests.show', $request))
            ->line('You can also track this request publicly using your tracking code whenever needed.');
    }
}
