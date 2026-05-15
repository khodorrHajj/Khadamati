<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\GovernmentOffice;
use App\Models\Municipality;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MunicipalityQuickLoginController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        abort_unless(app()->environment('local'), 404);

        $municipalityRole = Role::firstOrCreate([
            'role' => 'municipality',
        ]);

        $municipality = Municipality::firstOrCreate(
            ['name' => 'Local Test Municipality'],
            [
                'status' => 'active',
                'city' => 'Beirut',
                'address' => 'Local Test Municipality, Beirut',
            ]
        );

        $office = GovernmentOffice::firstOrCreate(
            ['email' => 'local-office@example.com'],
            [
                'municipality_id' => $municipality->id,
                'name' => 'Local Test Office',
                'status' => 'active',
                'city' => 'Beirut',
                'address' => 'Local Test Office, Beirut',
                'contact_info' => '01-000000',
            ]
        );

        if ($office->municipality_id !== $municipality->id) {
            $office->municipality_id = $municipality->id;
            $office->save();
        }

        $user = User::updateOrCreate(
            ['email' => 'local-municipality@example.com'],
            [
                'name' => 'Local Municipality User',
                'password' => Hash::make('password'),
                'role_id' => $municipalityRole->id,
                'government_office_id' => $office->id,
                'is_active' => true,
                'status' => 'active',
                'job_title' => 'Municipality Officer',
                'email_verified_at' => now(),
                'two_factor_enabled' => false,
                'two_factor_code' => null,
                'two_factor_expires_at' => null,
            ]
        );

        Auth::login($user);
        request()->session()->regenerate();

        return redirect()->route('municipality.dashboard');
    }
}
