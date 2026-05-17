<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\GovernmentOffice;
use App\Models\Municipality;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function index(Request $request): View
    {
        $query = Feedback::with(['user', 'serviceRequest.service.governmentOffice.municipality', 'serviceRequest.service.serviceCategory', 'responder']);

        // Filter by municipality
        if ($request->filled('municipality')) {
            $query->whereHas('serviceRequest.service.governmentOffice', function ($q) use ($request) {
                $q->where('municipality_id', $request->municipality);
            });
        }

        // Filter by office
        if ($request->filled('office')) {
            $query->whereHas('serviceRequest.service', function ($q) use ($request) {
                $q->where('government_office_id', $request->office);
            });
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->whereHas('serviceRequest.service', function ($q) use ($request) {
                $q->where('service_category_id', $request->category);
            });
        }

        // Filter by rating
        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        // Filter by response status
        if ($request->filled('response_status')) {
            if ($request->response_status === 'responded') {
                $query->whereNotNull('responded_at');
            } elseif ($request->response_status === 'unresponded') {
                $query->whereNull('responded_at');
            }
        }

        // Search by comment
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $feedbacks = $query->latest()->paginate(15)->withQueryString();

        // Stats
        $allFeedback = Feedback::all();
        $stats = [
            'total' => $allFeedback->count(),
            'average_rating' => $allFeedback->count() > 0 ? round($allFeedback->avg('rating'), 1) : 0,
            'responded' => $allFeedback->where('responded_at')->count(),
            'unresponded' => $allFeedback->whereNull('responded_at')->count(),
            'rating_distribution' => collect(range(1, 5))->mapWithKeys(fn ($r) => [
                $r => $allFeedback->where('rating', $r)->count(),
            ])->toArray(),
        ];

        $municipalities = Municipality::orderBy('name')->get();
        $offices = GovernmentOffice::with('municipality')->orderBy('name')->get();
        $categories = ServiceCategory::orderBy('name')->get();

        return view('Admin.feedback.index', compact(
            'feedbacks',
            'stats',
            'municipalities',
            'offices',
            'categories'
        ));
    }

    public function show(Feedback $feedback): View
    {
        $feedback->load(['user', 'serviceRequest.service.governmentOffice.municipality', 'serviceRequest.service.serviceCategory', 'responder']);

        return view('Admin.feedback.show', compact('feedback'));
    }

    public function respond(Request $request, Feedback $feedback): RedirectResponse
    {
        $validated = $request->validate([
            'public_response' => ['nullable', 'string', 'max:2000'],
            'private_response' => ['nullable', 'string', 'max:2000'],
        ]);

        $feedback->update([
            'public_response' => $validated['public_response'] ?? null,
            'private_response' => $validated['private_response'] ?? null,
            'responded_by' => Auth::id(),
            'responded_at' => now(),
        ]);

        return redirect()->route('admin.feedback.show', $feedback)
            ->with('success', 'Response submitted successfully.');
    }
}
