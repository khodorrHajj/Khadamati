@extends('layouts.admin')

@section('title', 'Appointment Details')
@section('page-title', 'Appointment Details')

@section('content')
    <div class="row">
        {{-- Appointment Details --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0"><i class="fas fa-calendar-check mr-1"></i> Appointment Details</h3>
                    <a href="{{ route('admin.appointments.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Appointments
                    </a>
                </div>
                <div class="card-body">
                    {{-- Status --}}
                    <div class="mb-4">
                        @php
                            $statusConfig = [
                                'Requested' => ['color' => 'warning', 'icon' => 'clock', 'bg' => 'warning'],
                                'Approved' => ['color' => 'success', 'icon' => 'check-circle', 'bg' => 'success'],
                                'Rescheduled' => ['color' => 'primary', 'icon' => 'redo', 'bg' => 'primary'],
                                'Cancelled' => ['color' => 'danger', 'icon' => 'times-circle', 'bg' => 'danger'],
                            ];
                            $cfg = $statusConfig[$appointment->status] ?? ['color' => 'secondary', 'icon' => 'question', 'bg' => 'secondary'];
                        @endphp
                        <span class="badge badge-{{ $cfg['color'] }} px-3 py-2" style="font-size: 1rem;">
                            <i class="fas fa-{{ $cfg['icon'] }} mr-1"></i> {{ $appointment->status }}
                        </span>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            {{-- Time Slot Info --}}
                            <div class="mb-3">
                                <label class="text-muted small font-weight-bold">SCHEDULED DATE & TIME</label>
                                @if($appointment->timeSlot)
                                    <div class="p-3 bg-light rounded">
                                        <div class="font-weight-bold text-primary" style="font-size: 1.1rem;">
                                            <i class="fas fa-calendar-day mr-1"></i>
                                            {{ $appointment->timeSlot->starts_at->format('l, F d, Y') }}
                                        </div>
                                        <div class="mt-1">
                                            <i class="fas fa-clock mr-1 text-muted"></i>
                                            {{ $appointment->timeSlot->starts_at->format('H:i') }} - {{ $appointment->timeSlot->ends_at->format('H:i') }}
                                        </div>
                                    </div>
                                @else
                                    <p class="text-muted">No time slot assigned.</p>
                                @endif
                            </div>

                            {{-- Citizen --}}
                            <div class="mb-3">
                                <label class="text-muted small font-weight-bold">CITIZEN</label>
                                <p class="mb-0">
                                    <i class="fas fa-user mr-1 text-muted"></i>
                                    {{ $appointment->user?->name ?? 'N/A' }}
                                </p>
                                <p class="text-muted small mb-0">{{ $appointment->user?->email ?? 'N/A' }}</p>
                            </div>

                            {{-- Office --}}
                            <div class="mb-3">
                                <label class="text-muted small font-weight-bold">GOVERNMENT OFFICE</label>
                                <p class="mb-0">
                                    <i class="fas fa-building mr-1 text-muted"></i>
                                    {{ $appointment->governmentOffice?->name ?? 'N/A' }}
                                </p>
                                <p class="text-muted small mb-0">
                                    {{ $appointment->governmentOffice?->municipality?->name ?? 'N/A' }}
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            {{-- Service Request --}}
                            <div class="mb-3">
                                <label class="text-muted small font-weight-bold">SERVICE REQUEST</label>
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <th class="pl-0" style="width: 120px;">Tracking Code</th>
                                        <td><code>{{ $appointment->serviceRequest?->tracking_code ?? 'N/A' }}</code></td>
                                    </tr>
                                    <tr>
                                        <th class="pl-0">Service</th>
                                        <td>{{ $appointment->serviceRequest?->service?->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="pl-0">Category</th>
                                        <td>
                                            @if($appointment->serviceRequest?->service?->serviceCategory)
                                                <span class="badge badge-info">{{ $appointment->serviceRequest->service->serviceCategory->name }}</span>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="pl-0">Request Status</th>
                                        <td>
                                            @php
                                                $reqStatusColors = [
                                                    'Pending' => 'warning',
                                                    'In Review' => 'info',
                                                    'Missing Documents' => 'orange',
                                                    'Approved' => 'success',
                                                    'Rejected' => 'danger',
                                                    'Completed' => 'primary',
                                                ];
                                            @endphp
                                            <span class="badge badge-{{ $reqStatusColors[$appointment->serviceRequest?->status ?? ''] ?? 'secondary' }}">
                                                {{ $appointment->serviceRequest?->status ?? 'N/A' }}
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            {{-- Approval Info --}}
                            @if($appointment->approved_by)
                                <div class="mb-3">
                                    <label class="text-muted small font-weight-bold">APPROVED BY</label>
                                    <p class="mb-0">
                                        <i class="fas fa-user-shield mr-1 text-muted"></i>
                                        {{ $appointment->approver?->name ?? 'N/A' }}
                                    </p>
                                    <p class="text-muted small mb-0">{{ $appointment->approved_at?->format('F d, Y \a\t H:i') }}</p>
                                </div>
                            @endif

                            {{-- Cancellation Info --}}
                            @if($appointment->cancelled_at)
                                <div class="mb-3">
                                    <label class="text-muted small font-weight-bold">CANCELLED</label>
                                    <p class="text-danger mb-0">
                                        <i class="fas fa-times-circle mr-1"></i>
                                        {{ $appointment->cancelled_at->format('F d, Y \a\t H:i') }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Notes --}}
                    @if($appointment->notes)
                        <div class="mt-3">
                            <label class="text-muted small font-weight-bold">CITIZEN NOTES</label>
                            <div class="p-3 bg-light rounded">
                                {{ $appointment->notes }}
                            </div>
                        </div>
                    @endif

                    @if($appointment->municipality_notes)
                        <div class="mt-3">
                            <label class="text-muted small font-weight-bold">MUNICIPALITY NOTES</label>
                            <div class="p-3 bg-info bg-light rounded">
                                {{ $appointment->municipality_notes }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Timeline --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-stream mr-1"></i> Timeline</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="timeline">
                        {{-- Created --}}
                        <li class="time-label">
                            <span class="bg-secondary">{{ $appointment->created_at->format('M d, Y') }}</span>
                        </li>
                        <li>
                            <i class="fas fa-calendar-plus bg-info"></i>
                            <div class="timeline-item">
                                <span class="time"><i class="fas fa-clock"></i> {{ $appointment->created_at->format('H:i') }}</span>
                                <h3 class="timeline-header">Appointment Requested</h3>
                                <div class="timeline-body">
                                    By <strong>{{ $appointment->user?->name ?? 'N/A' }}</strong>
                                </div>
                            </div>
                        </li>

                        @if($appointment->approved_at)
                            <li>
                                <i class="fas fa-check bg-success"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fas fa-clock"></i> {{ $appointment->approved_at->format('H:i') }}</span>
                                    <h3 class="timeline-header">Appointment Approved</h3>
                                    <div class="timeline-body">
                                        By <strong>{{ $appointment->approver?->name ?? 'N/A' }}</strong>
                                    </div>
                                </div>
                            </li>
                        @endif

                        @if($appointment->status === 'Rescheduled')
                            <li>
                                <i class="fas fa-redo bg-primary"></i>
                                <div class="timeline-item">
                                    <h3 class="timeline-header">Appointment Rescheduled</h3>
                                </div>
                            </li>
                        @endif

                        @if($appointment->cancelled_at)
                            <li>
                                <i class="fas fa-times bg-danger"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fas fa-clock"></i> {{ $appointment->cancelled_at->format('H:i') }}</span>
                                    <h3 class="timeline-header">Appointment Cancelled</h3>
                                </div>
                            </li>
                        @endif

                        {{-- Reminder Info --}}
                        @if($appointment->reminder_sent_at)
                            <li>
                                <i class="fas fa-bell bg-warning"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fas fa-clock"></i> {{ $appointment->reminder_sent_at->format('H:i') }}</span>
                                    <h3 class="timeline-header">Reminder Sent</h3>
                                </div>
                            </li>
                        @endif

                        @if($appointment->email_notified_at)
                            <li>
                                <i class="fas fa-envelope bg-secondary"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fas fa-clock"></i> {{ $appointment->email_notified_at->format('H:i') }}</span>
                                    <h3 class="timeline-header">Email Notification Sent</h3>
                                </div>
                            </li>
                        @endif

                        <li>
                            <i class="fas fa-clock bg-gray"></i>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Quick Links --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-link mr-1"></i> Quick Links</h3>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @if($appointment->serviceRequest)
                            <a href="{{ route('admin.requests.show', $appointment->serviceRequest) }}" class="list-group-item list-group-item-action">
                                <i class="fas fa-file-signature mr-2 text-info"></i> View Service Request
                            </a>
                        @endif
                        @if($appointment->user)
                            <a href="{{ route('admin.citizens.show', $appointment->user) }}" class="list-group-item list-group-item-action">
                                <i class="fas fa-user mr-2 text-primary"></i> View Citizen Profile
                            </a>
                        @endif
                        @if($appointment->governmentOffice)
                            <a href="{{ route('admin.offices.show', $appointment->governmentOffice) }}" class="list-group-item list-group-item-action">
                                <i class="fas fa-building mr-2 text-success"></i> View Government Office
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/toastr/toastr.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/adminlte/plugins/toastr/toastr.min.js') }}"></script>
    <script>
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: 4000,
        };
        @if(session('success'))
            toastr.success('{{ session('success') }}');
        @endif
    </script>
@endpush
