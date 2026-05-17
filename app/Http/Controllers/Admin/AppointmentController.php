<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\GovernmentOffice;
use App\Models\Municipality;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Appointment::with(['user', 'serviceRequest.service', 'governmentOffice.municipality', 'timeSlot', 'approver']);

        // Filter by municipality
        if ($request->filled('municipality')) {
            $query->whereHas('governmentOffice', function ($q) use ($request) {
                $q->where('municipality_id', $request->municipality);
            });
        }

        // Filter by office
        if ($request->filled('office')) {
            $query->where('government_office_id', $request->office);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date from
        if ($request->filled('date_from')) {
            $query->whereHas('timeSlot', function ($q) use ($request) {
                $q->where('starts_at', '>=', $request->date_from);
            });
        }

        // Filter by date to
        if ($request->filled('date_to')) {
            $query->whereHas('timeSlot', function ($q) use ($request) {
                $q->where('starts_at', '<=', $request->date_to . ' 23:59:59');
            });
        }

        // Search by citizen name or tracking code
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%");
                })->orWhereHas('serviceRequest', function ($sq) use ($search) {
                    $sq->where('tracking_code', 'like', "%{$search}%");
                });
            });
        }

        $appointments = $query->latest()->paginate(15)->withQueryString();

        // Stats
        $allAppointments = Appointment::all();
        $stats = [
            'total' => $allAppointments->count(),
            'requested' => $allAppointments->where('status', Appointment::STATUS_REQUESTED)->count(),
            'approved' => $allAppointments->where('status', Appointment::STATUS_APPROVED)->count(),
            'rescheduled' => $allAppointments->where('status', Appointment::STATUS_RESCHEDULED)->count(),
            'cancelled' => $allAppointments->where('status', Appointment::STATUS_CANCELLED)->count(),
        ];

        $municipalities = Municipality::orderBy('name')->get();
        $offices = GovernmentOffice::with('municipality')->orderBy('name')->get();

        return view('Admin.appointments.index', compact(
            'appointments',
            'stats',
            'municipalities',
            'offices'
        ));
    }

    public function show(Appointment $appointment): View
    {
        $appointment->load(['user', 'serviceRequest.service.governmentOffice.municipality', 'serviceRequest.service.serviceCategory', 'governmentOffice.municipality', 'timeSlot', 'approver']);

        return view('Admin.appointments.show', compact('appointment'));
    }
}
