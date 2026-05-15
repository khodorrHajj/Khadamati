<?php

namespace App\Http\Controllers;

use App\Http\Requests\IdentityVerificationUploadRequest;
use App\Models\IdentityVerification;
use App\Services\IdentityVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class IdentityVerificationController extends Controller
{
    public function create(): View
    {
        $user = Auth::user();
        abort_if(!$user || !$user->hasRole('citizen'), 403);

        $verification = $user->latestIdentityVerification;

        return view('Authentication.IdentityVerification', compact('verification'));
    }

    public function store(
        IdentityVerificationUploadRequest $request,
        IdentityVerificationService $verificationService
    ): RedirectResponse {
        $user = Auth::user();
        abort_if(!$user || !$user->hasRole('citizen'), 403);

        $latest = $user->latestIdentityVerification;

        if ($latest && $latest->status === IdentityVerification::STATUS_APPROVED) {
            return redirect()->route('citizen.dashboard');
        }

        $verificationService->submit($user, $request->file('id_image'));

        return redirect()
            ->route('identity.verification.create')
            ->with('success', 'Your ID was uploaded and sent for admin review.');
    }
}
