<?php

namespace App\Http\Controllers\Municipality;

use App\Http\Controllers\Controller;
use App\Http\Requests\Municipality\FilterServiceRequestsRequest;
use App\Http\Requests\Municipality\UpdateServiceRequestRequest;
use App\Models\Appointment;
use App\Models\GovernmentOffice;
use App\Models\RequestDocument;
use App\Models\ServiceRequest;
use App\Models\TimeSlot;
use App\Services\CitizenNotificationService;
use App\Services\MunicipalityRequestListingService;
use App\Services\MunicipalityRequestUpdateService;
use App\Services\RequestPdfService;
use App\Services\RequestTimelineService;
use App\Services\RequestWorkflowNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RequestController extends Controller
{
    public function index(
        FilterServiceRequestsRequest $request,
        MunicipalityRequestListingService $listingService
    )
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return view('Municipality.NoOffice');
        }

        $filters = $request->filters();
        $data = $listingService->build($office, $filters, Auth::id());

        return view('Municipality.Requests', compact(
            'filters',
            'office'
        ))->with($data);
    }

    public function show(ServiceRequest $serviceRequest)
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return view('Municipality.NoOffice');
        }

        $this->authorizeOrAbort('viewMunicipality', $serviceRequest);
        $this->markIncomingMessagesAsRead($serviceRequest);

        $serviceRequest->load([
            'appointments.timeSlot',
            'assignedBy.role',
            'assignedTo.role',
            'user',
            'service.serviceCategory',
            'requestDocuments',
            'officialResponseUploader',
            'requestMessages.sender.role',
            'timelineEntries.actor.role',
        ]);

        $statuses = ServiceRequest::statuses();

        $currentAppointment = $serviceRequest->appointments
            ->first(function (Appointment $appointment) {
                return $appointment->status !== Appointment::STATUS_CANCELLED;
            });

        $availableSlots = TimeSlot::where('government_office_id', $office->id)
            ->when($currentAppointment?->time_slot_id, function ($query) use ($currentAppointment) {
                $query->where(function ($slotQuery) use ($currentAppointment) {
                    $slotQuery->where('is_available', true)
                        ->orWhere('id', $currentAppointment->time_slot_id);
                });
            }, function ($query) {
                $query->where('is_available', true);
            })
            ->orderBy('starts_at')
            ->get();
        $requiredDocumentChoices = $serviceRequest->service?->requiredDocumentList() ?? [];

        return view('Municipality.requests.show', compact(
            'availableSlots',
            'currentAppointment',
            'office',
            'requiredDocumentChoices',
            'serviceRequest',
            'statuses'
        ));
    }

    public function update(
        UpdateServiceRequestRequest $request,
        ServiceRequest $serviceRequest,
        MunicipalityRequestUpdateService $updateService,
        CitizenNotificationService $citizenNotificationService,
        RequestTimelineService $timelineService,
        RequestWorkflowNotificationService $workflowNotificationService
    )
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return abort(403);
        }

        $this->authorizeOrAbort('updateMunicipality', $serviceRequest);

        $previousStatus = $serviceRequest->status;
        $officialResponseUploaded = $request->hasFile('official_response') || $request->boolean('generate_official_response_pdf');
        $validated = $request->validated();
        $updateService->update(
            $serviceRequest,
            $validated,
            $request->file('official_response'),
            Auth::id(),
            $request->boolean('generate_official_response_pdf'),
            $office->name ?? 'Municipality'
        );
        $serviceRequest = $serviceRequest->fresh(['user', 'service.governmentOffice']);
        $serviceRequest->update([
            'missing_document_items' => $validated['status'] === ServiceRequest::STATUS_MISSING_DOCUMENTS
                ? collect($validated['missing_document_items'] ?? [])
                    ->map(fn ($item) => trim((string) $item))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all()
                : null,
        ]);

        if ($request->boolean('escalate_to_admin')) {
            $serviceRequest->update([
                'assigned_to_user_id' => null,
                'assigned_by_user_id' => null,
                'assigned_at' => null,
                'workflow_state' => ServiceRequest::WORKFLOW_AWAITING_ADMIN,
                'escalated_to_admin_at' => now(),
                'escalation_reason' => $validated['escalation_reason'],
            ]);

            $timelineService->recordEscalatedToAdmin(
                $serviceRequest->fresh(['service']),
                $validated['escalation_reason'],
                $office->name ?? 'Municipality',
                Auth::id()
            );
            $workflowNotificationService->notifyEscalatedToAdmin(
                $serviceRequest->fresh(['service.governmentOffice', 'user']),
                $office->name ?? 'Municipality',
                $validated['escalation_reason']
            );
        } elseif ($serviceRequest->isClosed()) {
            $serviceRequest->update(ServiceRequest::resetHandoffContextAttributes());
        }

        if ($previousStatus !== $serviceRequest->status) {
            $timelineService->recordStatusChanged(
                $serviceRequest,
                $previousStatus,
                $office->name ?? 'Municipality',
                Auth::id()
            );
        }

        if ($officialResponseUploaded) {
            $timelineService->recordOfficialResponseUploaded(
                $serviceRequest,
                $office->name ?? 'Municipality',
                Auth::id()
            );
        }

        $citizenNotificationService->notifyRequestUpdated(
            $serviceRequest,
            $previousStatus,
            $officialResponseUploaded,
            $office->name ?? 'Municipality'
        );

        return redirect()
            ->route('municipality.requests.show', $serviceRequest)
            ->with('success', 'Request updated successfully.');
    }

    public function downloadReceipt(ServiceRequest $serviceRequest, RequestPdfService $requestPdfService)
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return abort(403);
        }

        $this->authorizeOrAbort('viewMunicipality', $serviceRequest);

        return $requestPdfService->downloadReceipt($serviceRequest);
    }

    public function downloadOfficialResponse(ServiceRequest $serviceRequest)
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return abort(403);
        }

        $this->authorizeOrAbort('viewMunicipality', $serviceRequest);

        if (!$serviceRequest->official_response_path || !Storage::disk('public')->exists($serviceRequest->official_response_path)) {
            return abort(404, 'The official response file could not be found.');
        }

        return Storage::disk('public')->download(
            $serviceRequest->official_response_path,
            $serviceRequest->official_response_original_name ?: basename($serviceRequest->official_response_path)
        );
    }

    public function downloadDocument(ServiceRequest $serviceRequest, RequestDocument $requestDocument)
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return abort(403);
        }

        $this->authorizeOrAbort('viewMunicipality', $serviceRequest);

        abort_if($requestDocument->service_request_id !== $serviceRequest->id, 404);

        if (!$requestDocument->document_path || !Storage::disk('public')->exists($requestDocument->document_path)) {
            return abort(404, 'The requested document file could not be found.');
        }

        return Storage::disk('public')->download(
            $requestDocument->document_path,
            $requestDocument->original_name ?: basename($requestDocument->document_path)
        );
    }

    private function assignedOffice(): ?GovernmentOffice
    {
        return Auth::user()->governmentOffice;
    }

    private function markIncomingMessagesAsRead(ServiceRequest $serviceRequest): void
    {
        $serviceRequest->requestMessages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', Auth::id())
            ->update(['read_at' => now()]);
    }
}
