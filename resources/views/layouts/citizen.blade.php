@extends('template.base')

@section('body')
<body class="hold-transition layout-top-nav">
    <div class="wrapper">
        <nav class="main-header navbar navbar-expand-md navbar-light navbar-white">
            <div class="container">
                <a href="{{ auth()->check() && auth()->user()->hasRole('citizen') ? route('citizen.dashboard') : route('login') }}" class="navbar-brand">
                    <span class="brand-text font-weight-light">E-Services Portal</span>
                </a>

                <button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#citizen-navbar">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse order-3" id="citizen-navbar">
                    <ul class="navbar-nav">
                        @if (auth()->check() && auth()->user()->hasRole('citizen'))
                            <li class="nav-item">
                                <a href="{{ route('citizen.dashboard') }}" class="nav-link {{ request()->routeIs('citizen.dashboard') ? 'active' : '' }}">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('citizen.offices.index') }}" class="nav-link {{ request()->routeIs('citizen.offices.*', 'citizen.categories.*', 'citizen.services.*') ? 'active' : '' }}">Browse Services</a>
                            </li>
                        @endif
                        <li class="nav-item">
                            <a href="{{ route('tracking.show', ['trackingCode' => 'ENTER-CODE']) }}" class="nav-link disabled" aria-disabled="true">Public Tracking</a>
                        </li>
                    </ul>

                    <ul class="order-1 order-md-3 navbar-nav navbar-no-expand ml-auto">
                        @if (auth()->check())
                            <li class="nav-item">
                                <span class="nav-link">{{ auth()->user()->name }}</span>
                            </li>
                            <li class="nav-item">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-link nav-link">Logout</button>
                                </form>
                            </li>
                        @else
                            <li class="nav-item">
                                <a href="{{ route('login') }}" class="nav-link">Login</a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </nav>

        <div class="content-wrapper">
            <div class="content-header">
                <div class="container">
                    <div class="row mb-2">
                        <div class="col-sm-12">
                            <h1 class="m-0">@yield('page-title')</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="container">
                    @yield('content')
                </div>
            </div>
        </div>

        <footer class="main-footer">
            <div class="container">
                <strong>E-Services Portal</strong>
            </div>
        </footer>
    </div>
</body>
@endsection
