<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="{{ route('municipality.dashboard') }}" class="brand-link">
        <span class="brand-text font-weight-light">Municipality Portal</span>
    </a>

    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                <li class="nav-item">
                    <a href="{{ route('municipality.dashboard') }}" class="nav-link {{ request()->routeIs('municipality.dashboard') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('municipality.office.show') }}" class="nav-link {{ request()->routeIs('municipality.office.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-building"></i>
                        <p>Office Profile</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('municipality.categories') }}" class="nav-link {{ request()->routeIs('municipality.categories*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-list"></i>
                        <p>Categories</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('municipality.services') }}" class="nav-link {{ request()->routeIs('municipality.services*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-concierge-bell"></i>
                        <p>Services</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('municipality.requests.index') }}" class="nav-link {{ request()->routeIs('municipality.requests.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-file-alt"></i>
                        <p>Requests</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('municipality.feedback.index') }}" class="nav-link {{ request()->routeIs('municipality.feedback.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-comment-dots"></i>
                        <p>Feedback</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('municipality.messages.index') }}" class="nav-link {{ request()->routeIs('municipality.messages.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-envelope"></i>
                        <p>
                            Messages
                            <span id="municipality-sidebar-message-badge" class="right badge badge-info {{ $municipalityUnreadMessageCount ? '' : 'd-none' }}">{{ $municipalityUnreadMessageCount }}</span>
                        </p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('municipality.appointments.index') }}" class="nav-link {{ request()->routeIs('municipality.appointments.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-calendar-check"></i>
                        <p>Appointments</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('municipality.reports.index') }}" class="nav-link {{ request()->routeIs('municipality.reports.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-chart-bar"></i>
                        <p>Reports</p>
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
