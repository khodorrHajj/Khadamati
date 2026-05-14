<?php

namespace App\Http\Controllers\Municipality;

use App\Http\Controllers\Controller;
use App\Http\Requests\Municipality\FilterServiceRequestsRequest;
use App\Http\Requests\Municipality\UpdateServiceRequestRequest;
use App\Models\Appointment;
use App\Models\GovernmentOffice;
use App\Models\ServiceRequest;
use App\Models\TimeSlot;
use App\Services\MunicipalityRequestListingService;
use App\Services\MunicipalityRequestUpdateService;
use Illuminate\Support\Facades\Auth;

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
        $data = $listingService->build($office, $filters);

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
            'user',
            'service.serviceCategory',
            'requestDocuments',
            'requestMessages.sender.role',
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

        return view('Municipality.requests.show', compact(
            'availableSlots',
            'currentAppointment',
            'office',
            'serviceRequest',
            'statuses'
        ));
    }

    public function update(
        UpdateServiceRequestRequest $request,
        ServiceRequest $serviceRequest,
        MunicipalityRequestUpdateService $updateService
    )
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return abort(403);
        }

        $this->authorizeOrAbort('updateMunicipality', $serviceRequest);

        $validated = $request->validated();
        $updateService->update($serviceRequest, $validated, $request->file('official_response'));

        return redirect()
            ->route('municipality.requests.show', $serviceRequest)
            ->with('success', 'Request updated successfully.');
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
