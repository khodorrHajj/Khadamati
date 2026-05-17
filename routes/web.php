<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/dev-login-admin', function () {
    if (!app()->isLocal()) {
        abort(404);
    }

    $adminRole = Role::where('role', 'admin')->first();

    if (!$adminRole) {
        return 'Admin role not found. Please run database migrations.';
    }

    $admin = User::where('role_id', $adminRole->id)->first();

    if (!$admin) {
        return 'No admin user found. Please create an admin user in the database. Current role_id: ' . $adminRole->id;
    }

    Auth::login($admin);
    request()->session()->save();

    return redirect()->route('admin.dashboard');
})->name('dev.login.admin');

Route::get('/dev-login-municipality', function () {
    if (!app()->isLocal()) {
        abort(404);
    }

    $municipalityRole = Role::where('role', 'municipality')->first();

    if (!$municipalityRole) {
        return 'Municipality role not found. Please run database migrations.';
    }

    $municipality = User::where('role_id', $municipalityRole->id)->first();

    if (!$municipality) {
        return 'No municipality user found. Please create a municipality user in the database. Current role_id: ' . $municipalityRole->id;
    }

    if (!$municipality->government_office_id) {
        $municipalityModel = \App\Models\Municipality::first();
        
        if (!$municipalityModel) {
            $municipalityModel = \App\Models\Municipality::create([
                'name' => 'Test Municipality',
                'city' => 'Test City',
                'status' => 'active',
            ]);
        }

        $office = \App\Models\GovernmentOffice::create([
            'municipality_id' => $municipalityModel->id,
            'name' => 'Main Office',
            'service_type' => 'general',
            'city' => 'Test City',
            'status' => 'active',
            'address' => '123 Test Street',
        ]);

        $municipality->government_office_id = $office->id;
        $municipality->save();
    }

    Auth::login($municipality);
    request()->session()->save();

    return redirect()->route('municipality.dashboard');
})->name('dev.login.municipality');

Route::get('/dev-login-citizen', function () {
    if (!app()->isLocal()) {
        abort(404);
    }

    $citizenRole = Role::where('role', 'citizen')->first();

    if (!$citizenRole) {
        return 'Citizen role not found. Please run database migrations.';
    }

    $citizen = User::where('role_id', $citizenRole->id)->first();

    if (!$citizen) {
        $citizen = User::create([
            'name' => 'Dev Citizen',
            'email' => 'dev-citizen@local.test',
            'password' => bcrypt('password'),
            'role_id' => $citizenRole->id,
            'email_verified_at' => now(),
            'is_active' => true,
            'status' => 'active',
        ]);
    } else {
        $citizen->update(['is_active' => true, 'status' => 'active']);
    }

    // Ensure an approved identity verification exists so middleware passes
    \App\Models\IdentityVerification::firstOrCreate(
        ['user_id' => $citizen->id],
        ['status' => \App\Models\IdentityVerification::STATUS_APPROVED]
    );

    // If it exists but isn't approved, force-approve it
    \App\Models\IdentityVerification::where('user_id', $citizen->id)
        ->where('status', '!=', \App\Models\IdentityVerification::STATUS_APPROVED)
        ->update(['status' => \App\Models\IdentityVerification::STATUS_APPROVED]);

    Auth::login($citizen);
    request()->session()->save();

    return redirect()->route('citizen.dashboard');
})->name('dev.login.citizen');

require __DIR__ . '/auth.php';
require __DIR__ . '/admin.php';
require __DIR__ . '/municipality.php';
require __DIR__ . '/citizen.php';
