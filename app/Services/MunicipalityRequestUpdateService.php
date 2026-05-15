<?php

namespace App\Services;

use App\Models\ServiceRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MunicipalityRequestUpdateService
{
    public function update(
        ServiceRequest $serviceRequest,
        array $validated,
        ?UploadedFile $officialResponse = null,
        ?int $uploadedBy = null
    ): void {
        $updates = [
            'status' => $validated['status'],
            'message' => $validated['notes'] ?? null,
        ];

        if ($officialResponse) {
            if ($serviceRequest->official_response_path) {
                Storage::disk('public')->delete($serviceRequest->official_response_path);
            }

            $updates['official_response_path'] = $officialResponse->store('official-responses', 'public');
            $updates['official_response_original_name'] = $officialResponse->getClientOriginalName();
            $updates['official_response_uploaded_by'] = $uploadedBy;
            $updates['official_response_document_type'] = $validated['official_response_document_type'] ?? 'Official Response';
        }

        $serviceRequest->update($updates);
    }
}
