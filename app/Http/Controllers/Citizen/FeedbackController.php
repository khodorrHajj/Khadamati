<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\StoreFeedbackRequest;
use App\Models\Feedback;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    public function store(StoreFeedbackRequest $request, ServiceRequest $serviceRequest)
    {
        $this->authorizeOrAbort('createForCitizen', [Feedback::class, $serviceRequest]);

        if ($serviceRequest->feedback()->exists()) {
            return redirect()
                ->route('citizen.requests.show', $serviceRequest)
                ->withErrors(['feedback' => 'Feedback has already been submitted for this request.']);
        }

        $validated = $request->validated();

        Feedback::create([
            'service_request_id' => $serviceRequest->id,
            'user_id' => Auth::id(),
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        return redirect()
            ->route('citizen.requests.show', $serviceRequest)
            ->with('success', 'Feedback submitted successfully.');
    }
}
