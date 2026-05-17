<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\StripePayment;
use Illuminate\Support\Facades\Auth;

class PaymentHistoryController extends Controller
{
    public function index()
    {
        $payments = StripePayment::with(['service.governmentOffice'])
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(15);

        return view('Citizen.payment.history', compact('payments'));
    }
}
