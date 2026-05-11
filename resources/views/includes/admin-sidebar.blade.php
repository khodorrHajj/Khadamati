<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="{{ route('admin.dashboard') }}" class="brand-link">
        <span class="brand-text font-weight-light">E-Services Admin</span>
    </a>

    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->is('admin/dashboard') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.municipalities') }}" class="nav-link {{ request()->is('admin/municipalities') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-city"></i>
                        <p>Manage Municipalities</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.offices') }}" class="nav-link {{ request()->is('admin/offices') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-building"></i>
                        <p>Manage Government Offices</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.municipality.users') }}" class="nav-link {{ request()->is('admin/municipality-users') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Manage Municipality Users</p>
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
