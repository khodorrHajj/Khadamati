<?php

namespace App\Http\Controllers\Municipality;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $office = Auth::user()->governmentOffice;

        if (!$office) {
            return view('Municipality.NoOffice');
        }

        $totalCategories = ServiceCategory::where('government_office_id', $office->id)->count();
        $totalServices = Service::where('government_office_id', $office->id)->count();
        $requestsQuery = ServiceRequest::whereHas('service', function ($query) use ($office) {
            $query->where('government_office_id', $office->id);
        });
        $totalRequests = (clone $requestsQuery)->count();
        $pendingRequests = (clone $requestsQuery)
            ->where('status', ServiceRequest::STATUS_PENDING)
            ->count();
        $inReviewRequests = (clone $requestsQuery)
            ->where('status', ServiceRequest::STATUS_IN_REVIEW)
            ->count();
        $completedRequests = (clone $requestsQuery)
            ->where('status', ServiceRequest::STATUS_COMPLETED)
            ->count();
        $missingDocumentsRequests = (clone $requestsQuery)
            ->where('status', ServiceRequest::STATUS_MISSING_DOCUMENTS)
            ->count();
        $assignedToMeRequests = (clone $requestsQuery)
            ->where('assigned_to_user_id', Auth::id())
            ->count();
        $awaitingAdminRequests = (clone $requestsQuery)
            ->where('workflow_state', ServiceRequest::WORKFLOW_AWAITING_ADMIN)
            ->count();
        $awaitingMunicipalityRequests = (clone $requestsQuery)
            ->where('workflow_state', ServiceRequest::WORKFLOW_AWAITING_MUNICIPALITY)
            ->count();
        $overdueRequests = (clone $requestsQuery)
            ->with('service')
            ->get()
            ->filter->isOverdue()
            ->count();

        return view('Municipality.Dashboard', compact(
            'office',
            'totalCategories',
            'totalServices',
            'totalRequests',
            'pendingRequests',
            'inReviewRequests',
            'completedRequests',
            'missingDocumentsRequests',
            'assignedToMeRequests',
            'awaitingAdminRequests',
            'awaitingMunicipalityRequests',
            'overdueRequests'
        ));
    }
}
