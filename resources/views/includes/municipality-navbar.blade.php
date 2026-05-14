<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars"></i>
            </a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="{{ route('municipality.dashboard') }}" class="nav-link">Dashboard</a>
        </li>
    </ul>

    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <a class="nav-link" href="{{ route('municipality.requests.index') }}" title="Unread citizen messages">
                <i class="far fa-comments"></i>
                <span id="municipality-message-badge" class="badge badge-info navbar-badge {{ $municipalityUnreadMessageCount ? '' : 'd-none' }}">{{ $municipalityUnreadMessageCount }}</span>
            </a>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-bell"></i>
                <span id="municipality-notification-badge" class="badge badge-warning navbar-badge {{ $municipalityUnreadCount ? '' : 'd-none' }}">{{ $municipalityUnreadCount }}</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header">
                    <span id="municipality-notification-count-label">{{ $municipalityUnreadCount }}</span> unread notification{{ $municipalityUnreadCount === 1 ? '' : 's' }}
                </span>
                <div class="dropdown-divider"></div>

                @forelse ($municipalityUnreadNotifications as $notification)
                    <div class="dropdown-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="pr-2">
                                <strong>{{ $notification->data['title'] ?? 'Notification' }}</strong>
                                <div class="text-muted small">{{ $notification->data['message'] ?? '' }}</div>
                                @if (!empty($notification->data['request_id']))
                                    <a href="{{ route('municipality.requests.show', $notification->data['request_id']) }}" class="small">Open request</a>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('municipality.notifications.read', $notification) }}">
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
            </div>
        </li>
        <li class="nav-item">
            <span class="nav-link">{{ Auth::user()->name }}</span>
        </li>
    </ul>
</nav>

<script>
    (function () {
        const badge = document.getElementById('municipality-notification-badge');
        const countLabel = document.getElementById('municipality-notification-count-label');
        const messageBadge = document.getElementById('municipality-message-badge');

        if (!badge || !countLabel || !messageBadge) {
            return;
        }

        async function refreshUnreadCount() {
            try {
                const response = await fetch(@json(route('municipality.notifications.unread-count')), {
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
                // Polling should fail quietly until a future real-time layer replaces it.
            }
        }

        async function refreshUnreadMessages() {
            try {
                const response = await fetch(@json(route('municipality.messages.unread-count')), {
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

                messageBadge.textContent = count;
                messageBadge.classList.toggle('d-none', count === 0);
            } catch (error) {
                // Polling should fail quietly until a future real-time layer replaces it.
            }
        }

        setInterval(refreshUnreadCount, 15000);
        setInterval(refreshUnreadMessages, 15000);
    }());
</script>
