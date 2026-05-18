@extends('layouts.citizen')

@section('title', 'My Profile')
@section('page-title', 'My Profile')

@section('content')
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="row">
        <div class="col-lg-5">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-id-badge mr-2"></i>Account Information</h3>
                </div>
                <div class="card-body box-profile">
                    <h3 class="profile-username text-center">{{ $user->name }}</h3>
                    <p class="text-muted text-center mb-4">{{ ucfirst($user->role?->role ?? 'citizen') }}</p>

                    <ul class="list-group list-group-unbordered mb-0">
                        <li class="list-group-item">
                            <b>Email</b> <span class="float-right">{{ $user->email }}</span>
                        </li>
                        <li class="list-group-item">
                            <b>Phone</b> <span class="float-right">{{ $user->phone ?: 'Not added' }}</span>
                        </li>
                        <li class="list-group-item">
                            <b>Status</b>
                            <span class="float-right">
                                <span class="badge badge-{{ $user->is_active ? 'success' : 'secondary' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </span>
                        </li>
                        <li class="list-group-item">
                            <b>Joined</b> <span class="float-right">{{ optional($user->created_at)->format('d M Y') ?: '-' }}</span>
                        </li>
                        <li class="list-group-item">
                            <b>ID Verification</b>
                            <span class="float-right">{{ $user->latestIdentityVerification?->status ? ucwords(str_replace('_', ' ', $user->latestIdentityVerification->status)) : 'Not started' }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-key mr-2"></i>Change Password</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">Enter your current password and the new password you want. We will send a verification code to your email before changing it.</p>

                    <form method="POST" action="{{ route('citizen.profile.password.send-otp') }}" class="mb-4">
                        @csrf
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input id="current_password" type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror">
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input id="new_password" type="password" name="new_password" class="form-control @error('new_password') is-invalid @enderror">
                        </div>

                        <div class="form-group">
                            <label for="new_password_confirmation">Confirm New Password</label>
                            <input id="new_password_confirmation" type="password" name="new_password_confirmation" class="form-control">
                        </div>

                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-paper-plane mr-1"></i> Send Email OTP
                        </button>
                    </form>

                    @if ($pendingPasswordChange)
                        <div class="callout callout-info">
                            <h5 class="mb-2">Email Verification Pending</h5>
                            <p class="mb-0">We sent a 6-digit code to <strong>{{ $user->email }}</strong>. Enter it below to confirm your password change.</p>
                        </div>

                        <form method="POST" action="{{ route('citizen.profile.password.confirm-otp') }}" class="mb-3">
                            @csrf
                            <div class="form-group">
                                <label for="password_otp">Verification Code</label>
                                <input id="password_otp" type="text" name="password_otp" maxlength="6" class="form-control @error('password_otp') is-invalid @enderror" placeholder="Enter the 6-digit code">
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check mr-1"></i> Confirm Password Change
                            </button>
                        </form>

                        <form method="POST" action="{{ route('citizen.profile.password.resend-otp') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-redo mr-1"></i> Resend OTP
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="card card-danger card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-times mr-2"></i>Delete Account</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">This permanently deletes your account and all linked citizen data that is removed with it. This action cannot be undone.</p>

                    <form method="POST" action="{{ route('citizen.profile.destroy') }}" onsubmit="return confirm('Are you sure you want to permanently delete your account?');">
                        @csrf
                        @method('DELETE')
                        <div class="form-group">
                            <label for="delete_password">Current Password</label>
                            <input id="delete_password" type="password" name="delete_password" class="form-control @error('delete_password') is-invalid @enderror" placeholder="Enter your current password to confirm">
                        </div>

                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash-alt mr-1"></i> Delete My Account
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
