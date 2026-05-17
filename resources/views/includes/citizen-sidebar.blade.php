<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="{{ route('citizen.dashboard') }}" class="brand-link">
        <span class="brand-text font-weight-light">Citizen Portal</span>
    </a>

    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                <li class="nav-item">
                    <a href="{{ route('citizen.dashboard') }}" class="nav-link {{ request()->routeIs('citizen.dashboard') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('citizen.services.index') }}" class="nav-link {{ request()->routeIs('citizen.offices.*', 'citizen.categories.*', 'citizen.services.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-concierge-bell"></i>
                        <p>Browse Services</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('citizen.requests.index') }}" class="nav-link {{ request()->routeIs('citizen.requests.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-file-alt"></i>
                        <p>My Requests</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('citizen.dashboard') }}#appointments" class="nav-link">
                        <i class="nav-icon fas fa-calendar-check"></i>
                        <p>Appointments</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('citizen.messages.index') }}" class="nav-link {{ request()->routeIs('citizen.messages.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-envelope"></i>
                        <p>
                            Messages
                            <span id="citizen-sidebar-message-badge" class="right badge badge-info {{ $citizenUnreadMessageCount ? '' : 'd-none' }}">{{ $citizenUnreadMessageCount }}</span>
                        </p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('citizen.payment.history') }}" class="nav-link {{ request()->routeIs('citizen.payment.history') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-credit-card"></i>
                        <p>My Payments</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('citizen.notifications.index') }}" class="nav-link {{ request()->routeIs('citizen.notifications.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-bell"></i>
                        <p>
                            Notifications
                            <span id="citizen-sidebar-notification-badge" data-notification-count-badge class="right badge badge-warning {{ $citizenUnreadCount ? '' : 'd-none' }}">{{ $citizenUnreadCount }}</span>
                        </p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('citizen.feedback.index') }}" class="nav-link {{ request()->routeIs('citizen.feedback.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-comment-dots"></i>
                        <p>Feedback</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('citizen.dashboard') }}#profile" class="nav-link">
                        <i class="nav-icon fas fa-user"></i>
                        <p>Profile</p>
                    </a>
                </li>
                <li class="nav-item">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="nav-link btn btn-link text-left">
                            <i class="nav-icon fas fa-sign-out-alt"></i>
                            <p>Logout</p>
                        </button>
                    </form>
                </li>
            </ul>
        </nav>
    </div>
</aside>
