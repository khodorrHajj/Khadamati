<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\CitizenServiceRequestService;
use App\Services\StripePaymentService;
use App\Support\LebaneseCurrency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StripePaymentController extends Controller
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

        $priceUsd    = LebaneseCurrency::toUsd((float) $service->price);
        $exchangeRate = LebaneseCurrency::getRate();

        return view('Citizen.payment.pay', compact('service', 'priceUsd', 'exchangeRate'));
    }

    public function create(Request $request, Service $service, StripePaymentService $stripePaymentService)
    {
        abort_if(
            !$service->is_active
            || !$service->governmentOffice
            || $service->governmentOffice->status !== 'active',
            404
        );

        abort_if((float) $service->price <= 0, 404);

        try {
            $paymentUrl = $stripePaymentService->initiate($service, Auth::id());
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['payment' => 'Could not initiate payment. Please try again later.']);
        }

        return redirect($paymentUrl);
    }

    public function webhook(Request $request, StripePaymentService $stripePaymentService, CitizenServiceRequestService $serviceRequestService)
    {
        $stripePaymentService->handleWebhook($request, $serviceRequestService);

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
