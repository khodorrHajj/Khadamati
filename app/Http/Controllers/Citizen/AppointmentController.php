<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\ServiceRequest;
use App\Models\TimeSlot;
use App\Services\AppointmentEmailHook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    public function store(StoreAppointmentRequest $request, ServiceRequest $serviceRequest, AppointmentEmailHook $emailHook): RedirectResponse
    {
        $this->authorizeOrAbort('createForCitizen', [Appointment::class, $serviceRequest]);

        $officeId = $serviceRequest->service?->government_office_id;
        abort_if(!$officeId, 404);

        $validated = $request->validated();

        $activeAppointment = $serviceRequest->appointments()
            ->where('status', '!=', Appointment::STATUS_CANCELLED)
            ->first();

        if ($activeAppointment) {
            return redirect()
                ->route('citizen.requests.show', $serviceRequest)
                ->withErrors(['appointment' => 'This request already has an active appointment.']);
        }

        $slot = TimeSlot::findOrFail($validated['time_slot_id']);

        if (!$slot->is_available) {
            return redirect()
                ->route('citizen.requests.show', $serviceRequest)
                ->withErrors(['appointment' => 'The selected time slot is no longer available.']);
        }

        $slot->update(['is_available' => false]);

        $appointment = Appointment::create([
            'service_request_id' => $serviceRequest->id,
            'user_id' => Auth::id(),
            'government_office_id' => $officeId,
            'time_slot_id' => $slot->id,
            'status' => Appointment::STATUS_REQUESTED,
            'notes' => $validated['notes'] ?? null,
            'reminder_scheduled_at' => Appointment::reminderScheduleFor($slot->starts_at),
        ]);

        $emailHook->send($appointment->fresh(['governmentOffice', 'serviceRequest.service', 'timeSlot', 'user']), 'requested');

        return redirect()
            ->route('citizen.requests.show', $serviceRequest)
            ->with('success', 'Appointment booked successfully.');
    }
}
