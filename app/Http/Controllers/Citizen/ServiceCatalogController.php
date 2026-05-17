<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\StoreRequestDocumentRequest;
use App\Http\Requests\Citizen\StoreServiceRequestRequest;
use App\Models\Appointment;
use App\Models\GovernmentOffice;
use App\Models\Municipality;
use App\Models\RequestDocument;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\TimeSlot;
use App\Notifications\NewServiceRequestNotification;
use App\Notifications\RequestDocumentUploadedNotification;
use App\Services\CitizenRequestDocumentService;
use App\Services\CitizenNotificationService;
use App\Services\CitizenOfficeDiscoveryService;
use App\Services\CitizenServiceRequestService;
use App\Services\MunicipalityOfficeNotificationService;
use App\Services\RequestPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ServiceCatalogController extends Controller
{
    public function services(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'municipality' => ['nullable', 'integer', 'exists:municipalities,id'],
            'office' => ['nullable', 'integer', 'exists:government_offices,id'],
            'category' => ['nullable', 'integer', 'exists:service_categories,id'],
        ]);

        $services = Service::with(['governmentOffice.municipality', 'serviceCategory'])
            ->where('is_active', true)
            ->whereHas('governmentOffice', function ($query) {
                $query->where('status', 'active');
            })
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($filters['municipality'] ?? null, function ($query, $municipalityId) {
                $query->whereHas('governmentOffice', function ($officeQuery) use ($municipalityId) {
                    $officeQuery->where('municipality_id', $municipalityId);
                });
            })
            ->when($filters['office'] ?? null, function ($query, $officeId) {
                $query->where('government_office_id', $officeId);
            })
            ->when($filters['category'] ?? null, function ($query, $categoryId) {
                $query->where('service_category_id', $categoryId);
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $municipalities = Municipality::whereHas('governmentOffices.services', function ($query) {
                $query->where('is_active', true);
            })
            ->whereHas('governmentOffices', function ($query) {
                $query->where('status', 'active');
            })
            ->orderBy('name')
            ->get();

        $offices = GovernmentOffice::where('status', 'active')
            ->whereHas('services', function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('name')
            ->get();

        $categories = ServiceCategory::whereHas('governmentOffice', function ($query) {
                $query->where('status', 'active');
            })
            ->whereHas('services', function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('name')
            ->get();

        return view('Citizen.services.index', compact('categories', 'filters', 'municipalities', 'offices', 'services'));
    }

    public function offices(Request $request, CitizenOfficeDiscoveryService $officeDiscoveryService)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'municipality' => ['nullable', 'integer', 'exists:municipalities,id'],
            'category' => ['nullable', 'integer', 'exists:service_categories,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'radius_km' => ['nullable', 'numeric', 'min:1', 'max:500'],
        ]);

        $municipalities = Municipality::whereHas('governmentOffices.services', function ($query) {
                $query->where('is_active', true);
            })
            ->whereHas('governmentOffices', function ($query) {
                $query->where('status', 'active');
            })
            ->orderBy('name')
            ->get();

        $categories = ServiceCategory::whereHas('services', function ($query) {
                $query->where('is_active', true);
            })
            ->whereHas('governmentOffice', function ($query) {
                $query->where('status', 'active');
            })
            ->orderBy('name')
            ->get();

        $data = $officeDiscoveryService->build($filters);
        $offices = $data['offices'];
        $hasLocationFilter = $data['hasLocationFilter'];

        return view('Citizen.offices.index', compact(
            'categories',
            'filters',
            'hasLocationFilter',
            'municipalities',
            'offices'
        ));
    }

    public function office(GovernmentOffice $office)
    {
        $this->ensureActiveOffice($office);
        $office->load(['municipality', 'workingHours']);

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

        $services = Service::with('serviceCategory')
            ->where('government_office_id', $office->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('Citizen.offices.show', compact('categories', 'office', 'services'));
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

    public function createRequest(Service $service)
    {
        $service->load(['governmentOffice.municipality', 'serviceCategory']);
        $this->ensureActiveService($service);

        if ((float) $service->price > 0) {
            return redirect()->route('citizen.payment.show', $service);
        }

        return view('Citizen.services.request', compact('service'));
    }

    public function storeRequest(
        StoreServiceRequestRequest $request,
        Service $service,
        CitizenServiceRequestService $serviceRequestService,
        MunicipalityOfficeNotificationService $notificationService,
        CitizenNotificationService $citizenNotificationService
    )
    {
        $service->load(['governmentOffice.municipality', 'serviceCategory']);
        $this->ensureActiveService($service);

        abort_if((float) $service->price > 0, 403, 'This service requires payment. Please complete payment first.');

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
        $citizenNotificationService->notifyRequestSubmitted(
            $serviceRequest->fresh(['user', 'service.governmentOffice'])
        );

        return redirect()
            ->route('citizen.requests.show', $serviceRequest)
            ->with('success', 'Request submitted successfully. Your tracking code is ' . $serviceRequest->tracking_code . '.');
    }

    public function requests(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', 'string', 'in:' . implode(',', ServiceRequest::statuses())],
            'service' => ['nullable', 'integer', 'exists:services,id'],
            'office' => ['nullable', 'integer', 'exists:government_offices,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'tracking_code' => ['nullable', 'string', 'max:255'],
        ]);

        $baseQuery = ServiceRequest::query()
            ->where('user_id', Auth::id());

        $requests = (clone $baseQuery)
            ->with(['service.governmentOffice'])
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['service'] ?? null, function ($query, $serviceId) {
                $query->where('service_id', $serviceId);
            })
            ->when($filters['office'] ?? null, function ($query, $officeId) {
                $query->whereHas('service', function ($serviceQuery) use ($officeId) {
                    $serviceQuery->where('government_office_id', $officeId);
                });
            })
            ->when($filters['date_from'] ?? null, function ($query, $dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($filters['date_to'] ?? null, function ($query, $dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->when($filters['tracking_code'] ?? null, function ($query, $trackingCode) {
                $query->where('tracking_code', 'like', "%{$trackingCode}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $services = Service::whereIn('id', (clone $baseQuery)->pluck('service_id')->unique())
            ->orderBy('name')
            ->get();

        $offices = GovernmentOffice::whereHas('services.serviceRequests', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->orderBy('name')
            ->get();

        $statuses = ServiceRequest::statuses();

        return view('Citizen.requests.index', compact('filters', 'offices', 'requests', 'services', 'statuses'));
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
            'timelineEntries.actor.role',
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

    public function downloadDocument(ServiceRequest $serviceRequest, RequestDocument $requestDocument)
    {
        $this->authorizeOrAbort('viewCitizen', $serviceRequest);

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
        $this->authorizeOrAbort('viewCitizen', $serviceRequest);

        if (!$serviceRequest->official_response_path || !Storage::disk('public')->exists($serviceRequest->official_response_path)) {
            return abort(404, 'The official response file could not be found.');
        }

        return Storage::disk('public')->download(
            $serviceRequest->official_response_path,
            $serviceRequest->official_response_original_name ?: basename($serviceRequest->official_response_path)
        );
    }

    public function downloadReceipt(ServiceRequest $serviceRequest, RequestPdfService $requestPdfService)
    {
        $this->authorizeOrAbort('viewCitizen', $serviceRequest);

        return $requestPdfService->downloadReceipt($serviceRequest);
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
