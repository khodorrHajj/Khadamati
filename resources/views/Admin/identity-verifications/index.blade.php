@extends('layouts.admin')

@section('title', 'ID Verifications')
@section('page-title', 'ID Verification Queue')

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Citizen ID Reviews</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.identity-verifications.index') }}">
                <div class="form-row">
                    <div class="col-md-6 mb-2">
                        <input type="text" name="search" value="{{ old('search', $search) }}" class="form-control" placeholder="Search by citizen, email, national ID, first name, or family name">
                    </div>
                    <div class="col-md-3 mb-2">
                        <select name="status" class="custom-select">
                            <option value="">All statuses</option>
                            @foreach ($statuses as $statusOption)
                                <option value="{{ $statusOption }}" {{ $status === $statusOption ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $statusOption)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button type="submit" class="btn btn-primary">Search</button>
                        @if ($search || $status)
                            <a href="{{ route('admin.identity-verifications.index') }}" class="btn btn-secondary">Clear</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Citizen</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>First Name</th>
                        <th>Family Name</th>
                        <th>National ID</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($verifications as $verification)
                        <tr>
                            <td>{{ $verification->id }}</td>
                            <td>{{ $verification->pendingRegistration->name ?? $verification->uploadedBy->name ?? 'Unknown Citizen' }}</td>
                            <td>{{ $verification->pendingRegistration->email ?? $verification->uploadedBy->email ?? '-' }}</td>
                            <td>
                                <span class="badge badge-{{ $verification->status === 'approved' ? 'success' : ($verification->status === 'rejected' ? 'danger' : 'secondary') }}">
                                    {{ ucwords(str_replace('_', ' ', $verification->status)) }}
                                </span>
                            </td>
                            <td>{{ $verification->first_name_ar ?: '-' }}</td>
                            <td>{{ $verification->family_name_ar ?: '-' }}</td>
                            <td>{{ $verification->national_id_number_normalized ?: '-' }}</td>
                            <td>{{ optional($verification->created_at)->format('Y-m-d H:i') ?: '-' }}</td>
                            <td>
                                <a href="{{ route('admin.identity-verifications.show', $verification) }}" class="btn btn-info btn-sm">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">No ID verifications found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($verifications->hasPages())
            <div class="card-footer">
                {{ $verifications->links() }}
            </div>
        @endif
    </div>
@endsection
