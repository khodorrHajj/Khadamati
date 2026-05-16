<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IdentityVerification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class IdentityVerificationController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:' . implode(',', IdentityVerification::statuses())],
        ]);

        $search = $validated['search'] ?? null;
        $status = $validated['status'] ?? null;

        $verifications = IdentityVerification::with('user.role')
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($verificationQuery) use ($search) {
                    $verificationQuery->where('extracted_full_name', 'like', "%{$search}%")
                        ->orWhere('extracted_first_name', 'like', "%{$search}%")
                        ->orWhere('extracted_family_name', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $statuses = IdentityVerification::statuses();

        return view('Admin.identity-verifications.index', compact('search', 'status', 'statuses', 'verifications'));
    }

    public function show(IdentityVerification $verification): View
    {
        $verification->load('user.role', 'reviewer');
        $imageExists = $verification->id_image_path
            && Storage::disk('public')->exists($verification->id_image_path);

        return view('Admin.identity-verifications.show', compact('verification', 'imageExists'));
    }

    public function image(IdentityVerification $verification): StreamedResponse
    {
        abort_if(!$verification->id_image_path || !Storage::disk('public')->exists($verification->id_image_path), 404, 'The uploaded ID image could not be found.');

        return Storage::disk('public')->response($verification->id_image_path);
    }

    public function approve(IdentityVerification $verification): RedirectResponse
    {
        $verification->load('user.role');
        $this->ensureCitizenVerification($verification);

        $verification->update([
            'status' => IdentityVerification::STATUS_APPROVED,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'admin_notes' => request('admin_notes'),
        ]);

        $verification->user->update([
            'status' => 'active',
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.identity-verifications.show', $verification)
            ->with('success', 'Identity verification approved and citizen account activated.');
    }

    public function reject(Request $request, IdentityVerification $verification): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => ['required', 'string', 'max:2000'],
        ]);

        $verification->load('user.role');
        $this->ensureCitizenVerification($verification);

        $verification->update([
            'status' => IdentityVerification::STATUS_REJECTED,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'admin_notes' => $validated['admin_notes'],
        ]);

        $verification->user->update([
            'status' => 'inactive',
            'is_active' => false,
        ]);

        return redirect()
            ->route('admin.identity-verifications.show', $verification)
            ->with('success', 'Identity verification rejected.');
    }

    private function ensureCitizenVerification(IdentityVerification $verification): void
    {
        abort_if(!$verification->user || !$verification->user->role || $verification->user->role->role !== 'citizen', 404);
    }
}
