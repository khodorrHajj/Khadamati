<?php

namespace App\Services;

use App\Models\ServiceRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AdminRequestUpdateService
{
    public function __construct(private readonly RequestPdfService $requestPdfService)
    {
    }

    public function update(
        ServiceRequest $serviceRequest,
        array $validated,
        ?UploadedFile $officialResponse = null,
        ?int $uploadedBy = null,
        bool $generateOfficialResponsePdf = false,
        ?string $issuerLabel = null
    ): void {
        $updates = [
            'status' => $validated['status'],
            'admin_internal_note' => $validated['admin_internal_note'] ?? null,
        ];

        if ($officialResponse || $generateOfficialResponsePdf) {
            if ($serviceRequest->official_response_path) {
                Storage::disk('public')->delete($serviceRequest->official_response_path);
            }

            if ($officialResponse) {
                $updates['official_response_path'] = $officialResponse->store('official-responses', 'public');
                $updates['official_response_original_name'] = $officialResponse->getClientOriginalName();
                $updates['official_response_uploaded_by'] = $uploadedBy;
                $updates['official_response_document_type'] = $validated['official_response_document_type'] ?? 'Official Response';
            } else {
                $updates = array_merge($updates, $this->requestPdfService->generateAndStoreOfficialResponse(
                    $serviceRequest,
                    $validated['official_response_summary'] ?? null,
                    $issuerLabel ?? 'Admin',
                    $validated['official_response_document_type'] ?? 'Official Response PDF',
                    $uploadedBy
                ));
            }
        }

        $serviceRequest->update($updates);
    }
}
