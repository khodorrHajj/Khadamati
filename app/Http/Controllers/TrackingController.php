<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;

class TrackingController extends Controller
{
    public function show(string $trackingCode)
    {
        $serviceRequest = ServiceRequest::with('service.governmentOffice')
            ->where('tracking_code', $trackingCode)
            ->firstOrFail();

        return view('tracking.show', compact('serviceRequest'));
    }
}
