@extends('layouts.municipality')

@section('title', 'Municipality Dashboard')
@section('page-title', 'Municipality Dashboard')

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Welcome</h3>
                </div>
                <div class="card-body">
                    <p class="mb-2">Welcome, {{ Auth::user()->name }}</p>
                    <p class="mb-0">Manage service categories and services for {{ $office->name }}.</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Office</h3>
                </div>
                <div class="card-body">
                    <span class="badge badge-primary">{{ $office->name }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totalCategories }}</h3>
                    <p>Total Categories</p>
                </div>
                <div class="icon">
                    <i class="fas fa-list"></i>
                </div>
                <a href="{{ route('municipality.categories') }}" class="small-box-footer">
                    Open <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $totalServices }}</h3>
                    <p>Total Services</p>
                </div>
                <div class="icon">
                    <i class="fas fa-concierge-bell"></i>
                </div>
                <a href="{{ route('municipality.services') }}" class="small-box-footer">
                    Open <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $totalRequests }}</h3>
                    <p>Total Requests</p>
                </div>
                <div class="icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <span class="small-box-footer">Coming Soon</span>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $pendingRequests }}</h3>
                    <p>Pending Requests</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
                <span class="small-box-footer">Coming Soon</span>
            </div>
        </div>
    </div>
@endsection
