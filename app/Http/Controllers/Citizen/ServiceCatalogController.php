<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\StoreRequestDocumentRequest;
use App\Http\Requests\Citizen\StoreServiceRequestRequest;
use App\Models\GovernmentOffice;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\TimeSlot;
use App\Notifications\NewServiceRequestNotification;
use App\Notifications\RequestDocumentUploadedNotification;
use App\Services\CitizenRequestDocumentService;
use App\Services\CitizenServiceRequestService;
use App\Services\MunicipalityOfficeNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceCatalogController extends Controller
{
    public function offices()
    {
        $offices = GovernmentOffice::with('municipality')
            ->where('status', 'active')
            ->whereHas('services', function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('name')
            ->paginate(9);

        return view('Citizen.offices.index', compact('offices'));
    }

    public function office(GovernmentOffice $office)
    {
        $this->ensureActiveOffice($office);

        $categories = ServiceCategory::where('government_office_id', $office->id)
            ->whereHas('services', function ($query) {
                $query->where('is_active', true);
            })
            ->withCount([
                'services as active_services_count' => function ($query) {
                    $query->where('is_active', true);
                },
            ])
            ->orderBy('name')
            ->get();

        return view('Citizen.offices.show', compact('categories', 'office'));
    }

    public function category(GovernmentOffice $office, ServiceCategory $category)
    {
        $this->ensureActiveOffice($office);
        abort_if($category->government_office_id !== $office->id, 404);

        $services = Service::where('government_office_id', $office->id)
            ->where('service_category_id', $category->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('Citizen.categories.show', compact('category', 'office', 'services'));
    }

    public function service(Service $service)
    {
        $service->load(['governmentOffice.municipality', 'serviceCategory']);
        $this->ensureActiveService($service);

        return view('Citizen.services.show', compact('service'));
    }

    public function storeRequest(
        StoreServiceRequestRequest $request,
        Service $service,
        CitizenServiceRequestService $serviceRequestService,
        MunicipalityOfficeNotificationService $notificationService
    )
    {
        $service->load(['governmentOffice.municipality', 'serviceCategory']);
        $this->ensureActiveService($service);

        $validated = $request->validated();
        $serviceRequest = $serviceRequestService->create(
            $service,
            Auth::id(),
            $validated['notes'] ?? null,
            $request->file('documents', [])
        );

        $notificationService->notifyForServiceRequest(
            $serviceRequest,
            new NewServiceRequestNotification($serviceRequest)
        );

        return redirect()
            ->route('tracking.show', $serviceRequest->tracking_code)
            ->with('success', 'Request submitted successfully. Keep your tracking code safe.');
    }

    public function request(ServiceRequest $serviceRequest)
    {
        $this->authorizeOrAbort('viewCitizen', $serviceRequest);
        $this->markIncomingMessagesAsRead($serviceRequest);
        $serviceRequest->load([
            'appointments.timeSlot',
            'service.governmentOffice.municipality',
            'service.serviceCategory',
            'requestDocuments',
            'feedback.responder',
            'requestMessages.sender.role',
        ]);

        $currentAppointment = $serviceRequest->appointments
            ->first(function (Appointment $appointment) {
                return $appointment->status !== Appointment::STATUS_CANCELLED;
            });

        $availableSlots = collect();
        $officeId = $serviceRequest->service?->government_office_id;

        if ($officeId && !$currentAppointment) {
            $availableSlots = TimeSlot::where('government_office_id', $officeId)
                ->available()
                ->orderBy('starts_at')
                ->get();
        }

        return view('Citizen.requests.show', compact('availableSlots', 'currentAppointment', 'serviceRequest'));
    }

    public function storeDocument(
        StoreRequestDocumentRequest $request,
        ServiceRequest $serviceRequest,
        CitizenRequestDocumentService $documentService,
        MunicipalityOfficeNotificationService $notificationService
    )
    {
        $this->authorizeOrAbort('uploadDocument', $serviceRequest);

        $validated = $request->validated();
        $document = $documentService->upload(
            $serviceRequest,
            Auth::id(),
            $request->file('document'),
            $validated['document_type'] ?? null
        );

        $notificationService->notifyForServiceRequest(
            $serviceRequest->fresh(['service.governmentOffice', 'user']),
            new RequestDocumentUploadedNotification($document)
        );

        return redirect()
            ->route('citizen.requests.show', $serviceRequest)
            ->with('success', 'Document uploaded successfully.');
    }

    private function ensureActiveOffice(GovernmentOffice $office): void
    {
        abort_if($office->status !== 'active', 404);
    }

    private function ensureActiveService(Service $service): void
    {
        abort_if(
            !$service->is_active || !$service->governmentOffice || $service->governmentOffice->status !== 'active',
            404
        );
    }

    private function markIncomingMessagesAsRead(ServiceRequest $serviceRequest): void
    {
        $serviceRequest->requestMessages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', Auth::id())
            ->update(['read_at' => now()]);
    }

}
