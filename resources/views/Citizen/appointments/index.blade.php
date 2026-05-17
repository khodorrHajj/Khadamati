@extends('layouts.citizen')

@section('title', 'My Appointments')
@section('page-title', 'My Appointments')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Upcoming &amp; Past Appointments</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Date &amp; Time</th>
                        <th>Service</th>
                        <th>Office</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($appointments as $appointment)
                        <tr>
                            <td>
                                @if ($appointment->timeSlot)
                                    {{ $appointment->timeSlot->starts_at->format('d M Y, H:i') }}
                                    <small class="text-muted d-block">until {{ $appointment->timeSlot->ends_at->format('H:i') }}</small>
                                @else
                                    <span class="text-muted">To be confirmed</span>
                                @endif
                            </td>
                            <td>{{ $appointment->serviceRequest?->service?->name ?? '-' }}</td>
                            <td>{{ $appointment->governmentOffice?->name ?? $appointment->serviceRequest?->service?->governmentOffice?->name ?? '-' }}</td>
                            <td>
                                @php
                                    $badgeMap = [
                                        'Requested'   => 'warning',
                                        'Approved'    => 'success',
                                        'Rescheduled' => 'info',
                                        'Cancelled'   => 'danger',
                                    ];
                                    $badge = $badgeMap[$appointment->status] ?? 'secondary';
                                @endphp
                                <span class="badge badge-{{ $badge }}">{{ $appointment->status }}</span>
                            </td>
                            <td>
                                @if ($appointment->municipality_notes)
                                    <span title="{{ $appointment->municipality_notes }}">
                                        {{ Str::limit($appointment->municipality_notes, 40) }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($appointment->serviceRequest)
                                    <a href="{{ route('citizen.requests.show', $appointment->serviceRequest) }}" class="btn btn-sm btn-outline-primary">
                                        View Request
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                You have no appointments yet. Book one from a service request detail page.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($appointments->hasPages())
            <div class="card-footer">
                {{ $appointments->links() }}
            </div>
        @endif
    </div>
@endsection
