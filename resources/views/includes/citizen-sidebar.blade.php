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
                    <a href="{{ route('citizen.dashboard') }}#recent-requests" class="nav-link">
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
                    <a href="{{ route('citizen.dashboard') }}#messages" class="nav-link">
                        <i class="nav-icon fas fa-envelope"></i>
                        <p>Messages</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('citizen.dashboard') }}#payments" class="nav-link">
                        <i class="nav-icon fas fa-credit-card"></i>
                        <p>Payments</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('citizen.dashboard') }}#notifications" class="nav-link">
                        <i class="nav-icon fas fa-bell"></i>
                        <p>Notifications</p>
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
