@extends('layouts.citizen')

@section('title', 'Payment Cancelled')
@section('page-title', 'Payment Cancelled')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-times-circle text-danger" style="font-size: 4rem;"></i>
                    </div>
                    <h4 class="mb-3">Payment Cancelled</h4>
                    <p class="text-muted mb-4">
                        Your payment was cancelled and no charge was made.
                        No service request has been created.
                    </p>
                    <a href="{{ route('citizen.offices.index') }}" class="btn btn-primary">
                        Browse Services
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
