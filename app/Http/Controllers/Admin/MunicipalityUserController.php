<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMunicipalityUserRequest;
use App\Models\GovernmentOffice;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class MunicipalityUserController extends Controller
{
    public function index(): View
    {
        $offices = GovernmentOffice::with('municipality')->get();
        $search = request('search');

        $users = User::with('governmentOffice.municipality', 'role')
            ->whereHas('role', function ($query) {
                $query->where('role', 'municipality');
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('governmentOffice', function ($officeQuery) use ($search) {
                            $officeQuery->where('name', 'like', "%{$search}%")
                                ->orWhereHas('municipality', function ($municipalityQuery) use ($search) {
                                    $municipalityQuery->where('name', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('Admin.MunicipalityUsers', compact('offices', 'users', 'search'));
    }

    public function store(StoreMunicipalityUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $municipalityRole = Role::where('role', 'municipality')->first();

        if (!$municipalityRole) {
            return redirect()->back()->withErrors([
                'role' => 'Municipality role does not exist.',
            ]);
        }

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role_id' => $municipalityRole->id,
            'government_office_id' => $validated['government_office_id'],
            'job_title' => $validated['job_title'] ?? null,
            'status' => $validated['status'],
            'is_active' => $validated['status'] === 'active',
            'two_factor_enabled' => true,
        ]);

        return redirect()->back()->with('success', 'Municipality user created successfully.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        if (!$user->role || $user->role->role !== 'municipality') {
            return redirect()->back()->withErrors([
                'user' => 'Only municipality user accounts can be activated or deactivated here.',
            ]);
        }

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';

        $user->update([
            'status' => $newStatus,
            'is_active' => $newStatus === 'active',
        ]);

        return redirect()->back()->with('success', "Municipality user {$newStatus} successfully.");
    }
}
