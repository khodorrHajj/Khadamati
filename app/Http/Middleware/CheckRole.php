<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\IdentityVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($role === 'municipality' && (!$user->is_active || $user->status === 'inactive')) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Your account is inactive.',
                ]);
            }

            if ($role === 'citizen' && (!$user->is_active || $user->status === 'inactive')) {
                if ($user->latestIdentityVerification?->status !== IdentityVerification::STATUS_APPROVED) {
                    return redirect()->route('identity.verification.create')->withErrors([
                        'identity' => 'Your ID verification must be approved before accessing citizen pages.',
                    ]);
                }

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Your account is inactive.',
                ]);
            }

            if ($user->role && $user->role->role === $role) {
                return $next($request);
            } else {
                return abort(403);
            }
        } else {
            return abort(401);
        }
    }
}
