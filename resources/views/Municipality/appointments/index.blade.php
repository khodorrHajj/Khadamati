@extends('layouts.municipality')

@section('title', 'Appointments')
@section('page-title', 'Appointments')

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create Time Slot</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('municipality.appointments.slots.store') }}">
                        @csrf

                        <div class="form-group">
                            <label>Starts At</label>
                            <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" class="form-control @error('starts_at') is-invalid @enderror">
                            @error('starts_at')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Ends At</label>
                            <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" class="form-control @error('ends_at') is-invalid @enderror">
                            @error('ends_at')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Create Slot</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Time Slots for {{ $office->name }}</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Starts</th>
                                <th>Ends</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($slots as $slot)
                                <tr>
                                    <td>{{ optional($slot->starts_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                    <td>{{ optional($slot->ends_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                    <td>
                                        <span class="badge {{ $slot->is_available ? 'badge-success' : 'badge-secondary' }}">
                                            {{ $slot->is_available ? 'Available' : 'Reserved' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">No time slots created yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Appointments</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Citizen</th>
                        <th>Service</th>
                        <th>Scheduled Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($appointments as $appointment)
                        <tr>
                            <td>#{{ $appointment->service_request_id }}</td>
                            <td>{{ $appointment->serviceRequest?->user?->name ?? 'Unknown Citizen' }}</td>
                            <td>{{ $appointment->serviceRequest?->service?->name ?? '-' }}</td>
                            <td>{{ optional($appointment->timeSlot?->starts_at)->format('Y-m-d H:i') ?: 'TBD' }}</td>
                            <td><span class="badge badge-light border">{{ $appointment->status }}</span></td>
                            <td>
                                <a href="{{ route('municipality.requests.show', $appointment->serviceRequest) }}" class="btn btn-primary btn-sm">Open Request</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No appointments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
