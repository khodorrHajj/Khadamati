<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

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

        // Email/password users will use 2FA.
        $user->two_factor_enabled = true;

        // Google login fields for later.
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

            $user = Auth::user();

            if ($user->two_factor_enabled) {
                $this->sendTwoFactorCode($user);

                Auth::logout();

                $request->session()->put('two_factor_user_id', $user->id);

                return redirect()->route('twofactor.form')
                    ->with('success', 'A verification code was sent to your email.');
            }

            return redirect()->route('home');
        }

        return redirect()->back()->withErrors([
            'email' => 'Invalid email or password',
        ])->withInput();
    }

    public function twoFactorForm()
    {
        return view('Authentication.Verify2fa');
    }

    public function verifyTwoFactor(Request $request)
    {
        $request->validate([
            'code' => 'required|digits:6',
        ]);

        $userId = $request->session()->get('two_factor_user_id');

        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->two_factor_code || !$user->two_factor_expires_at) {
            return redirect()->route('login')->withErrors([
                'code' => 'No verification code found. Please login again.',
            ]);
        }

        if (now()->greaterThan($user->two_factor_expires_at)) {
            return redirect()->back()->withErrors([
                'code' => 'The verification code has expired. Please request a new one.',
            ]);
        }

        if (!Hash::check($request->code, $user->two_factor_code)) {
            return redirect()->back()->withErrors([
                'code' => 'Invalid verification code.',
            ]);
        }

        $user->two_factor_code = null;
        $user->two_factor_expires_at = null;
        $user->save();

        Auth::loginUsingId($user->id);

        $request->session()->forget('two_factor_user_id');
        $request->session()->regenerate();

        return redirect()->route('home');
    }

    public function resendTwoFactor(Request $request)
    {
        $userId = $request->session()->get('two_factor_user_id');

        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('login');
        }

        $this->sendTwoFactorCode($user);

        return redirect()->back()->with('success', 'A new verification code was sent.');
    }

    private function sendTwoFactorCode(User $user)
    {
        $code = random_int(100000, 999999);

        $user->two_factor_code = Hash::make($code);
        $user->two_factor_expires_at = now()->addMinutes(10);
        $user->save();

        Mail::raw("Your verification code is: " . $code, function ($message) use ($user) {
            $message->to($user->email);
            $message->subject('Your E-Services Verification Code');
        });
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->forget('two_factor_user_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function home()
    {
        return view('Authentication.Home');
    }
}