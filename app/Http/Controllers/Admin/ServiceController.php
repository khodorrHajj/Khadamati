<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GovernmentOffice;
use App\Models\Municipality;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $query = Service::with(['governmentOffice.municipality', 'serviceCategory']);

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

        // Filter by category
        if ($request->filled('category')) {
            $query->where('service_category_id', $request->category);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $services = $query->orderBy('name')->paginate(20)->withQueryString();

        $municipalities = Municipality::orderBy('name')->get();
        $offices = GovernmentOffice::with('municipality')->orderBy('name')->get();
        $categories = ServiceCategory::orderBy('name')->get();

        return view('Admin.services.index', compact(
            'services',
            'municipalities',
            'offices',
            'categories'
        ));
    }

    public function show(Service $service): View
    {
        $service->load(['governmentOffice.municipality', 'serviceCategory', 'serviceRequests.user']);

        $stats = [
            'total_requests' => $service->serviceRequests()->count(),
            'pending' => $service->serviceRequests()->where('status', 'Pending')->count(),
            'in_review' => $service->serviceRequests()->where('status', 'In Review')->count(),
            'approved' => $service->serviceRequests()->where('status', 'Approved')->count(),
            'rejected' => $service->serviceRequests()->where('status', 'Rejected')->count(),
            'completed' => $service->serviceRequests()->where('status', 'Completed')->count(),
        ];

        $recentRequests = $service->serviceRequests()
            ->with('user')
            ->latest()
            ->limit(10)
            ->get();

        return view('Admin.services.show', compact('service', 'stats', 'recentRequests'));
    }

    public function toggleStatus(Service $service)
    {
        $service->update(['is_active' => !$service->is_active]);

        $status = $service->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Service \"{$service->name}\" has been {$status}.");
    }
}
