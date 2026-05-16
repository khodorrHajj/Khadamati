@extends('layouts.citizen')

@section('title', 'Payment History')
@section('page-title', 'My Payments')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Payment History</h3>
            <span class="badge badge-secondary">{{ $payments->total() }} payment(s)</span>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Service</th>
                        <th>Office</th>
                        <th>Amount (LBP)</th>
                        <th>Amount (USD)</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Request</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td>{{ $payment->id }}</td>
                            <td>{{ $payment->service?->name ?? '-' }}</td>
                            <td>{{ $payment->service?->governmentOffice?->name ?? '-' }}</td>
                            <td class="font-weight-bold">
                                LBP {{ number_format((float) $payment->price_amount, 0) }}
                            </td>
                            <td>
                                @if ($payment->price_amount_usd)
                                    ${{ number_format((float) $payment->price_amount_usd, 2) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @php
                                    $badgeClass = match($payment->status) {
                                        'completed' => 'badge-success',
                                        'pending'   => 'badge-warning',
                                        'failed'    => 'badge-danger',
                                        default     => 'badge-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ ucfirst($payment->status) }}</span>
                            </td>
                            <td>{{ $payment->created_at?->format('d M Y, H:i') }}</td>
                            <td>
                                @if ($payment->service_request_id)
                                    <a href="{{ route('citizen.requests.show', $payment->service_request_id) }}" class="btn btn-sm btn-outline-primary">
                                        View Request
                                    </a>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">You have no payment records yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($payments->hasPages())
            <div class="card-footer">
                {{ $payments->links() }}
            </div>
        @endif
    </div>
@endsection
