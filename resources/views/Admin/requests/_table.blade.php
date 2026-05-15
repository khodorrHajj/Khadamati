<table class="table table-bordered table-striped mb-0">
    <thead>
        <tr>
            <th>ID</th>
            <th>Tracking Code</th>
            <th>Citizen</th>
            <th>Service</th>
            <th>Office</th>
            <th>Municipality</th>
            <th>Status</th>
            <th>Documents</th>
            <th>Submitted</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($requests as $requestItem)
            <tr>
                <td>#{{ $requestItem->id }}</td>
                <td>{{ $requestItem->tracking_code }}</td>
                <td>
                    <div>{{ $requestItem->user->name ?? '-' }}</div>
                    <div class="text-muted small">{{ $requestItem->user->email ?? '-' }}</div>
                </td>
                <td>{{ $requestItem->service?->name ?? '-' }}</td>
                <td>{{ $requestItem->service?->governmentOffice?->name ?? '-' }}</td>
                <td>{{ $requestItem->service?->governmentOffice?->municipality?->name ?? '-' }}</td>
                <td>
                    <span class="badge badge-light border">{{ $requestItem->status }}</span>
                    <div class="small mt-1">
                        @if ($requestItem->isClosed())
                            <span class="badge badge-success">Closed</span>
                        @elseif ($requestItem->isAwaitingAdmin())
                            <span class="badge badge-danger">Awaiting Admin</span>
                        @else
                            <span class="badge badge-info">Awaiting Municipality</span>
                        @endif
                        @if ($requestItem->assignedTo)
                            <div class="text-muted mt-1">Assigned to {{ $requestItem->assignedTo->name }}</div>
                        @endif
                    </div>
                </td>
                <td>{{ $requestItem->request_documents_count }}</td>
                <td>{{ optional($requestItem->created_at)->format('Y-m-d') ?: '-' }}</td>
                <td>
                    <a href="{{ route('admin.requests.show', $requestItem) }}" class="btn btn-primary btn-sm">Open</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="text-center">No requests found.</td>
            </tr>
        @endforelse
    </tbody>
</table>
