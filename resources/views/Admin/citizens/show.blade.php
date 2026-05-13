@extends('layouts.admin')

@section('title', 'Citizen Details')
@section('page-title', 'Citizen Details')

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $citizen->name }}</h3>
            <div class="card-tools">
                <a href="{{ route('admin.citizens.index') }}" class="btn btn-secondary btn-sm">
                    Back to Citizens
                </a>
            </div>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">ID</dt>
                <dd class="col-sm-9">{{ $citizen->id }}</dd>

                <dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9">{{ $citizen->name }}</dd>

                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9">{{ $citizen->email }}</dd>

                <dt class="col-sm-3">Phone</dt>
                <dd class="col-sm-9">{{ $citizen->phone ?: '-' }}</dd>

                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9">
                    @if ($citizen->status === 'active')
                        <span class="badge badge-success">Active</span>
                    @else
                        <span class="badge badge-secondary">Inactive</span>
                    @endif
                </dd>

                <dt class="col-sm-3">Created Date</dt>
                <dd class="col-sm-9">{{ $citizen->created_at ? $citizen->created_at->format('Y-m-d H:i') : '-' }}</dd>

                <dt class="col-sm-3">Updated Date</dt>
                <dd class="col-sm-9">{{ $citizen->updated_at ? $citizen->updated_at->format('Y-m-d H:i') : '-' }}</dd>
            </dl>
        </div>
        <div class="card-footer">
            @if ($citizen->status === 'active')
                <form method="POST" action="{{ route('admin.citizens.deactivate', $citizen) }}" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-secondary">Deactivate</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.citizens.activate', $citizen) }}" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success">Activate</button>
                </form>
            @endif
        </div>
    </div>
@endsection
