<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;

abstract class Controller
{
    use AuthorizesRequests;

    protected function authorizeOrAbort(string $ability, mixed $arguments = []): void
    {
        $response = Gate::inspect($ability, $arguments);

        if ($response->allowed()) {
            return;
        }

        abort($response->status() ?? 403, $response->message() ?: 'This action is unauthorized.');
    }
}
