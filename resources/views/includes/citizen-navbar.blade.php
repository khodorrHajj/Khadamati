<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars"></i>
            </a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="{{ route('citizen.dashboard') }}" class="nav-link">Dashboard</a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="{{ route('citizen.services.index') }}" class="nav-link">Browse Services</a>
        </li>
    </ul>

    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <a class="nav-link" href="{{ route('citizen.messages.index') }}" title="Unread messages">
                <i class="far fa-comments"></i>
                <span id="citizen-message-badge" class="badge badge-info navbar-badge {{ $citizenUnreadMessageCount ? '' : 'd-none' }}">{{ $citizenUnreadMessageCount }}</span>
            </a>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-bell"></i>
                <span id="citizen-notification-badge" data-notification-count-badge class="badge badge-warning navbar-badge {{ $citizenUnreadCount ? '' : 'd-none' }}">{{ $citizenUnreadCount }}</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header">
                    <span id="citizen-notification-count-label" data-notification-count-label>{{ $citizenUnreadCount }}</span> unread notification{{ $citizenUnreadCount === 1 ? '' : 's' }}
                </span>
                <div class="dropdown-divider"></div>

                @forelse ($citizenUnreadNotifications as $notification)
                    <div class="dropdown-item" data-notification-item>
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="pr-2">
                                <strong>{{ $notification->data['title'] ?? 'Notification' }}</strong>
                                <div class="text-muted small">{{ $notification->data['message'] ?? '' }}</div>
                                @if (!empty($notification->data['action_url']))
                                    <form method="POST" action="{{ route('citizen.notifications.read', $notification) }}" data-notification-form data-notification-action data-redirect-url="{{ $notification->data['action_url'] }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="redirect_to" value="{{ $notification->data['action_url'] }}">
                                        <button type="submit" class="btn btn-link btn-sm p-0">Open</button>
                                    </form>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('citizen.notifications.read', $notification) }}" data-notification-form data-notification-action>
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

                <a href="{{ route('citizen.notifications.index') }}" class="dropdown-item dropdown-footer">View all notifications</a>
            </div>
        </li>
        <li class="nav-item">
            <span class="nav-link">{{ Auth::user()->name }}</span>
        </li>
    </ul>
</nav>

<script>
    (function () {
        const badge = document.getElementById('citizen-message-badge');
        const sidebarBadge = document.getElementById('citizen-sidebar-message-badge');
        const notificationBadge = document.getElementById('citizen-notification-badge');
        const sidebarNotificationBadge = document.getElementById('citizen-sidebar-notification-badge');
        const notificationCountLabel = document.getElementById('citizen-notification-count-label');

        if (!badge) {
            return;
        }

        async function refreshUnreadMessages() {
            try {
                const response = await fetch(@json(route('citizen.messages.unread-count')), {
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
                badge.classList.toggle('d-none', count === 0);

                if (sidebarBadge) {
                    sidebarBadge.textContent = count;
                    sidebarBadge.classList.toggle('d-none', count === 0);
                }
            } catch (error) {
                // The chat badge refresh should not interrupt regular navigation.
            }
        }

        async function refreshUnreadNotifications() {
            if (!notificationBadge || !notificationCountLabel) {
                return;
            }

            try {
                const response = await fetch(@json(route('citizen.notifications.unread-count')), {
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

                notificationBadge.textContent = count;
                notificationCountLabel.textContent = count;
                notificationBadge.classList.toggle('d-none', count === 0);

                if (sidebarNotificationBadge) {
                    sidebarNotificationBadge.textContent = count;
                    sidebarNotificationBadge.classList.toggle('d-none', count === 0);
                }
            } catch (error) {
                // Notification polling should fail quietly.
            }
        }

        setInterval(refreshUnreadMessages, 15000);
        setInterval(refreshUnreadNotifications, 15000);
    }());
</script>

@include('shared.notification-actions-scripts')
