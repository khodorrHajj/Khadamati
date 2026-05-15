@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('page-title', 'Admin Dashboard')

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Welcome</h3>
                </div>
                <div class="card-body">
                    <p class="mb-2">Welcome, {{ Auth::user()->name }}</p>
                    <p class="mb-0">Use the admin tools below to manage municipalities, government offices, and municipality users.</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Role</h3>
                </div>
                <div class="card-body">
                    <span class="badge badge-primary">{{ Auth::user()->role->role }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 col-12">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $municipalityCount }}</h3>
                    <p>Municipalities</p>
                </div>
                <div class="icon">
                    <i class="fas fa-city"></i>
                </div>
                <a href="{{ route('admin.municipalities.index') }}" class="small-box-footer">
                    Open <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-4 col-12">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $officeCount }}</h3>
                    <p>Government Offices</p>
                </div>
                <div class="icon">
                    <i class="fas fa-building"></i>
                </div>
                <a href="{{ route('admin.offices.index') }}" class="small-box-footer">
                    Open <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-4 col-12">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $municipalityUserCount }}</h3>
                    <p>Municipality Users</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <a href="{{ route('admin.municipality.users') }}" class="small-box-footer">
                    Open <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-4 col-12">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $requestStats['total'] }}</h3>
                    <p>Total Requests</p>
                </div>
                <div class="icon">
                    <i class="fas fa-file-signature"></i>
                </div>
                <a href="{{ route('admin.requests.index') }}" class="small-box-footer">
                    Open <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-4 col-12">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $requestStats['awaitingAdmin'] }}</h3>
                    <p>Awaiting Admin</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <a href="{{ route('admin.requests.index', ['workflow_state' => \App\Models\ServiceRequest::WORKFLOW_AWAITING_ADMIN]) }}" class="small-box-footer">
                    Open <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-4 col-12">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $requestStats['overdue'] }}</h3>
                    <p>Overdue Requests</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <a href="{{ route('admin.requests.index') }}" class="small-box-footer">
                    Review <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-4 col-12">
            <div class="small-box bg-dark">
                <div class="inner">
                    <h3>{{ $requestStats['unassigned'] }}</h3>
                    <p>Unassigned Requests</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user-clock"></i>
                </div>
                <a href="{{ route('admin.requests.index', ['assignment_scope' => 'unassigned']) }}" class="small-box-footer">
                    Open <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>
@endsection
