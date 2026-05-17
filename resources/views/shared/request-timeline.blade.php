<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">{{ $title ?? 'Request Timeline' }}</h3>
    </div>
    <div class="card-body p-0">
        <div class="list-group list-group-flush">
            @forelse ($entries as $entry)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="pr-3">
                            <div class="font-weight-bold">{{ $entry->title }}</div>
                            <div class="text-muted">{{ $entry->description }}</div>
                            <div class="small text-muted mt-2">
                                {{ optional($entry->created_at)->format('Y-m-d H:i') ?: '-' }}
                                @if ($entry->actor_label || $entry->actor?->name)
                                    <span class="mx-1">&middot;</span>
                                    {{ $entry->actor_label ?: $entry->actor?->name }}
                                @endif
                                @if ($entry->actor?->role)
                                    <span class="mx-1">&middot;</span>
                                    {{ ucfirst($entry->actor->role->role) }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="list-group-item text-muted">No request history has been recorded yet.</div>
            @endforelse
        </div>
    </div>
</div>
