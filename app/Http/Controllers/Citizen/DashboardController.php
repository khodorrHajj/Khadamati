<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\RequestMessage;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        $requestsQuery = ServiceRequest::query()
            ->where('user_id', $user->id);

        $totalRequests = (clone $requestsQuery)->count();
        $pendingRequests = (clone $requestsQuery)
            ->where('status', ServiceRequest::STATUS_PENDING)
            ->count();
        $completedRequests = (clone $requestsQuery)
            ->where('status', ServiceRequest::STATUS_COMPLETED)
            ->count();

        $upcomingAppointments = Appointment::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', Appointment::STATUS_CANCELLED)
            ->whereHas('timeSlot', function ($query) {
                $query->where('starts_at', '>=', now());
            })
            ->count();

        $unreadMessages = RequestMessage::query()
            ->unread()
            ->where('sender_id', '!=', $user->id)
            ->whereHas('serviceRequest', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->count();

        $actionRequired = (clone $requestsQuery)
            ->where('status', ServiceRequest::STATUS_MISSING_DOCUMENTS)
            ->count();

        $recentRequests = (clone $requestsQuery)
            ->with(['service.governmentOffice'])
            ->latest()
            ->take(5)
            ->get();

        return view('Citizen.Dashboard', compact(
            'actionRequired',
            'completedRequests',
            'pendingRequests',
            'recentRequests',
            'totalRequests',
            'unreadMessages',
            'upcomingAppointments',
        ));
    }
}
