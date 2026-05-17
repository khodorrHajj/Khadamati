<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NationalId;
use App\Models\PendingRegistration;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IdentityVerificationController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:' . implode(',', NationalId::statuses())],
        ]);

        $search = $validated['search'] ?? null;
        $status = $validated['status'] ?? null;

        $verifications = NationalId::with('pendingRegistration', 'uploadedBy.role', 'reviewedBy')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($verificationQuery) use ($search) {
                    $verificationQuery->where('national_id_number', 'like', "%{$search}%")
                        ->orWhere('national_id_number_normalized', 'like', "%{$search}%")
                        ->orWhere('first_name_ar', 'like', "%{$search}%")
                        ->orWhere('family_name_ar', 'like', "%{$search}%")
                        ->orWhereHas('pendingRegistration', function ($pendingQuery) use ($search) {
                            $pendingQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $statuses = NationalId::statuses();

        return view('Admin.identity-verifications.index', compact('search', 'status', 'statuses', 'verifications'));
    }

    public function show(NationalId $verification): View
    {
        $verification->load('pendingRegistration', 'uploadedBy.role', 'reviewedBy');
        $imageExists = $verification->id_image_path
            && Storage::disk('public')->exists($verification->id_image_path);

        return view('Admin.identity-verifications.show', compact('verification', 'imageExists'));
    }

    public function image(NationalId $verification): StreamedResponse
    {
        abort_if(!$verification->id_image_path || !Storage::disk('public')->exists($verification->id_image_path), 404, 'The uploaded ID image could not be found.');

        return Storage::disk('public')->response($verification->id_image_path);
    }

    public function approve(Request $request, NationalId $verification): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $verification->load('pendingRegistration');
        $pending = $verification->pendingRegistration;

        abort_if(!$pending, 404);

        if (User::where('email', $pending->email)->exists()) {
            return redirect()
                ->route('admin.identity-verifications.show', $verification)
                ->withErrors(['email' => 'A user with this email already exists.']);
        }

        $citizenRole = Role::where('role', 'citizen')->first();

        abort_if(!$citizenRole, 422, 'Citizen role does not exist.');

        DB::transaction(function () use ($verification, $pending, $citizenRole, $validated) {
            $user = User::create([
                'name' => $pending->name,
                'email' => $pending->email,
                'phone' => $pending->phone,
                'password' => $pending->password,
                'role_id' => $citizenRole->id,
                'status' => 'active',
                'is_active' => true,
                'email_verified_at' => now(),
                'two_factor_enabled' => true,
            ]);

            $verification->update([
                'uploaded_by' => $user->id,
                'status' => NationalId::STATUS_APPROVED,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'admin_notes' => $validated['admin_notes'] ?? null,
            ]);

            $pending->update([
                'status' => PendingRegistration::STATUS_APPROVED,
                'admin_notes' => $validated['admin_notes'] ?? null,
            ]);
        });

        return redirect()
            ->route('admin.identity-verifications.show', $verification)
            ->with('success', 'National ID approved and citizen account created.');
    }

    public function reject(Request $request, NationalId $verification): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => ['required', 'string', 'max:2000'],
        ]);

        $verification->load('pendingRegistration');

        DB::transaction(function () use ($verification, $validated) {
            $verification->update([
                'status' => NationalId::STATUS_REJECTED,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'admin_notes' => $validated['admin_notes'],
            ]);

            $verification->pendingRegistration?->update([
                'status' => PendingRegistration::STATUS_REJECTED,
                'admin_notes' => $validated['admin_notes'],
            ]);
        });

        return redirect()
            ->route('admin.identity-verifications.show', $verification)
            ->with('success', 'National ID registration rejected.');
    }
}
