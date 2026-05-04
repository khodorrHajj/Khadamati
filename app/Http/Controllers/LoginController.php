<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function signup()
    {
        return view('Authentication.Signup');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:6',
        ]);

        $citizenRole = Role::where('role', 'citizen')->first();

        if (!$citizenRole) {
            return redirect()->back()->withErrors([
                'role' => 'Citizen role does not exist. Please insert roles in the database first.',
            ]);
        }

        $user = new User();

        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->role_id = $citizenRole->id;
        $user->is_active = true;

        // These are ready for later pushes.
        $user->two_factor_enabled = false;
        $user->google_id = null;
        $user->avatar = null;

        $user->save();

        return redirect()->route('login')->with('success', 'Account created successfully');
    }

    public function login()
    {
        return view('Authentication.Login');
    }

    public function doLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
            'is_active' => true,
        ];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // testing purpose

            return redirect()->route('home');
        }

        return redirect()->back()->withErrors([
            'email' => 'Invalid email or password',
        ])->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function home()
    {
        return view('Authentication.Home');
    }
}