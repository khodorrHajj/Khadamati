@extends('layouts.citizen')

@section('title', 'Payment Received')
@section('page-title', 'Payment Received')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h4 class="mb-3">Payment Successful!</h4>
                    <p class="text-muted mb-4">
                        Your payment has been received. Your service request will be created shortly
                        and you will be able to track it from your dashboard.
                    </p>
                    <a href="{{ route('citizen.dashboard') }}" class="btn btn-primary">
                        Go to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
