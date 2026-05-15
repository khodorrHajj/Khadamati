@extends('layouts.auth')

@section('title', 'ID Verification')

@section('content')
    <div class="login-logo">
        <a href="{{ route('identity.verification.create') }}"><b>E</b>-Services</a>
    </div>

    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg">Upload your ID to complete signup</p>

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

            @if ($verification)
                <div class="alert alert-info">
                    Current verification status: <strong>{{ str_replace('_', ' ', ucfirst($verification->status)) }}</strong>
                </div>

                @if ($verification->status === \App\Models\IdentityVerification::STATUS_APPROVED)
                    <a href="{{ route('citizen.dashboard') }}" class="btn btn-primary btn-block">Go to Dashboard</a>
                @elseif ($verification->status === \App\Models\IdentityVerification::STATUS_REJECTED && $verification->admin_notes)
                    <div class="alert alert-warning">{{ $verification->admin_notes }}</div>
                @endif
            @endif

            @if (!$verification || in_array($verification->status, [
                \App\Models\IdentityVerification::STATUS_PENDING_UPLOAD,
                \App\Models\IdentityVerification::STATUS_REJECTED,
                \App\Models\IdentityVerification::STATUS_FAILED,
            ], true))
                <form method="POST" action="{{ route('identity.verification.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <label>ID Image</label>
                        <input type="file" name="id_image" class="form-control-file @error('id_image') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <small class="form-text text-muted">Upload a clear Lebanese ID image. JPG, PNG, or WebP only. Maximum size is 5 MB.</small>
                        @error('id_image')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Submit ID for Review</button>
                </form>
            @else
                <p class="text-muted mb-0">Your ID is waiting for admin review. You will be able to access citizen pages after approval.</p>
            @endif

            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="btn btn-secondary btn-block">Logout</button>
            </form>
        </div>
    </div>
@endsection
