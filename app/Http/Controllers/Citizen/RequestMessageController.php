<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Events\MessageSent;
use App\Http\Requests\Citizen\StoreRequestMessageRequest;
use App\Models\RequestMessage;
use App\Models\ServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RequestMessageController extends Controller
{
    public function index()
    {
        $requests = $this->conversationQuery()
            ->paginate(10);

        return view('Citizen.messages.index', compact('requests'));
    }

    public function show(ServiceRequest $serviceRequest)
    {
        $this->authorizeOrAbort('viewCitizen', $serviceRequest);

        $serviceRequest->load([
            'service.governmentOffice.municipality',
            'requestMessages.sender.role',
        ]);

        $this->markIncomingMessagesAsRead($serviceRequest);

        $requests = $this->conversationQuery()->get();

        return view('Citizen.messages.show', [
            'activeRequest' => $serviceRequest->fresh([
                'service.governmentOffice.municipality',
                'requestMessages.sender.role',
            ]),
            'requests' => $requests,
        ]);
    }

    public function store(StoreRequestMessageRequest $request, ServiceRequest $serviceRequest)
    {
        $this->authorizeOrAbort('createAsCitizen', [RequestMessage::class, $serviceRequest]);

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
            ->route('citizen.requests.show', $serviceRequest)
            ->with('success', 'Message sent successfully.');
    }

    public function unreadCount(): JsonResponse
    {
        return response()->json([
            'unread_count' => RequestMessage::query()
                ->unread()
                ->where('sender_id', '!=', Auth::id())
                ->whereHas('serviceRequest', function ($query) {
                    $query->where('user_id', Auth::id());
                })
                ->count(),
        ]);
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

    private function conversationQuery()
    {
        return ServiceRequest::with([
            'service.governmentOffice',
            'requestMessages.sender.role',
        ])
            ->where('user_id', Auth::id())
            ->whereHas('requestMessages')
            ->withCount([
                'requestMessages as unread_messages_count' => function ($query) {
                    $query->unread()
                        ->where('sender_id', '!=', Auth::id());
                },
            ])
            ->withMax('requestMessages', 'created_at')
            ->orderByDesc('request_messages_max_created_at');
    }

    private function markIncomingMessagesAsRead(ServiceRequest $serviceRequest): void
    {
        $serviceRequest->requestMessages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', Auth::id())
            ->update(['read_at' => now()]);
    }
}
