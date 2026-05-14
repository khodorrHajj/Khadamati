<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GovernmentOffice;
use App\Models\Municipality;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'municipality' => ['nullable', 'exists:municipalities,id'],
            'office' => ['nullable', 'exists:government_offices,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $filters = [
            'municipality' => $validated['municipality'] ?? null,
            'office' => $validated['office'] ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
        ];

        $municipalities = Municipality::orderBy('name')->get();
        $offices = GovernmentOffice::with('municipality')->orderBy('name')->get();

        $requests = ServiceRequest::with(['service.governmentOffice.municipality', 'service.serviceCategory'])
            ->get();

        $requestsPerOffice = $requests
            ->groupBy(fn (ServiceRequest $request) => $request->service?->governmentOffice?->name ?? 'Unknown Office')
            ->map(fn (Collection $group, string $label) => [
                'label' => $label,
                'count' => $group->count(),
            ])
            ->values()
            ->sortByDesc('count')
            ->values();

        $revenueRequests = $requests
            ->filter(function (ServiceRequest $request) use ($filters) {
                if (!in_array($request->status, [ServiceRequest::STATUS_APPROVED, ServiceRequest::STATUS_COMPLETED], true)) {
                    return false;
                }

                $office = $request->service?->governmentOffice;
                $municipality = $office?->municipality;

                if ($filters['municipality'] && (string) $municipality?->id !== (string) $filters['municipality']) {
                    return false;
                }

                if ($filters['office'] && (string) $office?->id !== (string) $filters['office']) {
                    return false;
                }

                if ($filters['date_from'] && optional($request->updated_at)->toDateString() < $filters['date_from']) {
                    return false;
                }

                if ($filters['date_to'] && optional($request->updated_at)->toDateString() > $filters['date_to']) {
                    return false;
                }

                return true;
            });

        $revenueRows = $revenueRequests
            ->groupBy(function (ServiceRequest $request) {
                $office = $request->service?->governmentOffice;
                $municipality = $office?->municipality;

                return ($municipality?->name ?? 'Unknown Municipality') . '||' . ($office?->name ?? 'Unknown Office');
            })
            ->map(function (Collection $group, string $key) {
                [$municipality, $office] = explode('||', $key);

                return [
                    'municipality' => $municipality,
                    'office' => $office,
                    'revenue' => $group->sum(fn (ServiceRequest $request) => (float) ($request->service?->price ?? 0)),
                ];
            })
            ->values()
            ->sortByDesc('revenue')
            ->values();

        $topServices = $requests
            ->groupBy(fn (ServiceRequest $request) => $request->service?->name ?? 'Unknown Service')
            ->map(fn (Collection $group, string $label) => [
                'label' => $label,
                'count' => $group->count(),
            ])
            ->values()
            ->sortByDesc('count')
            ->take(5)
            ->values();

        $pendingWorkload = $requests
            ->filter(fn (ServiceRequest $request) => in_array($request->status, [
                ServiceRequest::STATUS_PENDING,
                ServiceRequest::STATUS_IN_REVIEW,
                ServiceRequest::STATUS_MISSING_DOCUMENTS,
            ], true))
            ->groupBy(fn (ServiceRequest $request) => $request->service?->governmentOffice?->name ?? 'Unknown Office')
            ->map(fn (Collection $group, string $label) => [
                'label' => $label,
                'count' => $group->count(),
            ])
            ->values()
            ->sortByDesc('count')
            ->values();

        $revenueTotal = $revenueRows->sum('revenue');

        return view('Admin.reports.index', compact(
            'filters',
            'municipalities',
            'offices',
            'pendingWorkload',
            'requestsPerOffice',
            'revenueRows',
            'revenueTotal',
            'topServices'
        ));
    }
}
