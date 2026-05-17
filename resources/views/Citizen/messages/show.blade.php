@extends('layouts.citizen')

@section('title', 'Conversation')
@section('page-title', 'Conversation')

@section('content')
    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">My Conversations</h3>
                    <a href="{{ route('citizen.requests.show', $activeRequest) }}" class="btn btn-outline-secondary btn-sm">Back To Request</a>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach ($requests as $requestItem)
                            @php($latestMessage = $requestItem->requestMessages->last())
                            <a href="{{ route('citizen.messages.show', $requestItem) }}" class="list-group-item list-group-item-action {{ $requestItem->id === $activeRequest->id ? 'active' : '' }}">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <strong>{{ $requestItem->service?->name ?? 'Request #' . $requestItem->id }}</strong>
                                    @if ($requestItem->unread_messages_count)
                                        <span class="badge badge-info">{{ $requestItem->unread_messages_count }}</span>
                                    @endif
                                </div>
                                <div class="small {{ $requestItem->id === $activeRequest->id ? 'text-white-50' : 'text-muted' }}">{{ $requestItem->tracking_code }}</div>
                                <div class="small mt-1">{{ \Illuminate\Support\Str::limit($latestMessage?->body ?: 'Attachment shared', 55) }}</div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm" data-request-chat data-chat-style="bubbles" data-request-id="{{ $activeRequest->id }}" data-current-user-id="{{ Auth::id() }}">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h3 class="card-title mb-1">{{ $activeRequest->service?->name ?? 'Conversation' }}</h3>
                            <div class="text-muted small">
                                {{ $activeRequest->service?->governmentOffice?->name ?? '-' }} · {{ $activeRequest->tracking_code }}
                            </div>
                        </div>
                        <a href="{{ route('citizen.requests.show', $activeRequest) }}" class="btn btn-primary btn-sm">Open Request Details</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="px-3 py-2 border-bottom bg-light">
                        <span class="badge badge-light border">{{ $activeRequest->status }}</span>
                        <span class="text-muted small ml-2">{{ $activeRequest->service?->governmentOffice?->municipality?->name ?? '-' }}</span>
                    </div>

                    <div class="p-3" data-chat-messages style="max-height: 480px; overflow-y: auto; background: linear-gradient(180deg, #f9fbff 0%, #ffffff 100%);">
                        @forelse ($activeRequest->requestMessages as $messageItem)
                            <div class="mb-3 d-flex {{ $messageItem->sender_id === Auth::id() ? 'justify-content-end' : 'justify-content-start' }}" data-message-id="{{ $messageItem->id }}">
                                <div class="rounded-lg border px-3 py-2 {{ $messageItem->sender_id === Auth::id() ? 'bg-primary text-white' : 'bg-white' }}" style="max-width: 78%;">
                                    <div class="small {{ $messageItem->sender_id === Auth::id() ? 'text-white-50' : 'text-muted' }} mb-1">
                                        {{ $messageItem->sender->name ?? 'Unknown User' }}
                                        @if ($messageItem->sender?->role)
                                            · {{ ucfirst($messageItem->sender->role->role) }}
                                        @endif
                                    </div>
                                    @if (filled($messageItem->body))
                                        <div>{!! nl2br(e($messageItem->body)) !!}</div>
                                    @endif
                                    @if ($messageItem->attachment_path)
                                        <div class="mt-2">
                                            <a href="{{ route('request-messages.attachments.download', $messageItem) }}" target="_blank" rel="noopener" class="{{ $messageItem->sender_id === Auth::id() ? 'text-white' : '' }}">
                                                Open attachment
                                            </a>
                                        </div>
                                    @endif
                                    <div class="small {{ $messageItem->sender_id === Auth::id() ? 'text-white-50' : 'text-muted' }} mt-2">
                                        {{ optional($messageItem->created_at)->format('Y-m-d H:i') ?: '-' }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No messages yet.</p>
                        @endforelse
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <form method="POST" action="{{ route('citizen.requests.messages.store', $activeRequest) }}" enctype="multipart/form-data" data-chat-form>
                        @csrf
                        <div class="form-group mb-2">
                            <textarea name="body" rows="3" class="form-control @error('body') is-invalid @enderror" placeholder="Write a message to the municipality...">{{ old('body') }}</textarea>
                            @error('body')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <input type="file" name="attachment" class="form-control-file @error('attachment') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,application/pdf,image/*">
                                @error('attachment')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary px-4">Send</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @include('shared.request-chat-scripts')
@endsection
