@extends('layouts.municipality')

@section('title', 'Conversation')
@section('page-title', 'Conversation')

@section('content')
    <div class="row">
        {{-- Conversation List --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list mr-1"></i> Conversations</h3>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach ($requests as $requestItem)
                            @php($latestMessage = $requestItem->requestMessages->last())
                            <a href="{{ route('municipality.messages.show', $requestItem) }}" class="list-group-item list-group-item-action {{ $requestItem->id === $activeRequest->id ? 'active' : '' }}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>{{ $requestItem->user?->name ?? 'Citizen' }}</strong>
                                        <div class="small text-muted">{{ \Illuminate\Support\Str::limit($latestMessage?->body ?: 'Attachment shared', 45) }}</div>
                                    </div>
                                    @if ($requestItem->unread_messages_count)
                                        <span class="badge badge-info">{{ $requestItem->unread_messages_count }}</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Chat Area --}}
        <div class="col-lg-8">
            <div class="card" data-request-chat data-chat-style="bubbles" data-request-id="{{ $activeRequest->id }}" data-current-user-id="{{ Auth::id() }}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">
                            {{ $activeRequest->user?->name ?? 'Citizen Conversation' }}
                        </h3>
                        <div class="small text-muted">
                            {{ $activeRequest->service?->name ?? '-' }} &middot; {{ $activeRequest->tracking_code }}
                            &middot; <span class="badge badge-light border">{{ $activeRequest->status }}</span>
                        </div>
                    </div>
                    <a href="{{ route('municipality.requests.show', $activeRequest) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-file-alt mr-1"></i> Request Details
                    </a>
                </div>

                <div class="card-body p-0" data-chat-messages style="max-height:480px;overflow-y:auto;">
                    @forelse ($activeRequest->requestMessages as $messageItem)
                        <div class="p-3 border-bottom {{ $messageItem->sender_id === Auth::id() ? 'bg-light' : '' }}" data-message-id="{{ $messageItem->id }}">
                            <div class="d-flex justify-content-between">
                                <strong>
                                    {{ $messageItem->sender->name ?? 'Unknown' }}
                                    @if ($messageItem->sender?->role)
                                        <span class="badge badge-secondary ml-1">{{ ucfirst($messageItem->sender->role->role) }}</span>
                                    @endif
                                </strong>
                                <span class="text-muted small">{{ optional($messageItem->created_at)->format('M d, Y H:i') ?: '-' }}</span>
                            </div>
                            @if (filled($messageItem->body))
                                <div class="mt-1">{!! nl2br(e($messageItem->body)) !!}</div>
                            @endif
                            @if ($messageItem->attachment_path)
                                <div class="mt-1">
                                    <a href="{{ route('request-messages.attachments.download', $messageItem) }}" target="_blank" rel="noopener">
                                        <i class="fas fa-paperclip mr-1"></i> Open attachment
                                    </a>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-comment-slash fa-2x mb-2 d-block"></i>
                            No messages yet. Start the conversation!
                        </div>
                    @endforelse
                </div>

                <div class="card-footer">
                    <form method="POST" action="{{ route('municipality.requests.messages.store', $activeRequest) }}" enctype="multipart/form-data" data-chat-form>
                        @csrf
                        <div class="form-group">
                            <textarea name="body" rows="2" class="form-control @error('body') is-invalid @enderror" placeholder="Type your reply...">{{ old('body') }}</textarea>
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
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane mr-1"></i> Send
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @include('shared.request-chat-scripts')
@endsection