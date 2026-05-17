<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Municipality;
use App\Models\StripePayment;
use App\Models\User;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'municipality' => ['nullable', 'exists:municipalities,id'],
            'user'         => ['nullable', 'exists:users,id'],
        ]);

        $filters = [
            'municipality' => $validated['municipality'] ?? null,
            'user'         => $validated['user'] ?? null,
        ];

        $municipalities = Municipality::orderBy('name')->get();

        $citizens = User::whereIn('id', StripePayment::distinct()->pluck('user_id'))
            ->orderBy('name')
            ->get();

        $query = StripePayment::with(['user', 'service.governmentOffice.municipality']);

        if ($filters['municipality']) {
            $query->whereHas('service.governmentOffice', function ($q) use ($filters) {
                $q->where('municipality_id', $filters['municipality']);
            });
        }

        if ($filters['user']) {
            $query->where('user_id', $filters['user']);
        }

        $payments = $query->latest()->paginate(20)->withQueryString();

        return view('Admin.payments.index', compact('payments', 'filters', 'municipalities', 'citizens'));
    }
}
