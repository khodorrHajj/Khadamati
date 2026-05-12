<?php

namespace App\Http\Controllers;

use App\Models\GovernmentOffice;
use App\Models\Municipality;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('Admin.Dashboard');
    }

    public function municipalities()
    {
        $municipalities = Municipality::all();

        return view('Admin.Municipalities', compact('municipalities'));
    }

    public function storeMunicipality(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'address' => 'nullable',
            'phone' => 'nullable',
            'email' => 'nullable|email',
        ]);

        Municipality::create([
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
        ]);

        return redirect()->back()->with('success', 'Municipality created successfully.');
    }

    public function offices()
    {
        $municipalities = Municipality::all();
        $offices = GovernmentOffice::with('municipality')->get();

        return view('Admin.Offices', compact('municipalities', 'offices'));
    }

    public function storeOffice(Request $request)
    {
        $request->validate([
            'municipality_id' => 'required|exists:municipalities,id',
            'name' => 'required',
            'address' => 'nullable',
            'google_maps_location' => 'nullable',
            'working_hours' => 'nullable',
            'contact_info' => 'nullable',
        ]);

        GovernmentOffice::create([
            'municipality_id' => $request->municipality_id,
            'name' => $request->name,
            'address' => $request->address,
            'google_maps_location' => $request->google_maps_location,
            'working_hours' => $request->working_hours,
            'contact_info' => $request->contact_info,
        ]);

        return redirect()->back()->with('success', 'Government office created successfully.');
    }

    public function municipalityUsers()
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

    public function storeMunicipalityUser(Request $request)
    {
        $validated = $request->validate([
            'government_office_id' => ['required', 'exists:government_offices,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^(?:0(?:1|3|5)\d{6}|(?:70|71|76|78|79|81)\d{6}|\+961(?:1|3|5|70|71|76|78|79|81)\d{6})$/',
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ], [
            'phone.regex' => 'Please enter a valid Lebanese phone number.',
        ]);

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

    public function toggleMunicipalityUserStatus(User $user)
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
