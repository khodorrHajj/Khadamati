<?php

namespace App\Events;

use App\Models\RequestMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public RequestMessage $message)
    {
        $this->message->loadMissing('sender.role');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('request-chat.' . $this->message->service_request_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'service_request_id' => $this->message->service_request_id,
                'sender_id' => $this->message->sender_id,
                'sender_name' => $this->message->sender?->name ?? 'Unknown User',
                'sender_role' => $this->message->sender?->role?->role,
                'body' => $this->message->body,
                'attachment_url' => $this->message->attachment_path
                    ? route('request-messages.attachments.download', $this->message)
                    : null,
                'created_at' => optional($this->message->created_at)->format('Y-m-d H:i'),
                'created_at_human' => optional($this->message->created_at)->diffForHumans(),
            ],
        ];
    }
}
