@extends('layouts.citizen')

@section('title', 'Citizen Dashboard')
@section('page-title', 'Citizen Dashboard')

@section('content')
    <div class="row">
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totalRequests }}</h3>
                    <p>Total Requests</p>
                </div>
                <div class="icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <a href="#recent-requests" class="small-box-footer">View <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $pendingRequests }}</h3>
                    <p>Pending Requests</p>
                </div>
                <div class="icon">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <a href="#recent-requests" class="small-box-footer">View <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $completedRequests }}</h3>
                    <p>Completed Requests</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <a href="#recent-requests" class="small-box-footer">View <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6" id="appointments">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $upcomingAppointments }}</h3>
                    <p>Upcoming Appointments</p>
                </div>
                <div class="icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <a href="#recent-requests" class="small-box-footer">View <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6" id="messages">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $unreadMessages }}</h3>
                    <p>Unread Messages</p>
                </div>
                <div class="icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <a href="#recent-requests" class="small-box-footer">View <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6" id="payments">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $pendingPayments }}</h3>
                    <p>Pending Payments</p>
                </div>
                <div class="icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <a href="#recent-requests" class="small-box-footer">View <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card" id="recent-requests">
                <div class="card-header">
                    <h3 class="card-title">Recent Requests</h3>
                    <div class="card-tools">
                        <a href="{{ route('citizen.services.index') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus mr-1"></i> New Request
                        </a>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap mb-0">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Service</th>
                                <th>Office</th>
                                <th>Status</th>
                                <th>Created date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentRequests as $serviceRequest)
                                <tr>
                                    <td>#{{ $serviceRequest->id }}</td>
                                    <td>{{ $serviceRequest->service?->name ?? '-' }}</td>
                                    <td>{{ $serviceRequest->service?->governmentOffice?->name ?? '-' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $serviceRequest->status === \App\Models\ServiceRequest::STATUS_COMPLETED ? 'success' : ($serviceRequest->status === \App\Models\ServiceRequest::STATUS_REJECTED ? 'danger' : 'warning') }}">
                                            {{ $serviceRequest->status }}
                                        </span>
                                    </td>
                                    <td>{{ $serviceRequest->created_at?->format('d M Y') }}</td>
                                    <td>
                                        <a href="{{ route('citizen.requests.show', $serviceRequest) }}" class="btn btn-sm btn-outline-primary">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No requests yet. Browse services to submit your first request.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card" id="notifications">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <a href="{{ route('citizen.services.index') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-concierge-bell mr-2 text-primary"></i> Browse Services
                        </a>
                        <a href="#recent-requests" class="list-group-item list-group-item-action">
                            <i class="fas fa-file-alt mr-2 text-info"></i> Review My Requests
                        </a>
                        <a href="#appointments" class="list-group-item list-group-item-action">
                            <i class="fas fa-calendar-check mr-2 text-success"></i> Upcoming Appointments
                        </a>
                    </div>
                </div>
            </div>

            <div class="card" id="profile">
                <div class="card-header">
                    <h3 class="card-title">Profile</h3>
                </div>
                <div class="card-body">
                    <strong>{{ Auth::user()->name }}</strong>
                    <p class="text-muted mb-0">{{ Auth::user()->email }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
