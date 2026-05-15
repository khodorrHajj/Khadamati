<?php

namespace App\Services;

use App\Models\GovernmentOffice;
use App\Models\Municipality;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;

class AdminRequestListingService
{
    public function build(array $filters): array
    {
        $requests = ServiceRequest::with(['user', 'service.governmentOffice.municipality', 'service.serviceCategory'])
            ->with(['assignedTo'])
            ->withCount('requestDocuments')
            ->when($filters['status'], function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when($filters['workflow_state'], function ($query) use ($filters) {
                $query->where('workflow_state', $filters['workflow_state']);
            })
            ->when($filters['assignment_scope'] === 'assigned', function ($query) {
                $query->whereNotNull('assigned_to_user_id');
            })
            ->when($filters['assignment_scope'] === 'unassigned', function ($query) {
                $query->whereNull('assigned_to_user_id');
            })
            ->when($filters['assignment_scope'] === 'escalated', function ($query) {
                $query->where('workflow_state', ServiceRequest::WORKFLOW_AWAITING_ADMIN);
            })
            ->when($filters['assigned_to_user_id'], function ($query) use ($filters) {
                $query->where('assigned_to_user_id', $filters['assigned_to_user_id']);
            })
            ->when($filters['municipality'], function ($query) use ($filters) {
                $query->whereHas('service.governmentOffice', function ($officeQuery) use ($filters) {
                    $officeQuery->where('municipality_id', $filters['municipality']);
                });
            })
            ->when($filters['office'], function ($query) use ($filters) {
                $query->whereHas('service', function ($serviceQuery) use ($filters) {
                    $serviceQuery->where('government_office_id', $filters['office']);
                });
            })
            ->when($filters['service'], function ($query) use ($filters) {
                $query->where('service_id', $filters['service']);
            })
            ->when($filters['category'], function ($query) use ($filters) {
                $query->whereHas('service', function ($serviceQuery) use ($filters) {
                    $serviceQuery->where('service_category_id', $filters['category']);
                });
            })
            ->when($filters['date_from'], function ($query) use ($filters) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            })
            ->when($filters['date_to'], function ($query) use ($filters) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            })
            ->when($filters['search'], function ($query) use ($filters) {
                $search = $filters['search'];

                $query->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where(function ($citizenQuery) use ($search) {
                        $citizenQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                });
            })
            ->when($filters['tracking_code'], function ($query) use ($filters) {
                $query->where('tracking_code', 'like', '%' . $filters['tracking_code'] . '%');
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return [
            'requests' => $requests,
            'statuses' => ServiceRequest::statuses(),
            'workflowStates' => ServiceRequest::workflowStates(),
            'municipalities' => Municipality::orderBy('name')->get(),
            'municipalityUsers' => User::with(['governmentOffice'])
                ->whereHas('role', function ($query) {
                    $query->where('role', 'municipality');
                })
                ->orderBy('name')
                ->get(),
            'offices' => GovernmentOffice::with('municipality')->orderBy('name')->get(),
            'services' => Service::with('governmentOffice')->orderBy('name')->get(),
            'categories' => ServiceCategory::with('governmentOffice')->orderBy('name')->get(),
        ];
    }
}
