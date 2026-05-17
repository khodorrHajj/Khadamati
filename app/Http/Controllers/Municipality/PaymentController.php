<?php

namespace App\Http\Controllers\Municipality;

use App\Http\Controllers\Controller;
use App\Models\StripePayment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $office = Auth::user()->governmentOffice;

        if (!$office) {
            return view('Municipality.NoOffice');
        }

        $validated = $request->validate([
            'user' => ['nullable', 'exists:users,id'],
        ]);

        $filters = [
            'user' => $validated['user'] ?? null,
        ];

        $citizens = User::whereIn('id',
            StripePayment::whereHas('service', fn ($q) => $q->where('government_office_id', $office->id))
                ->distinct()
                ->pluck('user_id')
        )->orderBy('name')->get();

        $query = StripePayment::with(['user', 'service'])
            ->whereHas('service', fn ($q) => $q->where('government_office_id', $office->id));

        if ($filters['user']) {
            $query->where('user_id', $filters['user']);
        }

        $payments = $query->latest()->paginate(20)->withQueryString();

        return view('Municipality.payments.index', compact('payments', 'filters', 'citizens', 'office'));
    }
}
