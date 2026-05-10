<?php

namespace App\Http\Controllers;

use App\Models\GovernmentOffice;
use App\Models\Municipality;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
        $offices = GovernmentOffice::all();

        $users = User::with('governmentOffice', 'role')
            ->whereHas('role', function ($query) {
                $query->where('role', 'municipality');
            })
            ->get();

        return view('Admin.MunicipalityUsers', compact('offices', 'users'));
    }

    public function storeMunicipalityUser(Request $request)
    {
        $request->validate([
            'government_office_id' => 'required|exists:government_offices,id',
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        $municipalityRole = Role::where('role', 'municipality')->first();

        if (!$municipalityRole) {
            return redirect()->back()->withErrors([
                'role' => 'Municipality role does not exist.',
            ]);
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $municipalityRole->id,
            'government_office_id' => $request->government_office_id,
            'is_active' => true,
            'two_factor_enabled' => true,
        ]);

        return redirect()->back()->with('success', 'Municipality user created successfully.');
    }
}