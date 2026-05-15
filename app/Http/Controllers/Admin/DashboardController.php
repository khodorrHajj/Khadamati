<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GovernmentOffice;
use App\Models\Municipality;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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

        // Status distribution for doughnut chart
        $statusDistribution = (clone $requests)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Requests over last 30 days (line chart)
        $requestsLast30Days = ServiceRequest::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Fill missing dates with 0
        $dateRange = collect(range(0, 29))->map(fn($i) => now()->subDays(29 - $i)->toDateString());
        $requestsTrend = $dateRange->mapWithKeys(fn($date) => [
            $date => $requestsLast30Days[$date] ?? 0
        ]);

        // Revenue last 30 days
        $revenueLast30Days = ServiceRequest::select(DB::raw('DATE(service_requests.updated_at) as date'), DB::raw('SUM(services.price) as revenue'))
            ->join('services', 'service_requests.service_id', '=', 'services.id')
            ->whereIn('service_requests.status', [ServiceRequest::STATUS_APPROVED, ServiceRequest::STATUS_COMPLETED])
            ->where('service_requests.updated_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('revenue', 'date')
            ->toArray();

        $revenueTrend = $dateRange->mapWithKeys(fn($date) => [
            $date => round((float)($revenueLast30Days[$date] ?? 0), 2)
        ]);

        // Top 5 municipalities by requests
        $topMunicipalities = ServiceRequest::select('municipalities.name', DB::raw('count(*) as count'))
            ->join('services', 'service_requests.service_id', '=', 'services.id')
            ->join('government_offices', 'services.government_office_id', '=', 'government_offices.id')
            ->join('municipalities', 'government_offices.municipality_id', '=', 'municipalities.id')
            ->groupBy('municipalities.name')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'municipalities.name')
            ->toArray();

        // Recent requests (last 8)
        $recentRequests = ServiceRequest::with(['user', 'service.governmentOffice.municipality'])
            ->latest()
            ->limit(8)
            ->get();

        // Citizen count
        $citizenCount = User::whereHas('role', function ($query) {
            $query->where('role', 'citizen');
        })->count();

        return view('Admin.Dashboard', [
            'municipalityCount' => Municipality::count(),
            'officeCount' => GovernmentOffice::count(),
            'municipalityUserCount' => User::whereHas('role', function ($query) {
                $query->where('role', 'municipality');
            })->count(),
            'citizenCount' => $citizenCount,
            'serviceCount' => Service::count(),
            'requestStats' => $requestStats,
            'statusDistribution' => $statusDistribution,
            'requestsTrend' => $requestsTrend,
            'revenueTrend' => $revenueTrend,
            'topMunicipalities' => $topMunicipalities,
            'recentRequests' => $recentRequests,
        ]);
    }
}
