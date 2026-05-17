@extends('layouts.citizen')

@section('title', 'Notifications')
@section('page-title', 'Notifications')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Notifications</h3>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                @forelse ($notifications as $notification)
                    <div class="list-group-item {{ $notification->read_at ? '' : 'bg-light' }}" data-notification-item>
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="pr-3">
                                <div class="font-weight-bold">{{ $notification->data['title'] ?? 'Notification' }}</div>
                                <div class="text-muted">{{ $notification->data['message'] ?? '' }}</div>
                                <div class="small text-muted mt-1">
                                    {{ optional($notification->created_at)->diffForHumans() }}
                                </div>
                                @if (!empty($notification->data['action_url']))
                                    <form method="POST" action="{{ route('citizen.notifications.read', $notification) }}" data-notification-form data-notification-action data-redirect-url="{{ $notification->data['action_url'] }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="redirect_to" value="{{ $notification->data['action_url'] }}">
                                        <button type="submit" class="btn btn-primary btn-sm mt-2">Open</button>
                                    </form>
                                @endif
                            </div>
                            @if (!$notification->read_at)
                                <form method="POST" action="{{ route('citizen.notifications.read', $notification) }}" data-notification-form data-notification-action>
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-link btn-sm p-0">Mark read</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="list-group-item text-muted">No notifications yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    @if ($notifications->hasPages())
        <div class="mt-3">
            {{ $notifications->links() }}
        </div>
    @endif

    @include('shared.notification-actions-scripts')
@endsection
