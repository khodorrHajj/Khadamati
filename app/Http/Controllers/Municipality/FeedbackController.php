<?php

namespace App\Http\Controllers\Municipality;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\GovernmentOffice;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return view('Municipality.NoOffice');
        }

        $validated = $request->validate([
            'service' => [
                'nullable',
                Rule::exists('services', 'id')->where(function ($query) use ($office) {
                    $query->where('government_office_id', $office->id);
                }),
            ],
            'rating' => ['nullable', 'integer', 'between:1,5'],
        ]);

        $filters = [
            'service' => $validated['service'] ?? null,
            'rating' => $validated['rating'] ?? null,
        ];

        $services = Service::where('government_office_id', $office->id)
            ->orderBy('name')
            ->get();

        $feedbackQuery = Feedback::with([
            'user',
            'responder',
            'serviceRequest.service.serviceCategory',
        ])
            ->whereHas('serviceRequest.service', function ($query) use ($office) {
                $query->where('government_office_id', $office->id);
            })
            ->when($filters['service'], function ($query) use ($filters) {
                $query->whereHas('serviceRequest', function ($serviceRequestQuery) use ($filters) {
                    $serviceRequestQuery->where('service_id', $filters['service']);
                });
            })
            ->when($filters['rating'], function ($query) use ($filters) {
                $query->where('rating', $filters['rating']);
            });

        $averageRating = (clone $feedbackQuery)->avg('rating');

        $feedback = $feedbackQuery
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('Municipality.feedback.index', [
            'averageRating' => $averageRating ? round((float) $averageRating, 1) : null,
            'feedback' => $feedback,
            'filters' => $filters,
            'office' => $office,
            'services' => $services,
        ]);
    }

    public function update(Request $request, Feedback $feedback)
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return abort(403);
        }

        $this->authorizeOrAbort('updateMunicipality', $feedback);

        $validated = $request->validate([
            'public_response' => ['nullable', 'string'],
            'private_response' => ['nullable', 'string'],
        ]);

        $publicResponse = $validated['public_response'] ?? null;
        $privateResponse = $validated['private_response'] ?? null;
        $hasResponse = filled($publicResponse) || filled($privateResponse);

        $feedback->update([
            'public_response' => $publicResponse,
            'private_response' => $privateResponse,
            'responded_by' => $hasResponse ? Auth::id() : null,
            'responded_at' => $hasResponse ? now() : null,
        ]);

        return redirect()
            ->route('municipality.feedback.index')
            ->with('success', 'Feedback response saved successfully.');
    }

    private function assignedOffice(): ?GovernmentOffice
    {
        return Auth::user()->governmentOffice;
    }

}
