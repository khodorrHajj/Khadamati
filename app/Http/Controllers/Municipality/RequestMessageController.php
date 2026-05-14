<?php

namespace App\Http\Controllers\Municipality;

use App\Http\Controllers\Controller;
use App\Http\Requests\Municipality\StoreRequestMessageRequest;
use App\Models\GovernmentOffice;
use App\Models\RequestMessage;
use App\Models\ServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RequestMessageController extends Controller
{
    public function store(StoreRequestMessageRequest $request, ServiceRequest $serviceRequest)
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return abort(403);
        }

        $this->authorizeOrAbort('createAsMunicipality', [RequestMessage::class, $serviceRequest]);

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
            ->route('municipality.requests.show', $serviceRequest)
            ->with('success', 'Message sent successfully.');
    }

    public function unreadCount(): JsonResponse
    {
        $officeId = Auth::user()->government_office_id;

        if (!$officeId) {
            return response()->json([
                'unread_count' => 0,
            ]);
        }

        return response()->json([
            'unread_count' => RequestMessage::query()
                ->forMunicipalityOffice($officeId)
                ->fromCitizens()
                ->unread()
                ->count(),
        ]);
    }

    private function assignedOffice(): ?GovernmentOffice
    {
        return Auth::user()->governmentOffice;
    }

}
