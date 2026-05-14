<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\StoreRequestMessageRequest;
use App\Models\RequestMessage;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\Auth;

class RequestMessageController extends Controller
{
    public function store(StoreRequestMessageRequest $request, ServiceRequest $serviceRequest)
    {
        $this->authorizeOrAbort('createAsCitizen', [RequestMessage::class, $serviceRequest]);

        $validated = $request->validated();

        RequestMessage::create([
            'service_request_id' => $serviceRequest->id,
            'sender_id' => Auth::id(),
            'body' => $validated['body'] ?? null,
            'attachment_path' => $request->hasFile('attachment')
                ? $request->file('attachment')->store('request-messages', 'public')
                : null,
        ]);

        return redirect()
            ->route('citizen.requests.show', $serviceRequest)
            ->with('success', 'Message sent successfully.');
    }
}
