@extends('layouts.municipality')

@section('title', 'No Office Assigned')
@section('page-title', 'No Government Office Assigned')

@push('styles')
<style>
    .no-office-card {
        border: none;
        border-radius: 0.75rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        text-align: center;
        padding: 3rem 2rem;
    }
    .no-office-card .icon-wrap {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #edf2f7, #e2e8f0);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
    }
    .no-office-card .icon-wrap i {
        font-size: 2rem;
        color: #718096;
    }
    .no-office-card h3 {
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 0.75rem;
    }
    .no-office-card p {
        color: #718096;
        font-size: 0.95rem;
        max-width: 400px;
        margin: 0 auto;
    }
</style>
@endpush

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card no-office-card">
                <div class="icon-wrap">
                    <i class="fas fa-building"></i>
                </div>
                <h3>No Office Assigned</h3>
                <p>Your account is not assigned to any government office yet. Please contact the administrator to get assigned to an office.</p>
            </div>
        </div>
    </div>
@endsection