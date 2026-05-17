<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IdentityVerification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $verifications = IdentityVerification::with('user.role', 'reviewer')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($verificationQuery) use ($search) {
                    $verificationQuery->where('extracted_first_name', 'like', "%{$search}%")
                        ->orWhere('extracted_family_name', 'like', "%{$search}%")
                        ->orWhere('extracted_father_name', 'like', "%{$search}%")
                        ->orWhere('extracted_mother_name', 'like', "%{$search}%")
                        ->orWhere('extracted_mother_family_name', 'like', "%{$search}%")
                        ->orWhere('extracted_full_name', 'like', "%{$search}%")
                        ->orWhere('extracted_place_of_birth', 'like', "%{$search}%")
                        ->orWhere('extracted_date_of_birth_text', 'like', "%{$search}%")
                        ->orWhere('extracted_id_number', 'like', "%{$search}%")
                        ->orWhere('extracted_gender', 'like', "%{$search}%")
                        ->orWhere('extracted_marital_status', 'like', "%{$search}%")
                        ->orWhere('extracted_record_number', 'like', "%{$search}%")
                        ->orWhere('extracted_locality', 'like', "%{$search}%")
                        ->orWhere('extracted_governorate', 'like', "%{$search}%")
                        ->orWhere('extracted_district', 'like', "%{$search}%")
                        ->orWhere('extracted_blood_type', 'like', "%{$search}%")
                        ->orWhere('extracted_issue_date_text', 'like', "%{$search}%")
                        ->orWhere('admin_notes', 'like', "%{$search}%")
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
        $frontImageExists = $verification->id_image_path
            && Storage::disk('public')->exists($verification->id_image_path);
        $backImageExists = $verification->id_image_back_path
            && Storage::disk('public')->exists($verification->id_image_back_path);

        return view('Admin.identity-verifications.show', compact('verification', 'frontImageExists', 'backImageExists'));
    }

    public function image(Request $request, IdentityVerification $verification): StreamedResponse
    {
        $side = $request->query('side') === 'back' ? 'back' : 'front';
        $path = $side === 'back' ? $verification->id_image_back_path : $verification->id_image_path;

        abort_if(
            !$path || !Storage::disk('public')->exists($path),
            404,
            'The uploaded ID image could not be found.'
        );

        return Storage::disk('public')->response($path);
    }

    public function approve(Request $request, IdentityVerification $verification): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $verification->load('user');
        $user = $verification->user;

        abort_if(!$user, 404, 'The citizen account for this verification could not be found.');

        $user->update([
            'is_active' => true,
            'status' => 'active',
            'email_verified_at' => $user->email_verified_at ?? now(),
        ]);

        $verification->update([
            'status' => IdentityVerification::STATUS_APPROVED,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'admin_notes' => $validated['admin_notes'] ?? null,
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

        $verification->load('user');

        if ($verification->user) {
            $verification->user->update([
                'is_active' => false,
                'status' => 'inactive',
            ]);
        }

        $verification->update([
            'status' => IdentityVerification::STATUS_REJECTED,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'admin_notes' => $validated['admin_notes'],
        ]);

        return redirect()
            ->route('admin.identity-verifications.show', $verification)
            ->with('success', 'Identity verification rejected.');
    }
}
