<?php

namespace App\Services;

use App\Models\GovernmentOffice;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;

class MunicipalityRequestListingService
{
    public function build(GovernmentOffice $office, array $filters): array
    {
        $services = Service::with('serviceCategory')
            ->where('government_office_id', $office->id)
            ->orderBy('name')
            ->get();

        $categories = ServiceCategory::where('government_office_id', $office->id)
            ->orderBy('name')
            ->get();

        $requests = ServiceRequest::with(['user', 'service.serviceCategory'])
            ->withCount('requestDocuments')
            ->whereHas('service', function ($query) use ($office) {
                $query->where('government_office_id', $office->id);
            })
            ->when($filters['status'], function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when($filters['service'], function ($query) use ($filters) {
                $query->where('service_id', $filters['service']);
            })
            ->when($filters['category'], function ($query) use ($filters) {
                $query->whereHas('service.serviceCategory', function ($categoryQuery) use ($filters) {
                    $categoryQuery->where('id', $filters['category']);
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
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return [
            'categories' => $categories,
            'requests' => $requests,
            'services' => $services,
            'statuses' => ServiceRequest::statuses(),
        ];
    }
}
