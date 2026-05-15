<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GovernmentOffice;
use App\Models\Municipality;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $requests = ServiceRequest::with('service');

        $requestStats = [
            'total' => (clone $requests)->count(),
            'awaitingAdmin' => (clone $requests)->where('workflow_state', ServiceRequest::WORKFLOW_AWAITING_ADMIN)->count(),
            'awaitingMunicipality' => (clone $requests)->where('workflow_state', ServiceRequest::WORKFLOW_AWAITING_MUNICIPALITY)->count(),
            'overdue' => (clone $requests)->get()->filter->isOverdue()->count(),
            'unassigned' => (clone $requests)->whereNull('assigned_to_user_id')->count(),
        ];

        return view('Admin.Dashboard', [
            'municipalityCount' => Municipality::count(),
            'officeCount' => GovernmentOffice::count(),
            'municipalityUserCount' => User::whereHas('role', function ($query) {
                $query->where('role', 'municipality');
            })->count(),
            'requestStats' => $requestStats,
        ]);
    }
}
