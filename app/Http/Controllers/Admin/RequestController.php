<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FilterServiceRequestsRequest;
use App\Http\Requests\Admin\UpdateServiceRequestRequest;
use App\Models\RequestDocument;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\AdminRequestListingService;
use App\Services\AdminRequestUpdateService;
use App\Services\CitizenNotificationService;
use App\Services\RequestPdfService;
use App\Services\RequestTimelineService;
use App\Services\RequestWorkflowNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class RequestController extends Controller
{
    public function index(
        FilterServiceRequestsRequest $request,
        AdminRequestListingService $listingService
    ): View {
        $filters = $request->filters();

        return view('Admin.requests.index', compact('filters'))
            ->with($listingService->build($filters));
    }

    public function poll(
        FilterServiceRequestsRequest $request,
        AdminRequestListingService $listingService
    ): JsonResponse {
        $filters = $request->filters();
        $data = $listingService->build($filters);

        return response()->json([
            'table_html' => view('Admin.requests._table', [
                'requests' => $data['requests'],
            ])->render(),
            'pagination_html' => view('Admin.requests._pagination', [
                'requests' => $data['requests'],
            ])->render(),
            'count' => $data['requests']->count(),
        ]);
    }

    public function show(ServiceRequest $serviceRequest): View
    {
        $this->authorizeOrAbort('viewAdmin', $serviceRequest);

        $serviceRequest->load([
            'assignedBy.role',
            'assignedTo.role',
            'user',
            'service.governmentOffice.municipality',
            'service.serviceCategory',
            'requestDocuments',
            'officialResponseUploader.role',
            'timelineEntries.actor.role',
        ]);

        $statuses = ServiceRequest::statuses();
        $municipalityUsers = User::with('role')
            ->whereHas('role', function ($query) {
                $query->where('role', 'municipality');
            })
            ->when($serviceRequest->service?->government_office_id, function ($query, $officeId) {
                $query->where('government_office_id', $officeId);
            })
            ->orderBy('name')
            ->get();

        return view('Admin.requests.show', compact('municipalityUsers', 'serviceRequest', 'statuses'));
    }

    public function update(
        UpdateServiceRequestRequest $request,
        ServiceRequest $serviceRequest,
        AdminRequestUpdateService $updateService,
        CitizenNotificationService $citizenNotificationService,
        RequestTimelineService $timelineService,
        RequestWorkflowNotificationService $workflowNotificationService
    ): RedirectResponse {
        $this->authorizeOrAbort('updateAdmin', $serviceRequest);

        $previousStatus = $serviceRequest->status;
        $previousWorkflowState = $serviceRequest->workflow_state;
        $previousAssignedToUserId = $serviceRequest->assigned_to_user_id;
        $officialResponseUploaded = $request->hasFile('official_response') || $request->boolean('generate_official_response_pdf');
        $validated = $request->validated();
        $updateService->update(
            $serviceRequest,
            $validated,
            $request->file('official_response'),
            Auth::id(),
            $request->boolean('generate_official_response_pdf'),
            'Platform Administration'
        );
        $handoffUpdates = [];
        $newWorkflowState = $validated['workflow_state'] ?? $serviceRequest->workflow_state;
        $newAssignedToUserId = array_key_exists('assigned_to_user_id', $validated)
            ? ($validated['assigned_to_user_id'] ?: null)
            : $serviceRequest->assigned_to_user_id;

        if (in_array($validated['status'], ServiceRequest::terminalStatuses(), true)) {
            $handoffUpdates = ServiceRequest::resetHandoffContextAttributes();
        } else {
            if ($newWorkflowState !== $serviceRequest->workflow_state) {
                $handoffUpdates['workflow_state'] = $newWorkflowState;
            }

            if ($newAssignedToUserId !== $serviceRequest->assigned_to_user_id) {
                $handoffUpdates['assigned_to_user_id'] = $newAssignedToUserId;
            }

            if ($newWorkflowState === ServiceRequest::WORKFLOW_AWAITING_MUNICIPALITY) {
                $handoffUpdates = array_merge(
                    $handoffUpdates,
                    ServiceRequest::clearEscalationContextAttributes()
                );
            }

            if (
                $newWorkflowState !== $previousWorkflowState ||
                $newAssignedToUserId !== $previousAssignedToUserId
            ) {
                $handoffUpdates['assigned_by_user_id'] = Auth::id();
                $handoffUpdates['assigned_at'] = now();
            }
        }

        if (!empty($handoffUpdates)) {
            $serviceRequest->update($handoffUpdates);
        }

        $serviceRequest = $serviceRequest->fresh(['assignedTo', 'user', 'service.governmentOffice']);

        if ($previousStatus !== $serviceRequest->status) {
            $timelineService->recordStatusChanged(
                $serviceRequest,
                $previousStatus,
                'Admin',
                Auth::id()
            );
        }

        if ($officialResponseUploaded) {
            $timelineService->recordOfficialResponseUploaded(
                $serviceRequest,
                'Admin',
                Auth::id()
            );
        }

        if (
            $serviceRequest->workflow_state === ServiceRequest::WORKFLOW_AWAITING_MUNICIPALITY &&
            (
                $previousWorkflowState !== ServiceRequest::WORKFLOW_AWAITING_MUNICIPALITY ||
                $previousAssignedToUserId !== $serviceRequest->assigned_to_user_id
            )
        ) {
            if ($serviceRequest->assignedTo) {
                $timelineService->recordAssignedToMunicipalityUser(
                    $serviceRequest,
                    $serviceRequest->assignedTo,
                    'Admin',
                    Auth::id()
                );
                $workflowNotificationService->notifyAssignedToMunicipality(
                    $serviceRequest->fresh(['service.governmentOffice', 'user']),
                    $serviceRequest->assignedTo,
                    'Admin'
                );
            }

            $timelineService->recordReturnedToMunicipality(
                $serviceRequest,
                $serviceRequest->assignedTo,
                'Admin',
                Auth::id()
            );
        }

        $citizenNotificationService->notifyRequestUpdated(
            $serviceRequest,
            $previousStatus,
            $officialResponseUploaded,
            'Admin'
        );

        return redirect()
            ->route('admin.requests.show', $serviceRequest)
            ->with('success', 'Request updated successfully.');
    }

    public function downloadReceipt(ServiceRequest $serviceRequest, RequestPdfService $requestPdfService)
    {
        $this->authorizeOrAbort('viewAdmin', $serviceRequest);

        return $requestPdfService->downloadReceipt($serviceRequest);
    }

    public function downloadDocument(ServiceRequest $serviceRequest, RequestDocument $requestDocument)
    {
        $this->authorizeOrAbort('viewAdmin', $serviceRequest);

        abort_if($requestDocument->service_request_id !== $serviceRequest->id, 404);

        if (!$requestDocument->document_path || !Storage::disk('public')->exists($requestDocument->document_path)) {
            return abort(404, 'The requested document file could not be found.');
        }

        return Storage::disk('public')->download(
            $requestDocument->document_path,
            $requestDocument->original_name ?: basename($requestDocument->document_path)
        );
    }

    public function downloadOfficialResponse(ServiceRequest $serviceRequest)
    {
        $this->authorizeOrAbort('viewAdmin', $serviceRequest);

        if (!$serviceRequest->official_response_path || !Storage::disk('public')->exists($serviceRequest->official_response_path)) {
            return abort(404, 'The official response file could not be found.');
        }

        return Storage::disk('public')->download(
            $serviceRequest->official_response_path,
            $serviceRequest->official_response_original_name ?: basename($serviceRequest->official_response_path)
        );
    }
}
