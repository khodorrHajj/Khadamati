<?php

namespace App\Services;

use App\Models\RequestDocument;
use App\Models\ServiceRequest;
use Illuminate\Http\UploadedFile;

class CitizenRequestDocumentService
{
    public function upload(
        ServiceRequest $serviceRequest,
        int $userId,
        UploadedFile $document,
        ?string $documentType = null
    ): RequestDocument {
        return RequestDocument::create([
            'service_request_id' => $serviceRequest->id,
            'uploaded_by' => $userId,
            'document_path' => $document->store('request-documents', 'public'),
            'original_name' => $document->getClientOriginalName(),
            'document_type' => $documentType,
        ]);
    }
}
