@extends('layouts.citizen')

@section('title', 'Citizen Dashboard')
@section('page-title', 'Citizen Dashboard')

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h2 class="h4">Welcome, {{ Auth::user()->name }}</h2>
                    <p class="text-muted mb-0">Browse active offices, review available services, submit a request, and keep the tracking code for follow-up.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>Services</h3>
                    <p>Browse Offices</p>
                </div>
                <div class="icon">
                    <i class="fas fa-building"></i>
                </div>
                <a href="{{ route('citizen.offices.index') }}" class="small-box-footer">
                    Open <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">How Tracking Works</h3>
        </div>
        <div class="card-body">
            <ol class="mb-0 pl-3">
                <li>Choose an active office and service.</li>
                <li>Submit your request and upload supporting documents.</li>
                <li>Save the generated tracking code.</li>
                <li>Use the public tracking page to check status updates later.</li>
            </ol>
        </div>
    </div>
@endsection
