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
        <li class="nav-item">
            <span class="nav-link">{{ Auth::user()->name }}</span>
        </li>
    </ul>
</nav>

<script>
    (function () {
        const badge = document.getElementById('citizen-message-badge');
        const sidebarBadge = document.getElementById('citizen-sidebar-message-badge');

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

        setInterval(refreshUnreadMessages, 15000);
    }());
</script>
