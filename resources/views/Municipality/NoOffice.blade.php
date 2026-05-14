@extends('layouts.municipality')

@section('title', 'No Office Assigned')
@section('page-title', 'No Government Office Assigned')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Office Assignment Required</h3>
        </div>
        <div class="card-body">
            <p>Your account is not assigned to any government office yet.</p>
            <p class="mb-0">Please contact the admin.</p>
        </div>
    </div>
@endsection
