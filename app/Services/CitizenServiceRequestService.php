<?php

namespace App\Services;

use App\Models\RequestDocument;
use App\Models\Service;
use App\Models\ServiceRequest;

class CitizenServiceRequestService
{
    public function create(Service $service, int $userId, ?string $notes = null, array $documents = []): ServiceRequest
    {
        $serviceRequest = ServiceRequest::create([
            'user_id' => $userId,
            'service_id' => $service->id,
            'status' => ServiceRequest::STATUS_PENDING,
            'message' => $notes,
        ]);

        foreach ($documents as $file) {
            RequestDocument::create([
                'service_request_id' => $serviceRequest->id,
                'uploaded_by' => $userId,
                'document_path' => $file->store('request-documents', 'public'),
                'original_name' => $file->getClientOriginalName(),
            ]);
        }

        return $serviceRequest;
    }
}
