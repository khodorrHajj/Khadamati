<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Socialite;
use Throwable;

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
    if (Auth::user()->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    }

    if (Auth::user()->hasRole('municipality')) {
        return redirect()->route('municipality.dashboard');
    }

    if (Auth::user()->hasRole('citizen')) {
        return redirect()->route('citizen.dashboard');
    }

    return abort(403);
}

    public function redirectToGoogle()
{
    return Socialite::driver('google')->redirect();
}

public function handleGoogleCallback(Request $request)
{
    try {
        $googleUser = Socialite::driver('google')->user();
    } catch (Throwable $e) {
        return redirect()->route('login')->withErrors([
            'google' => 'Google login failed. Please try again.',
        ]);
    }

    $citizenRole = Role::where('role', 'citizen')->first();

    if (!$citizenRole) {
        return redirect()->route('login')->withErrors([
            'role' => 'Citizen role does not exist. Please insert roles in the database first.',
        ]);
    }

    $user = User::where('google_id', $googleUser->getId())->first();

    if (!$user) {
        $user = User::where('email', $googleUser->getEmail())->first();
    }

    if ($user) {
        $user->google_id = $googleUser->getId();
        $user->avatar = $googleUser->getAvatar();
        $user->email_verified_at = now();
        $user->save();
    } else {
        $user = new User();

        $user->name = $googleUser->getName();
        $user->email = $googleUser->getEmail();
        $user->password = null;
        $user->role_id = $citizenRole->id;
        $user->is_active = true;

        $user->google_id = $googleUser->getId();
        $user->avatar = $googleUser->getAvatar();
        $user->email_verified_at = now();

        // Google users do not need our email/password 2FA.
        $user->two_factor_enabled = false;

        $user->save();
    }

    if (!$user->is_active) {
        return redirect()->route('login')->withErrors([
            'email' => 'Your account is deactivated.',
        ]);
    }

    Auth::login($user);

    $request->session()->regenerate();

    return redirect()->route('home');
}
}