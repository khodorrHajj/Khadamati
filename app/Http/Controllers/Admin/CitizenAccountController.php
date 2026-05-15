<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CitizenAccountController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $search = $validated['search'] ?? null;

        $citizens = User::with('role')
            ->whereHas('role', function ($query) {
                $query->where('role', 'citizen');
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($citizenQuery) use ($search) {
                    $citizenQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('Admin.Citizens', compact('citizens', 'search'));
    }

    public function show(User $citizen): View
    {
        $this->ensureCitizenAccount($citizen);

        $citizen->load(['serviceRequests.service.governmentOffice.municipality', 'feedback']);

        $requestStats = [
            'total' => $citizen->serviceRequests()->count(),
            'pending' => $citizen->serviceRequests()->where('status', 'Pending')->count(),
            'in_review' => $citizen->serviceRequests()->where('status', 'In Review')->count(),
            'approved' => $citizen->serviceRequests()->where('status', 'Approved')->count(),
            'rejected' => $citizen->serviceRequests()->where('status', 'Rejected')->count(),
            'completed' => $citizen->serviceRequests()->where('status', 'Completed')->count(),
        ];

        $recentRequests = $citizen->serviceRequests()
            ->with('service.governmentOffice.municipality')
            ->latest()
            ->limit(10)
            ->get();

        return view('Admin.citizens.show', compact('citizen', 'requestStats', 'recentRequests'));
    }

    public function destroy(User $citizen): RedirectResponse
    {
        $this->ensureCitizenAccount($citizen);

        $citizen->delete();

        return redirect()->route('admin.citizens.index')
            ->with('success', 'Citizen account deleted successfully.');
    }

    public function activate(User $citizen): RedirectResponse
    {
        $this->ensureCitizenAccount($citizen);

        $citizen->update([
            'status' => 'active',
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Citizen account activated successfully.');
    }

    public function deactivate(User $citizen): RedirectResponse
    {
        $this->ensureCitizenAccount($citizen);

        $citizen->update([
            'status' => 'inactive',
            'is_active' => false,
        ]);

        return redirect()->back()->with('success', 'Citizen account deactivated successfully.');
    }

    private function ensureCitizenAccount(User $user): void
    {
        abort_if(!$user->role || $user->role->role !== 'citizen', 404);
    }
}
