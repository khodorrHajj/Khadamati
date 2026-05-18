<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ProfileController extends Controller
{
    private const PASSWORD_CHANGE_SESSION_KEY = 'citizen_password_change_otp';

    public function show(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $pendingPasswordChange = $this->pendingPasswordChange($request, $user);

        return view('Citizen.profile.show', [
            'user' => $user,
            'pendingPasswordChange' => $pendingPasswordChange,
        ]);
    }

    public function sendPasswordOtp(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->password) {
            return redirect()
                ->route('citizen.profile.show')
                ->withErrors([
                    'current_password' => 'Password change is not available for this account.',
                ]);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed', 'different:current_password'],
        ]);

        $code = (string) random_int(100000, 999999);

        $request->session()->put(self::PASSWORD_CHANGE_SESSION_KEY, [
            'user_id' => $user->id,
            'email' => $user->email,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10)->toDateTimeString(),
            'new_password_hash' => Hash::make($validated['new_password']),
        ]);

        $this->sendPasswordChangeCode($user, $code);

        return redirect()
            ->route('citizen.profile.show')
            ->with('success', 'A verification code was sent to your email. Enter it below to confirm your new password.');
    }

    public function confirmPasswordOtp(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $pendingPasswordChange = $this->pendingPasswordChange($request, $user);

        if (!$pendingPasswordChange) {
            return redirect()
                ->route('citizen.profile.show')
                ->withErrors([
                    'password_otp' => 'No password change request was found. Please start again.',
                ]);
        }

        $validated = $request->validate([
            'password_otp' => ['required', 'digits:6'],
        ]);

        if (now()->greaterThan($pendingPasswordChange['expires_at'])) {
            $request->session()->forget(self::PASSWORD_CHANGE_SESSION_KEY);

            return redirect()
                ->route('citizen.profile.show')
                ->withErrors([
                    'password_otp' => 'The verification code has expired. Please request a new one.',
                ]);
        }

        if (!Hash::check($validated['password_otp'], $pendingPasswordChange['code_hash'])) {
            return redirect()
                ->route('citizen.profile.show')
                ->withErrors([
                    'password_otp' => 'Invalid verification code.',
                ]);
        }

        $user->update([
            'password' => $pendingPasswordChange['new_password_hash'],
        ]);

        $request->session()->forget(self::PASSWORD_CHANGE_SESSION_KEY);

        return redirect()
            ->route('citizen.profile.show')
            ->with('success', 'Your password was updated successfully.');
    }

    public function resendPasswordOtp(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $pendingPasswordChange = $this->pendingPasswordChange($request, $user);

        if (!$pendingPasswordChange) {
            return redirect()
                ->route('citizen.profile.show')
                ->withErrors([
                    'password_otp' => 'No password change request was found. Please start again.',
                ]);
        }

        $code = (string) random_int(100000, 999999);

        $request->session()->put(self::PASSWORD_CHANGE_SESSION_KEY, [
            'user_id' => $user->id,
            'email' => $user->email,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10)->toDateTimeString(),
            'new_password_hash' => $pendingPasswordChange['new_password_hash'],
        ]);

        $this->sendPasswordChangeCode($user, $code);

        return redirect()
            ->route('citizen.profile.show')
            ->with('success', 'A new verification code was sent to your email.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->password) {
            return redirect()
                ->route('citizen.profile.show')
                ->withErrors([
                    'delete_password' => 'Account deletion is not available for this account.',
                ]);
        }

        $request->validate([
            'delete_password' => ['required', 'current_password'],
        ]);

        DB::transaction(function () use ($user) {
            $user->delete();
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'Your account was deleted successfully.');
    }

    private function pendingPasswordChange(Request $request, User $user): ?array
    {
        $pendingPasswordChange = $request->session()->get(self::PASSWORD_CHANGE_SESSION_KEY);

        if (!is_array($pendingPasswordChange) || ($pendingPasswordChange['user_id'] ?? null) !== $user->id) {
            return null;
        }

        return $pendingPasswordChange;
    }

    private function sendPasswordChangeCode(User $user, string $code): void
    {
        Mail::raw("Your Khadamati password change code is: {$code}", function ($message) use ($user) {
            $message->to($user->email);
            $message->subject('Your Khadamati password change code');
        });
    }
}
