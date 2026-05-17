@extends('layouts.municipality')

@section('title', 'Payment History')
@section('page-title', 'Payment History')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filter Payments</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('municipality.payments.index') }}">
                <div class="row align-items-end">
                    <div class="col-md-5">
                        <div class="form-group mb-0">
                            <label>Citizen</label>
                            <select name="user" class="form-control">
                                <option value="">All Citizens</option>
                                @foreach ($citizens as $citizen)
                                    <option value="{{ $citizen->id }}" {{ (string) ($filters['user'] ?? '') === (string) $citizen->id ? 'selected' : '' }}>
                                        {{ $citizen->name }} ({{ $citizen->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label>&nbsp;</label>
                            <div class="d-flex">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                                @if (!empty($filters['user']))
                                    <a href="{{ route('municipality.payments.index') }}" class="btn btn-outline-secondary ml-2">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Stripe Payments — {{ $office->name }}</h3>
            <span class="badge badge-secondary">{{ $payments->total() }} record(s)</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Citizen</th>
                            <th>Service</th>
                            <th>Amount (LBP)</th>
                            <th>Amount (USD)</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            <tr>
                                <td>{{ $payment->id }}</td>
                                <td>
                                    <div>{{ $payment->user?->name ?? '-' }}</div>
                                    <small class="text-muted">{{ $payment->user?->email }}</small>
                                </td>
                                <td>{{ $payment->service?->name ?? '-' }}</td>
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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No payments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($payments->hasPages())
            <div class="card-footer">
                {{ $payments->links() }}
            </div>
        @endif
    </div>
@endsection
