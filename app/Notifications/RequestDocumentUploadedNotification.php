<?php

namespace App\Notifications;

use App\Models\RequestDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RequestDocumentUploadedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly RequestDocument $document)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $document = $this->document->loadMissing(['serviceRequest.service', 'serviceRequest.user']);
        $request = $document->serviceRequest;

        return [
            'kind' => 'request_document_uploaded',
            'request_id' => $request?->id,
            'tracking_code' => $request?->tracking_code,
            'service_name' => $request?->service?->name,
            'citizen_name' => $request?->user?->name,
            'document_name' => $document->original_name,
            'title' => 'New request document uploaded',
            'message' => sprintf(
                '%s uploaded %s for request #%s.',
                $request?->user?->name ?? 'A citizen',
                $document->original_name ?? 'a document',
                $request?->id ?? '-'
            ),
        ];
    }
}
