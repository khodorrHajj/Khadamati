@extends('layouts.municipality')

@section('title', 'Messages')
@section('page-title', 'Messages')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $office->name }} Conversations</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap mb-0">
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Tracking Code</th>
                        <th>Citizen</th>
                        <th>Service</th>
                        <th>Office</th>
                        <th>Status</th>
                        <th>Latest Message</th>
                        <th>Unread</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $serviceRequest)
                        @php($latestMessage = $serviceRequest->requestMessages->last())
                        <tr>
                            <td>#{{ $serviceRequest->id }}</td>
                            <td>{{ $serviceRequest->tracking_code }}</td>
                            <td>{{ $serviceRequest->user?->name ?? 'Unknown Citizen' }}</td>
                            <td>{{ $serviceRequest->service?->name ?? '-' }}</td>
                            <td>{{ $serviceRequest->service?->governmentOffice?->name ?? $office->name }}</td>
                            <td><span class="badge badge-light border">{{ $serviceRequest->status }}</span></td>
                            <td>
                                <strong>{{ $latestMessage?->sender?->name ?? 'Unknown User' }}</strong>
                                <span class="text-muted small ml-1">{{ optional($latestMessage?->created_at)->format('Y-m-d H:i') ?: '-' }}</span>
                                <div>{{ \Illuminate\Support\Str::limit($latestMessage?->body ?: 'Attachment', 80) }}</div>
                            </td>
                            <td>
                                <span class="badge badge-info {{ $serviceRequest->unread_messages_count ? '' : 'd-none' }}">
                                    {{ $serviceRequest->unread_messages_count }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('municipality.messages.show', $serviceRequest) }}" class="btn btn-primary btn-sm">
                                    Open Chat
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No request conversations yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($requests->hasPages())
        <div class="mt-3">
            {{ $requests->links() }}
        </div>
    @endif
@endsection
