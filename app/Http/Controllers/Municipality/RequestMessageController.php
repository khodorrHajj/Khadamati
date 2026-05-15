<?php

namespace App\Http\Controllers\Municipality;

use App\Http\Controllers\Controller;
use App\Events\MessageSent;
use App\Http\Requests\Municipality\StoreRequestMessageRequest;
use App\Models\GovernmentOffice;
use App\Models\RequestMessage;
use App\Models\ServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RequestMessageController extends Controller
{
    public function index()
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return view('Municipality.NoOffice');
        }

        $requests = ServiceRequest::with([
            'user',
            'service.governmentOffice',
            'requestMessages.sender.role',
        ])
            ->whereHas('service', function ($query) use ($office) {
                $query->where('government_office_id', $office->id);
            })
            ->whereHas('requestMessages')
            ->withCount([
                'requestMessages as unread_messages_count' => function ($query) {
                    $query->unread()
                        ->fromCitizens();
                },
            ])
            ->withMax('requestMessages', 'created_at')
            ->orderByDesc('request_messages_max_created_at')
            ->paginate(10);

        return view('Municipality.messages.index', compact('office', 'requests'));
    }

    public function store(StoreRequestMessageRequest $request, ServiceRequest $serviceRequest)
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return abort(403);
        }

        $this->authorizeOrAbort('createAsMunicipality', [RequestMessage::class, $serviceRequest]);

        $validated = $request->validated();

        $message = RequestMessage::create([
            'service_request_id' => $serviceRequest->id,
            'sender_id' => Auth::id(),
            'body' => $validated['body'] ?? null,
            'attachment_path' => $request->hasFile('attachment')
                ? $request->file('attachment')->store('request-messages', 'public')
                : null,
        ])->load('sender.role');

        event(new MessageSent($message));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->messagePayload($message),
            ], 201);
        }

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

    private function messagePayload(RequestMessage $message): array
    {
        return [
            'id' => $message->id,
            'service_request_id' => $message->service_request_id,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender?->name ?? 'Unknown User',
            'sender_role' => $message->sender?->role?->role,
            'body' => $message->body,
            'attachment_url' => $message->attachment_path
                ? route('request-messages.attachments.download', $message)
                : null,
            'created_at' => optional($message->created_at)->format('Y-m-d H:i'),
            'created_at_human' => optional($message->created_at)->diffForHumans(),
        ];
    }

}
