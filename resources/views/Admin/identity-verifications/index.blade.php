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
            <div class="card-tools">
                <span class="text-muted small">Auto-refreshing every 10 seconds</span>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.identity-verifications.index') }}">
                <div class="form-row">
                    <div class="col-md-6 mb-2">
                        <input type="text" name="search" value="{{ old('search', $search) }}" class="form-control" placeholder="Search by citizen, email, ID number, names, location fields, issue date, blood type, or admin notes">
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
        <div data-admin-live-region="identity-verification-queue">
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Citizen</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Extracted Name</th>
                        <th>Extracted ID</th>
                        <th>OCR Confidence</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($verifications as $verification)
                        <tr>
                            <td>{{ $verification->id }}</td>
                            <td>{{ $verification->user->name ?? 'Unknown Citizen' }}</td>
                            <td>{{ $verification->user->email ?? '-' }}</td>
                            <td>
                                <span class="badge badge-{{ $verification->status === 'approved' ? 'success' : ($verification->status === 'rejected' ? 'danger' : ($verification->status === 'needs_review' ? 'warning' : 'secondary')) }}">
                                    {{ ucwords(str_replace('_', ' ', $verification->status)) }}
                                </span>
                            </td>
                            <td>{{ $verification->extracted_full_name ?: collect([$verification->extracted_first_name, $verification->extracted_family_name])->filter()->join(' ') ?: '-' }}</td>
                            <td>{{ $verification->extracted_id_number ?: '-' }}</td>
                            <td>{{ $verification->ocr_confidence !== null ? number_format($verification->ocr_confidence * 100, 1) . '%' : '-' }}</td>
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
    </div>
@endsection
