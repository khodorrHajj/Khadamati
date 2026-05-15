<?php

namespace App\Services;

use App\Models\ServiceRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class RequestPdfService
{
    public function downloadReceipt(ServiceRequest $serviceRequest): Response
    {
        $request = $this->loadPdfRelations($serviceRequest);

        return Pdf::loadView('pdfs.request-receipt', [
            'serviceRequest' => $request,
        ])->setPaper('a4')
            ->download($this->receiptFilename($request));
    }

    public function generateAndStoreOfficialResponse(
        ServiceRequest $serviceRequest,
        ?string $summary,
        string $issuerLabel,
        string $documentType,
        ?int $uploadedBy = null
    ): array {
        $request = $this->loadPdfRelations($serviceRequest);
        $filename = $this->officialResponseFilename($request);
        $path = 'official-responses/' . $filename;
        $summary = trim((string) $summary);

        if ($summary === '') {
            $summary = $this->defaultOfficialResponseSummary($request);
        }

        $contents = Pdf::loadView('pdfs.official-response', [
            'serviceRequest' => $request,
            'issuerLabel' => $issuerLabel,
            'summary' => $summary,
        ])->setPaper('a4')
            ->output();

        Storage::disk('public')->put($path, $contents);

        return [
            'official_response_path' => $path,
            'official_response_original_name' => $filename,
            'official_response_uploaded_by' => $uploadedBy,
            'official_response_document_type' => $documentType ?: 'Official Response PDF',
        ];
    }

    public function receiptFilename(ServiceRequest $serviceRequest): string
    {
        return ($serviceRequest->tracking_code ?: ('request-' . $serviceRequest->id)) . '-receipt.pdf';
    }

    public function officialResponseFilename(ServiceRequest $serviceRequest): string
    {
        return ($serviceRequest->tracking_code ?: ('request-' . $serviceRequest->id)) . '-official-response.pdf';
    }

    private function defaultOfficialResponseSummary(ServiceRequest $serviceRequest): string
    {
        $serviceName = $serviceRequest->service?->name ?? 'your service request';
        $status = $serviceRequest->status ?: 'updated';

        return sprintf(
            'This document confirms that your request for %s is currently marked as %s. Please keep this PDF for your records and contact the office if you need further assistance.',
            $serviceName,
            $status
        );
    }

    private function loadPdfRelations(ServiceRequest $serviceRequest): ServiceRequest
    {
        return $serviceRequest->loadMissing([
            'user',
            'service.governmentOffice.municipality',
            'service.serviceCategory',
            'requestDocuments',
        ]);
    }
}
