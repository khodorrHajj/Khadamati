<?php

namespace App\Http\Controllers;

use App\Http\Requests\IdentityVerificationUploadRequest;
use App\Models\IdentityVerification;
use App\Services\IdentityVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class IdentityVerificationController extends Controller
{
    public function create(): View|RedirectResponse
    {
        $user = Auth::user();
        abort_if(!$user || !$user->hasRole('citizen'), 403);

        $verification = $user->latestIdentityVerification;

        if ($user->is_active || $verification?->status === IdentityVerification::STATUS_APPROVED) {
            return redirect()->route('citizen.dashboard');
        }

        return view('Authentication.IdentityVerification', compact('verification'));
    }

    public function status(): JsonResponse
    {
        $user = Auth::user();
        abort_if(!$user || !$user->hasRole('citizen'), 403);

        $verification = $user->fresh()->latestIdentityVerification;
        $shouldRedirect = (bool) $user->fresh()->is_active
            || $verification?->status === IdentityVerification::STATUS_APPROVED;

        return response()->json([
            'status' => $verification?->status,
            'is_active' => (bool) $user->fresh()->is_active,
            'message' => $this->statusMessage($verification),
            'should_redirect' => $shouldRedirect,
            'redirect_url' => $shouldRedirect ? route('citizen.dashboard') : null,
        ]);
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

        $verificationService->submit(
            $user,
            $request->file('id_image_front'),
            $request->file('id_image_back')
        );

        return redirect()
            ->route('identity.verification.create')
            ->with('success', 'Your ID was uploaded. OCR processing is running in the background and will be sent for admin review shortly.');
    }

    private function statusMessage(?IdentityVerification $verification): string
    {
        if (!$verification) {
            return 'Upload your ID to complete signup.';
        }

        return match ($verification->status) {
            IdentityVerification::STATUS_PROCESSING => 'Your ID is getting processed.',
            IdentityVerification::STATUS_NEEDS_REVIEW => 'Your ID is getting processed.',
            IdentityVerification::STATUS_REJECTED => 'Your ID verification was rejected. Please review the admin note and upload new images.',
            IdentityVerification::STATUS_FAILED => 'We could not process your ID automatically. Please upload the images again.',
            default => 'Your ID is getting processed.',
        };
    }
}
