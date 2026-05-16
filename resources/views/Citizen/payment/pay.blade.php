@extends('layouts.citizen')

@section('title', 'Pay with Card')
@section('page-title', 'Pay with Card')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Service Details</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th style="width: 180px;">Service</th>
                                <td>{{ $service->name }}</td>
                            </tr>
                            <tr>
                                <th>Office</th>
                                <td>{{ $service->governmentOffice?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Municipality</th>
                                <td>{{ $service->governmentOffice?->municipality?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Category</th>
                                <td>{{ $service->serviceCategory?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td>{{ $service->description ?: 'No description available.' }}</td>
                            </tr>
                            <tr>
                                <th>Estimated Duration</th>
                                <td>{{ $service->duration_days }} day{{ $service->duration_days === 1 ? '' : 's' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Payment Summary</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered mb-4">
                        <tbody>
                            <tr>
                                <th style="width: 140px;">Amount (LBP)</th>
                                <td class="font-weight-bold">{{ $service->formattedPrice() }}</td>
                            </tr>
                            <tr>
                                <th>Amount (USD)</th>
                                <td class="font-weight-bold">${{ number_format($priceUsd, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Exchange Rate</th>
                                <td class="text-muted">1 USD = {{ number_format($exchangeRate, 0) }} LBP</td>
                            </tr>
                            <tr>
                                <th>Payment Method</th>
                                <td>Credit / Debit Card (via Stripe)</td>
                            </tr>
                        </tbody>
                    </table>

                    <p class="text-muted small mb-4">
                        You will be redirected to Stripe to complete your payment securely.
                        Once your payment is confirmed, your service request will be created automatically.
                    </p>

                    <form method="POST" action="{{ route('citizen.payment.create', $service) }}">
                        @csrf
                        <button type="submit" class="btn btn-success btn-block">
                            Proceed to Secure Payment
                        </button>
                    </form>

                    <a href="{{ route('citizen.services.show', $service) }}" class="btn btn-link btn-block mt-2">
                        Back to Service
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
