<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars"></i>
            </a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="{{ route('admin.dashboard') }}" class="nav-link">Dashboard</a>
        </li>
    </ul>

    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-bell"></i>
                <span id="admin-notification-badge" data-notification-count-badge class="badge badge-warning navbar-badge {{ $adminUnreadCount ? '' : 'd-none' }}">{{ $adminUnreadCount }}</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header">
                    <span id="admin-notification-count-label" data-notification-count-label>{{ $adminUnreadCount }}</span> unread notification{{ $adminUnreadCount === 1 ? '' : 's' }}
                </span>
                <div class="dropdown-divider"></div>

                @forelse ($adminUnreadNotifications as $notification)
                    <div class="dropdown-item" data-notification-item>
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="pr-2">
                                <strong>{{ $notification->data['title'] ?? 'Notification' }}</strong>
                                <div class="text-muted small">{{ $notification->data['message'] ?? '' }}</div>
                                @if (!empty($notification->data['action_url']))
                                    <form method="POST" action="{{ route('admin.notifications.read', $notification) }}" data-notification-form data-notification-action data-redirect-url="{{ $notification->data['action_url'] }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="redirect_to" value="{{ $notification->data['action_url'] }}">
                                        <button type="submit" class="btn btn-link btn-sm p-0">Open request</button>
                                    </form>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('admin.notifications.read', $notification) }}" data-notification-form data-notification-action>
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-link btn-sm p-0">Mark read</button>
                            </form>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                @empty
                    <span class="dropdown-item text-muted">No unread notifications.</span>
                @endforelse

                <div class="dropdown-divider"></div>
                <a href="{{ route('admin.notifications.index') }}" class="dropdown-item dropdown-footer">View all notifications</a>
            </div>
        </li>
        <li class="nav-item">
            <span class="nav-link">{{ Auth::user()->name }}</span>
        </li>
    </ul>
</nav>

<script>
    (function () {
        const badge = document.getElementById('admin-notification-badge');
        const countLabel = document.getElementById('admin-notification-count-label');

        if (!badge || !countLabel) {
            return;
        }

        async function refreshUnreadCount() {
            try {
                const response = await fetch(@json(route('admin.notifications.unread-count')), {
                    headers: {
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                const count = Number(data.unread_count || 0);

                badge.textContent = count;
                countLabel.textContent = count;
                badge.classList.toggle('d-none', count === 0);
            } catch (error) {
                // Polling should fail quietly.
            }
        }

        setInterval(refreshUnreadCount, 15000);
    }());
</script>

@include('shared.notification-actions-scripts')
