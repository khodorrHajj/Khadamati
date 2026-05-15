@extends('layouts.municipality')

@section('title', 'Municipality Dashboard')
@section('page-title', 'Municipality Dashboard')

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Welcome</h3>
                </div>
                <div class="card-body">
                    <p class="mb-2">Welcome, {{ Auth::user()->name }}</p>
                    <p class="mb-0">Manage service categories and services for {{ $office->name }}.</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Office</h3>
                </div>
                <div class="card-body">
                    <span class="badge badge-primary">{{ $office->name }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totalCategories }}</h3>
                    <p>Total Categories</p>
                </div>
                <div class="icon">
                    <i class="fas fa-list"></i>
                </div>
                <a href="{{ route('municipality.categories') }}" class="small-box-footer">
                    Open <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $totalServices }}</h3>
                    <p>Total Services</p>
                </div>
                <div class="icon">
                    <i class="fas fa-concierge-bell"></i>
                </div>
                <a href="{{ route('municipality.services') }}" class="small-box-footer">
                    Open <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $totalRequests }}</h3>
                    <p>Total Requests</p>
                </div>
                <div class="icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <a href="{{ route('municipality.requests.index') }}" class="small-box-footer">
                    Open <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $pendingRequests }}</h3>
                    <p>Pending Requests</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
                <a href="{{ route('municipality.requests.index', ['status' => 'Pending']) }}" class="small-box-footer">
                    Open <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $inReviewRequests }}</h3>
                    <p>In Review Requests</p>
                </div>
                <div class="icon">
                    <i class="fas fa-search"></i>
                </div>
                <a href="{{ route('municipality.requests.index', ['status' => 'In Review']) }}" class="small-box-footer">
                    Open <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $completedRequests }}</h3>
                    <p>Completed Requests</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <a href="{{ route('municipality.requests.index', ['status' => 'Completed']) }}" class="small-box-footer">
                    Open <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $missingDocumentsRequests }}</h3>
                    <p>Missing Documents</p>
                </div>
                <div class="icon">
                    <i class="fas fa-file-upload"></i>
                </div>
                <a href="{{ route('municipality.requests.index', ['status' => 'Missing Documents']) }}" class="small-box-footer">
                    Open <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-dark">
                <div class="inner">
                    <h3>{{ $assignedToMeRequests }}</h3>
                    <p>Assigned To Me</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <a href="{{ route('municipality.requests.index', ['handoff_scope' => 'assigned_to_me']) }}" class="small-box-footer">
                    Open <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-maroon">
                <div class="inner">
                    <h3>{{ $awaitingAdminRequests }}</h3>
                    <p>Awaiting Admin</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <a href="{{ route('municipality.requests.index', ['handoff_scope' => 'awaiting_admin']) }}" class="small-box-footer">
                    Open <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $overdueRequests }}</h3>
                    <p>Overdue Requests</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <a href="{{ route('municipality.requests.index') }}" class="small-box-footer">
                    Review <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>
@endsection
