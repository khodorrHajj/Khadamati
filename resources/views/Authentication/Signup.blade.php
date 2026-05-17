@extends('layouts.auth')

@section('title', 'Sign Up')
@section('auth-box-class', 'w-100 px-3')

@section('content')
    <div class="row justify-content-center">
        <div class="col-xl-7 col-lg-8 col-md-10">
            <div class="login-logo">
                <a href="{{ route('signup') }}"><strong>{{ config('app.name', 'Khadamati') }}</strong></a>
            </div>

            <div class="card card-outline card-primary">
                <div class="card-body register-card-body">
                    <p class="login-box-msg">Create your account</p>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="input-group mb-3">
                            <input
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                class="form-control @error('name') is-invalid @enderror"
                                placeholder="Name"
                            >
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-user"></span>
                                </div>
                            </div>
                            @error('name')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

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
                                type="text"
                                name="phone"
                                value="{{ old('phone') }}"
                                class="form-control @error('phone') is-invalid @enderror"
                                placeholder="Phone"
                            >
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-phone"></span>
                                </div>
                            </div>
                            @error('phone')
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

                        <div class="input-group mb-3">
                            <input
                                type="password"
                                name="password_confirmation"
                                class="form-control"
                                placeholder="Confirm Password"
                            >
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-lock"></span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="id_image_front">Lebanese ID front image</label>
                            <div class="input-group">
                                <div class="custom-file">
                                    <input
                                        id="id_image_front"
                                        type="file"
                                        name="id_image_front"
                                        accept="image/jpeg,image/png,image/webp"
                                        class="custom-file-input @error('id_image_front') is-invalid @enderror"
                                        data-file-input
                                        data-default-label="Choose front image"
                                    >
                                    <label class="custom-file-label" for="id_image_front">Choose front image</label>
                                </div>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-default" data-file-clear data-file-target="id_image_front">
                                        <i class="fas fa-times mr-1"></i>Remove
                                    </button>
                                </div>
                            </div>
                            @error('id_image_front')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="id_image_back">Lebanese ID back image</label>
                            <div class="input-group">
                                <div class="custom-file">
                                    <input
                                        id="id_image_back"
                                        type="file"
                                        name="id_image_back"
                                        accept="image/jpeg,image/png,image/webp"
                                        class="custom-file-input @error('id_image_back') is-invalid @enderror"
                                        data-file-input
                                        data-default-label="Choose back image"
                                    >
                                    <label class="custom-file-label" for="id_image_back">Choose back image</label>
                                </div>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-default" data-file-clear data-file-target="id_image_back">
                                        <i class="fas fa-times mr-1"></i>Remove
                                    </button>
                                </div>
                            </div>
                            @error('id_image_back')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Register</button>
                    </form>

                    <p class="mb-0 mt-3">
                        Already have an account?
                        <a href="{{ route('login') }}">Login</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const updateFileLabel = (input) => {
                const defaultLabel = input.dataset.defaultLabel || 'Choose file';
                const fileName = input.files && input.files.length
                    ? input.files[0].name
                    : defaultLabel;
                const label = input.closest('.custom-file')?.querySelector('.custom-file-label');

                if (label) {
                    label.textContent = fileName;
                }
            };

            document.querySelectorAll('[data-file-input]').forEach((input) => {
                input.addEventListener('change', (event) => {
                    updateFileLabel(event.target);
                });
            });

            document.querySelectorAll('[data-file-clear]').forEach((button) => {
                button.addEventListener('click', () => {
                    const input = document.getElementById(button.dataset.fileTarget);

                    if (!input) {
                        return;
                    }

                    input.value = '';
                    updateFileLabel(input);
                });
            });
        })();
    </script>
@endpush
