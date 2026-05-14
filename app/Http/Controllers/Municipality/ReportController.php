<?php

namespace App\Http\Controllers\Municipality;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ServiceRequest;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function index()
    {
        $office = Auth::user()->governmentOffice;

        if (!$office) {
            return view('Municipality.NoOffice');
        }

        $requests = ServiceRequest::with(['service.serviceCategory'])
            ->whereHas('service', function ($query) use ($office) {
                $query->where('government_office_id', $office->id);
            })
            ->get();

        $statusData = collect(ServiceRequest::statuses())->map(function ($status) use ($requests) {
            return [
                'label' => $status,
                'count' => $requests->where('status', $status)->count(),
            ];
        });

        $serviceData = $requests
            ->groupBy(fn (ServiceRequest $request) => $request->service?->name ?? 'Unknown Service')
            ->map(fn (Collection $group, string $label) => [
                'label' => $label,
                'count' => $group->count(),
            ])
            ->values()
            ->sortByDesc('count')
            ->values();

        $categoryData = $requests
            ->groupBy(fn (ServiceRequest $request) => $request->service?->serviceCategory?->name ?? 'Uncategorized')
            ->map(fn (Collection $group, string $label) => [
                'label' => $label,
                'count' => $group->count(),
            ])
            ->values()
            ->sortByDesc('count')
            ->values();

        $monthlyData = collect(range(5, 0))->map(function (int $monthsAgo) use ($requests) {
            $month = now()->subMonths($monthsAgo)->format('Y-m');

            return [
                'label' => Carbon::createFromFormat('Y-m', $month)->format('M Y'),
                'count' => $requests->filter(fn (ServiceRequest $request) => $request->created_at?->format('Y-m') === $month)->count(),
            ];
        })->values();

        $revenueRequests = $requests->filter(function (ServiceRequest $request) {
            return in_array($request->status, [
                ServiceRequest::STATUS_APPROVED,
                ServiceRequest::STATUS_COMPLETED,
            ], true);
        });

        $revenueTotal = $revenueRequests->sum(fn (ServiceRequest $request) => (float) ($request->service?->price ?? 0));

        $averageCompletionHours = round(
            $requests
                ->where('status', ServiceRequest::STATUS_COMPLETED)
                ->filter(fn (ServiceRequest $request) => $request->created_at && $request->updated_at)
                ->avg(fn (ServiceRequest $request) => $request->created_at->diffInHours($request->updated_at)),
            1
        );

        $appointmentSummary = [
            'total' => Appointment::where('government_office_id', $office->id)->count(),
            'requested' => Appointment::where('government_office_id', $office->id)->where('status', Appointment::STATUS_REQUESTED)->count(),
            'approved' => Appointment::where('government_office_id', $office->id)->where('status', Appointment::STATUS_APPROVED)->count(),
        ];

        return view('Municipality.reports.index', compact(
            'appointmentSummary',
            'averageCompletionHours',
            'categoryData',
            'monthlyData',
            'office',
            'revenueTotal',
            'requests',
            'serviceData',
            'statusData'
        ));
    }
}
