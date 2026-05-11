<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckTwoFactorSession
{
    public function handle(Request $request, Closure $next)
    {
        if (
            !$request->session()->has('two_factor_user_id') &&
            !$request->session()->has('pending_registration')
        ) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
