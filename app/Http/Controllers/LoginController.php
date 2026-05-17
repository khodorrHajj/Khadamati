<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\IdentityVerification;
use App\Models\NationalId;
use App\Models\PendingRegistration;
use App\Services\IdentityOcrService;
use App\Services\LebaneseNationalIdParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Laravel\Socialite\Socialite;
use Throwable;

class LoginController extends Controller
{
    public function signup()
    {
        return view('Authentication.Signup');
    }

    public function register(Request $request, IdentityOcrService $ocrService, LebaneseNationalIdParser $parser)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('pending_registrations', 'email'),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'confirmed', 'min:6'],
            'id_image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $citizenRole = Role::where('role', 'citizen')->first();

        if (!$citizenRole) {
            return redirect()->back()->withErrors([
                'role' => 'Citizen role does not exist. Please insert roles in the database first.',
            ]);
        }

        $imagePath = $request->file('id_image')->store('identity-verifications', 'public');
        $ocr = $ocrService->analyze($imagePath);
        $rawText = $ocr['text'] ?? '';
        $fields = $parser->parse($rawText);
        $normalizedNationalId = $fields['national_id_number_normalized'] ?? null;

        if (
            $normalizedNationalId
            && NationalId::where('national_id_number_normalized', $normalizedNationalId)
                ->whereIn('status', NationalId::statuses())
                ->exists()
        ) {
            return redirect()->back()
                ->withInput($request->except(['password', 'password_confirmation', 'id_image']))
                ->withErrors([
                    'id_image' => 'This national ID is already registered or pending review.',
                ]);
        }

        DB::transaction(function () use ($validated, $imagePath, $ocr, $rawText, $fields) {
            $nationalId = NationalId::create([
                'national_id_number' => $fields['national_id_number'] ?? null,
                'national_id_number_normalized' => $fields['national_id_number_normalized'] ?? null,
                'first_name_ar' => $fields['first_name_ar'] ?? null,
                'family_name_ar' => $fields['family_name_ar'] ?? null,
                'father_name_ar' => $fields['father_name_ar'] ?? null,
                'mother_name_ar' => $fields['mother_name_ar'] ?? null,
                'place_of_birth_ar' => $fields['place_of_birth_ar'] ?? null,
                'date_of_birth_text' => $fields['date_of_birth_text'] ?? null,
                'raw_ocr_text' => $rawText,
                'id_image_path' => $imagePath,
                'ocr_confidence' => $ocr['confidence'] ?? null,
                'status' => NationalId::STATUS_PENDING_REVIEW,
            ]);

            $pendingRegistration = PendingRegistration::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'national_id_id' => $nationalId->id,
                'status' => PendingRegistration::STATUS_PENDING_REVIEW,
            ]);

            $nationalId->update([
                'pending_registration_id' => $pendingRegistration->id,
            ]);
        });

        return redirect()->route('login')
            ->with('success', 'Your registration is pending admin ID verification.');
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

        $inactiveCitizen = User::with('role', 'latestIdentityVerification')
            ->where('email', $request->email)
            ->first();

        if (
            $inactiveCitizen
            && Hash::check($request->password, (string) $inactiveCitizen->password)
            && $inactiveCitizen->hasRole('citizen')
            && !$inactiveCitizen->is_active
            && $inactiveCitizen->latestIdentityVerification?->status !== IdentityVerification::STATUS_APPROVED
        ) {
            Auth::login($inactiveCitizen);
            $request->session()->regenerate();

            return redirect()->route('identity.verification.create');
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

        if ($request->session()->has('pending_registration')) {
            return $this->verifyPendingRegistration($request);
        }

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
        if ($request->session()->has('pending_registration')) {
            $pendingRegistration = $request->session()->get('pending_registration');
            $code = random_int(100000, 999999);

            $pendingRegistration['code'] = Hash::make($code);
            $pendingRegistration['expires_at'] = now()->addMinutes(10)->toDateTimeString();

            $request->session()->put('pending_registration', $pendingRegistration);

            Mail::raw("Your verification code is: " . $code, function ($message) use ($pendingRegistration) {
                $message->to($pendingRegistration['email']);
                $message->subject('Verify Your E-Services Account');
            });

            return redirect()->back()->with('success', 'A new verification code was sent.');
        }

        $userId = $request->session()->get('two_factor_user_id');

        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('login');
        }

        $this->sendTwoFactorCode($user);

        return redirect()->back()->with('success', 'A new verification code was sent.');
    }

    private function verifyPendingRegistration(Request $request)
    {
        $pendingRegistration = $request->session()->get('pending_registration');

        if (now()->greaterThan(\Carbon\Carbon::parse($pendingRegistration['expires_at']))) {
            return redirect()->back()->withErrors([
                'code' => 'The verification code has expired. Please request a new one.',
            ]);
        }

        if (!Hash::check($request->code, $pendingRegistration['code'])) {
            return redirect()->back()->withErrors([
                'code' => 'Invalid verification code.',
            ]);
        }

        if (User::where('email', $pendingRegistration['email'])->exists()) {
            $request->session()->forget('pending_registration');

            return redirect()->route('signup')->withErrors([
                'email' => 'An account with this email already exists.',
            ]);
        }

        $user = new User();

        $user->name = $pendingRegistration['name'];
        $user->email = $pendingRegistration['email'];
        $user->password = $pendingRegistration['password'];
        $user->role_id = $pendingRegistration['role_id'];
        $user->is_active = false;
        $user->status = 'inactive';
        $user->email_verified_at = now();
        $user->google_id = null;
        $user->avatar = null;
        $user->two_factor_enabled = true;

        $user->save();

        $request->session()->forget('pending_registration');

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('identity.verification.create')
            ->with('success', 'Email verified. Please upload your ID to complete account verification.');
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
        $existingEmailUser = User::where('email', $googleUser->getEmail())->first();

        if (!$user && $existingEmailUser) {
            if ($existingEmailUser->role_id !== $citizenRole->id) {
                return redirect()->route('login')->withErrors([
                    'google' => 'This Google account email is already registered with a different user type.',
                ]);
            }

            $user = $existingEmailUser;
        }

        if ($user) {
            $user->google_id = $googleUser->getId();
            $user->avatar = $googleUser->getAvatar();
            $user->email_verified_at = now();
            $user->two_factor_enabled = false;
            $user->save();
        } else {
            $user = new User();
            $user->name = $googleUser->getName();
            $user->email = $googleUser->getEmail();
            $user->password = null;
            $user->role_id = $citizenRole->id;
            $user->is_active = false;
            $user->status = 'inactive';
            $user->google_id = $googleUser->getId();
            $user->avatar = $googleUser->getAvatar();
            $user->email_verified_at = now();
            $user->two_factor_enabled = false;
            $user->save();
        }

        if (!$user->is_active && (!$user->hasRole('citizen') || $user->latestIdentityVerification?->status === IdentityVerification::STATUS_APPROVED)) {
            return redirect()->route('login')->withErrors([
                'email' => 'Your account is deactivated.',
            ]);
        }

        Auth::login($user);

        $request->session()->regenerate();

        if ($user->hasRole('citizen') && !$user->is_active) {
            return redirect()->route('identity.verification.create');
        }

        return redirect()->route('home');
    }
}
