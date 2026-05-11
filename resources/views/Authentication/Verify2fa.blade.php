@extends('layouts.auth')

@section('title', 'Verify 2FA')

@section('content')
    <div class="login-logo">
        <a href="{{ route('login') }}"><b>E</b>-Services</a>
    </div>

    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg">Enter your verification code</p>

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

            <form method="POST" action="{{ route('twofactor.verify') }}">
                @csrf

                <div class="input-group mb-3">
                    <input
                        type="text"
                        name="code"
                        maxlength="6"
                        class="form-control @error('code') is-invalid @enderror"
                        placeholder="Verification Code"
                    >
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-shield-alt"></span>
                        </div>
                    </div>
                    @error('code')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary btn-block">Verify</button>
            </form>

            <form method="POST" action="{{ route('twofactor.resend') }}" class="mt-3">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-block">Resend Code</button>
            </form>
        </div>
    </div>
@endsection
