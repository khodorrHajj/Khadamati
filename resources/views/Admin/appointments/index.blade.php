@extends('layouts.admin')

@section('title', 'Appointments Overview')
@section('page-title', 'Appointments Overview')

@section('content')
    {{-- Stats Row --}}
    <div class="row">
        <div class="col-lg-2 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $stats['total'] }}</h3>
                    <p>Total</p>
                </div>
                <div class="icon"><i class="fas fa-calendar-check"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $stats['requested'] }}</h3>
                    <p>Requested</p>
                </div>
                <div class="icon"><i class="fas fa-clock"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $stats['approved'] }}</h3>
                    <p>Approved</p>
                </div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $stats['rescheduled'] }}</h3>
                    <p>Rescheduled</p>
                </div>
                <div class="icon"><i class="fas fa-redo"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $stats['cancelled'] }}</h3>
                    <p>Cancelled</p>
                </div>
                <div class="icon"><i class="fas fa-times-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $stats['total'] > 0 ? round(($stats['approved'] / $stats['total']) * 100) : 0 }}%</h3>
                    <p>Approval Rate</p>
                </div>
                <div class="icon"><i class="fas fa-percentage"></i></div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filters</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
            </div>
        </div>
        <form method="GET" action="{{ route('admin.appointments.index') }}">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control select2-filter">
                                <option value="">All Statuses</option>
                                <option value="Requested" {{ request('status') === 'Requested' ? 'selected' : '' }}>Requested</option>
                                <option value="Approved" {{ request('status') === 'Approved' ? 'selected' : '' }}>Approved</option>
                                <option value="Rescheduled" {{ request('status') === 'Rescheduled' ? 'selected' : '' }}>Rescheduled</option>
                                <option value="Cancelled" {{ request('status') === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Municipality</label>
                            <select name="municipality" class="form-control select2-filter">
                                <option value="">All</option>
                                @foreach ($municipalities as $municipality)
                                    <option value="{{ $municipality->id }}" {{ request('municipality') == $municipality->id ? 'selected' : '' }}>{{ $municipality->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Office</label>
                            <select name="office" class="form-control select2-filter">
                                <option value="">All</option>
                                @foreach ($offices as $office)
                                    <option value="{{ $office->id }}" {{ request('office') == $office->id ? 'selected' : '' }}>{{ $office->municipality?->name ? $office->municipality->name . ' - ' : '' }}{{ $office->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Date From</label>
                            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Date To</label>
                            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end pb-3">
                        <div class="btn-group w-100">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i> Filter</button>
                            @if(request()->hasAny(['status', 'municipality', 'office', 'date_from', 'date_to', 'search']))
                                <a href="{{ route('admin.appointments.index') }}" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by citizen name or tracking code...">
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- Appointments Table --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0"><i class="fas fa-calendar-alt mr-1"></i> All Appointments</h3>
            <span class="text-muted small">{{ $appointments->total() }} appointments</span>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th style="width: 120px;">Tracking Code</th>
                        <th>Citizen</th>
                        <th>Service</th>
                        <th>Office</th>
                        <th>Date & Time</th>
                        <th style="width: 110px;">Status</th>
                        <th>Approved By</th>
                        <th style="width: 80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($appointments as $appt)
                        <tr>
                            <td><code>{{ $appt->serviceRequest?->tracking_code ?? 'N/A' }}</code></td>
                            <td class="font-weight-bold">{{ $appt->user?->name ?? 'N/A' }}</td>
                            <td>{{ $appt->serviceRequest?->service?->name ?? 'N/A' }}</td>
                            <td>{{ $appt->governmentOffice?->name ?? 'N/A' }}</td>
                            <td>
                                @if($appt->timeSlot)
                                    <div class="font-weight-bold">{{ $appt->timeSlot->starts_at->format('M d, Y') }}</div>
                                    <div class="text-muted small">{{ $appt->timeSlot->starts_at->format('H:i') }} - {{ $appt->timeSlot->ends_at->format('H:i') }}</div>
                                @else
                                    <span class="text-muted">No time slot</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusConfig = [
                                        'Requested' => ['color' => 'warning', 'icon' => 'clock'],
                                        'Approved' => ['color' => 'success', 'icon' => 'check-circle'],
                                        'Rescheduled' => ['color' => 'primary', 'icon' => 'redo'],
                                        'Cancelled' => ['color' => 'danger', 'icon' => 'times-circle'],
                                    ];
                                    $cfg = $statusConfig[$appt->status] ?? ['color' => 'secondary', 'icon' => 'question'];
                                @endphp
                                <span class="badge badge-{{ $cfg['color'] }}">
                                    <i class="fas fa-{{ $cfg['icon'] }} mr-1"></i>{{ $appt->status }}
                                </span>
                            </td>
                            <td>
                                @if($appt->approver)
                                    {{ $appt->approver->name }}
                                    <div class="text-muted small">{{ $appt->approved_at?->format('M d, Y') }}</div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.appointments.show', $appt) }}" class="btn btn-outline-info btn-sm" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-calendar-times fa-2x mb-2 d-block text-muted"></i>
                                No appointments found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($appointments->hasPages())
            <div class="card-footer">
                {{ $appointments->links() }}
            </div>
        @endif
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('.select2-filter').select2({
                theme: 'bootstrap4',
                placeholder: 'Select...',
                allowClear: true,
            });
        });
    </script>
@endpush
