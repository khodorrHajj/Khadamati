@extends('layouts.citizen')

@section('title', 'My Feedback')
@section('page-title', 'My Feedback')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Submitted Feedback</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap mb-0">
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Service</th>
                        <th>Office</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Municipality Response</th>
                        <th>Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($feedback as $item)
                        <tr>
                            <td>#{{ $item->service_request_id }}</td>
                            <td>{{ $item->serviceRequest?->service?->name ?? '-' }}</td>
                            <td>{{ $item->serviceRequest?->service?->governmentOffice?->name ?? '-' }}</td>
                            <td><span class="badge badge-warning">{{ $item->rating }}/5</span></td>
                            <td>{{ \Illuminate\Support\Str::limit($item->comment, 60) }}</td>
                            <td>
                                @if ($item->public_response || $item->private_response)
                                    <span class="badge badge-success">Responded</span>
                                @else
                                    <span class="badge badge-light border">Pending</span>
                                @endif
                            </td>
                            <td>{{ optional($item->created_at)->format('Y-m-d H:i') ?: '-' }}</td>
                            <td>
                                <a href="{{ route('citizen.requests.show', $item->serviceRequest) }}" class="btn btn-primary btn-sm">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">You have not submitted feedback yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($feedback->hasPages())
        <div class="mt-3">
            {{ $feedback->links() }}
        </div>
    @endif
@endsection
