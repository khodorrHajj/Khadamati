<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\CitizenServiceRequestService;
use App\Services\CryptoPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CryptoPaymentController extends Controller
{
    public function show(Service $service)
    {
        abort_if(
            !$service->is_active
            || !$service->governmentOffice
            || $service->governmentOffice->status !== 'active',
            404
        );

        abort_if((float) $service->price <= 0, 404);

        $service->load(['governmentOffice.municipality', 'serviceCategory']);

        return view('Citizen.payment.pay', compact('service'));
    }

    public function create(Request $request, Service $service, CryptoPaymentService $cryptoPaymentService)
    {
        abort_if(
            !$service->is_active
            || !$service->governmentOffice
            || $service->governmentOffice->status !== 'active',
            404
        );

        abort_if((float) $service->price <= 0, 404);

        try {
            $paymentUrl = $cryptoPaymentService->initiate($service, Auth::id());
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->withErrors(['payment' => 'Could not initiate payment. Please try again later.']);
        }

        return redirect($paymentUrl);
    }

    public function webhook(Request $request, CryptoPaymentService $cryptoPaymentService, CitizenServiceRequestService $serviceRequestService)
    {
        $cryptoPaymentService->handleWebhook($request, $serviceRequestService);

        return response()->json(['status' => 'ok']);
    }

    public function success()
    {
        return view('Citizen.payment.success');
    }

    public function cancelled()
    {
        return view('Citizen.payment.cancelled');
    }
}
