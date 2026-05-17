<?php

namespace App\Http\Controllers\Municipality;

use App\Http\Controllers\Controller;
use App\Http\Requests\Municipality\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\GovernmentOffice;
use App\Models\TimeSlot;
use App\Services\CitizenNotificationService;
use App\Services\AppointmentEmailHook;
use App\Services\RequestTimelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    public function index()
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return view('Municipality.NoOffice');
        }

        $slots = TimeSlot::where('government_office_id', $office->id)
            ->orderBy('starts_at')
            ->get();

        $appointments = Appointment::with(['serviceRequest.user', 'serviceRequest.service', 'timeSlot'])
            ->where('government_office_id', $office->id)
            ->latest()
            ->get();

        return view('Municipality.appointments.index', compact('appointments', 'office', 'slots'));
    }

    public function storeSlot(Request $request): RedirectResponse
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return abort(403);
        }

        $validated = $request->validate([
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);

        TimeSlot::create([
            'government_office_id' => $office->id,
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'created_by' => Auth::id(),
            'is_available' => true,
        ]);

        return redirect()
            ->route('municipality.appointments.index')
            ->with('success', 'Time slot created successfully.');
    }

    public function update(
        UpdateAppointmentRequest $request,
        Appointment $appointment,
        AppointmentEmailHook $emailHook,
        CitizenNotificationService $citizenNotificationService,
        RequestTimelineService $timelineService
    ): RedirectResponse
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return abort(403);
        }

        $this->authorizeOrAbort('updateMunicipality', $appointment);

        $validated = $request->validated();

        $appointment->loadMissing('timeSlot');

        if ($validated['action'] === 'approve') {
            $appointment->update([
                'status' => Appointment::STATUS_APPROVED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'municipality_notes' => $validated['municipality_notes'] ?? null,
                'cancelled_at' => null,
                'reminder_scheduled_at' => Appointment::reminderScheduleFor($appointment->timeSlot?->starts_at),
            ]);

            $appointment = $appointment->fresh(['governmentOffice', 'serviceRequest.service', 'timeSlot', 'user']);
            $emailHook->send($appointment, 'approved');
            $timelineService->recordAppointmentUpdated($appointment, $office->name ?? 'Municipality', Auth::id());
            $citizenNotificationService->notifyAppointmentUpdated($appointment);
        }

        if ($validated['action'] === 'reschedule') {
            $targetSlot = TimeSlot::findOrFail($validated['time_slot_id']);

            if (
                $targetSlot->id !== $appointment->time_slot_id &&
                !$targetSlot->is_available
            ) {
                return redirect()->back()->withErrors([
                    'time_slot_id' => 'The selected replacement slot is no longer available.',
                ]);
            }

            if ($appointment->timeSlot && $appointment->timeSlot->id !== $targetSlot->id) {
                $appointment->timeSlot->update(['is_available' => true]);
            }

            $targetSlot->update(['is_available' => false]);

            $appointment->update([
                'time_slot_id' => $targetSlot->id,
                'status' => Appointment::STATUS_RESCHEDULED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'municipality_notes' => $validated['municipality_notes'] ?? null,
                'cancelled_at' => null,
                'reminder_scheduled_at' => Appointment::reminderScheduleFor($targetSlot->starts_at),
            ]);

            $appointment = $appointment->fresh(['governmentOffice', 'serviceRequest.service', 'timeSlot', 'user']);
            $emailHook->send($appointment, 'rescheduled');
            $timelineService->recordAppointmentUpdated($appointment, $office->name ?? 'Municipality', Auth::id());
            $citizenNotificationService->notifyAppointmentUpdated($appointment);
        }

        if ($validated['action'] === 'cancel') {
            if ($appointment->timeSlot) {
                $appointment->timeSlot->update(['is_available' => true]);
            }

            $appointment->update([
                'status' => Appointment::STATUS_CANCELLED,
                'municipality_notes' => $validated['municipality_notes'] ?? null,
                'cancelled_at' => now(),
                'reminder_scheduled_at' => null,
            ]);

            $appointment = $appointment->fresh(['governmentOffice', 'serviceRequest.service', 'timeSlot', 'user']);
            $emailHook->send($appointment, 'cancelled');
            $timelineService->recordAppointmentUpdated($appointment, $office->name ?? 'Municipality', Auth::id());
            $citizenNotificationService->notifyAppointmentUpdated($appointment);
        }

        return redirect()->back()->with('success', 'Appointment updated successfully.');
    }

    private function assignedOffice(): ?GovernmentOffice
    {
        return Auth::user()->governmentOffice;
    }

}
