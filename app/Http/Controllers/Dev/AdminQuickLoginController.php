<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminQuickLoginController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        abort_unless(app()->environment('local'), 404);

        $adminRole = Role::firstOrCreate([
            'role' => 'admin',
        ]);

        $user = User::updateOrCreate(
            ['email' => 'local-admin@example.com'],
            [
                'name' => 'Local Admin',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
                'is_active' => true,
                'status' => 'active',
                'email_verified_at' => now(),
                'two_factor_enabled' => false,
                'two_factor_code' => null,
                'two_factor_expires_at' => null,
            ]
        );

        Auth::login($user);
        request()->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }
}
