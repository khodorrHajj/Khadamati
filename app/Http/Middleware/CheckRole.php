<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (Auth::check()) {
            if (Auth::user()->role && Auth::user()->role->role === $role) {
                return $next($request);
            } else {
                return abort(403);
            }
        } else {
            return abort(401);
        }
    }
}