@extends('layouts.auth')

@section('title', 'Login')
@section('auth-box-class', 'w-100 px-3')

@section('content')
    <div class="row justify-content-center">
        <div class="col-xl-5 col-lg-6 col-md-8">
            <div class="login-logo">
                <a href="{{ route('login') }}"><strong>{{ config('app.name', 'Khadamati') }}</strong></a>
            </div>

            <div class="card card-outline card-primary">
                <div class="card-body login-card-body">
                    <p class="login-box-msg">Sign in to start your session</p>

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

                    <form method="POST" action="{{ route('dologin') }}">
                        @csrf

                        <div class="input-group mb-3">
                            <input
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                class="form-control @error('email') is-invalid @enderror"
                                placeholder="Email"
                            >
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-envelope"></span>
                                </div>
                            </div>
                            @error('email')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="input-group mb-3">
                            <input
                                type="password"
                                name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="Password"
                            >
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-lock"></span>
                                </div>
                            </div>
                            @error('password')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Login</button>
                    </form>

                    <a href="{{ route('google.redirect') }}" class="btn btn-danger btn-block mt-3">
                        <i class="fab fa-google mr-2"></i>
                        Login with Google
                    </a>

                    <p class="mb-0 mt-3">
                        Don't have an account?
                        <a href="{{ route('signup') }}">Register</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
