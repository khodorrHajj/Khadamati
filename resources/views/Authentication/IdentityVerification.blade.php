@extends('layouts.auth')

@section('title', 'ID Verification')
@section('auth-box-class', 'w-100 px-3')

@section('content')
    @php
        $showUploadForm = !$verification || in_array($verification->status, [
            \App\Models\IdentityVerification::STATUS_PENDING_UPLOAD,
            \App\Models\IdentityVerification::STATUS_REJECTED,
            \App\Models\IdentityVerification::STATUS_FAILED,
        ], true);
        $waitingForReview = $verification && !$showUploadForm;
        $statusMessage = match ($verification?->status) {
            \App\Models\IdentityVerification::STATUS_PROCESSING => 'Your ID is getting processed.',
            \App\Models\IdentityVerification::STATUS_NEEDS_REVIEW => 'Your ID is getting processed.',
            \App\Models\IdentityVerification::STATUS_REJECTED => 'Your ID verification was rejected. Please review the admin note and upload new images.',
            \App\Models\IdentityVerification::STATUS_FAILED => 'We could not process your ID automatically. Please upload the images again.',
            default => 'Upload your ID to complete signup.',
        };
    @endphp

    <div class="row justify-content-center">
        <div class="col-xl-7 col-lg-8 col-md-10">
            <div class="login-logo">
                <a href="{{ route('identity.verification.create') }}"><strong>{{ config('app.name', 'Khadamati') }}</strong></a>
                <p class="text-muted mb-0 mt-2 h6">Citizen ID Verification</p>
            </div>

            <div class="card card-outline card-primary">
                <div class="card-header text-center">
                    <span class="h4 mb-0"><i class="fas fa-id-card mr-2 text-primary"></i>Verify Your Lebanese ID</span>
                </div>
                <div class="card-body register-card-body">
                    <p class="login-box-msg mb-4">Upload both sides of your ID so we can complete your account review.</p>

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

                    <div class="callout callout-info">
                        <h5><i class="fas fa-camera mr-2"></i>Before You Upload</h5>
                        <p class="mb-0">Use clear photos, show the full card, and upload the front and back as separate images.</p>
                    </div>

                    @if ($verification)
                        <div class="alert alert-info" id="identity-verification-status-alert">
                            Current verification status: <strong>{{ str_replace('_', ' ', ucfirst($verification->status)) }}</strong>
                        </div>

                        @if ($verification->status === \App\Models\IdentityVerification::STATUS_REJECTED && $verification->admin_notes)
                            <div class="alert alert-warning">{{ $verification->admin_notes }}</div>
                        @endif
                    @endif

                    @if ($showUploadForm)
                        <form method="POST" action="{{ route('identity.verification.store') }}" enctype="multipart/form-data">
                            @csrf

                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-primary"><i class="far fa-image"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Front Side</span>
                                    <span class="info-box-number text-sm">Name, family name, parents, place of birth, date of birth, and ID number</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="id_image_front">ID Front Image</label>
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input id="id_image_front" type="file" name="id_image_front" class="custom-file-input @error('id_image_front') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-file-input data-default-label="Choose front image">
                                        <label class="custom-file-label" for="id_image_front">Choose front image</label>
                                    </div>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-default" data-file-clear data-file-target="id_image_front">
                                            <i class="fas fa-times mr-1"></i>Remove
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">JPG, PNG, or WebP only. Maximum size is 5 MB.</small>
                                @error('id_image_front')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-warning"><i class="fas fa-reply"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Back Side</span>
                                    <span class="info-box-number text-sm">Gender, family status, record number, locality, governorate, district, issue date, and blood type</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="id_image_back">ID Back Image</label>
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input id="id_image_back" type="file" name="id_image_back" class="custom-file-input @error('id_image_back') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-file-input data-default-label="Choose back image">
                                        <label class="custom-file-label" for="id_image_back">Choose back image</label>
                                    </div>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-default" data-file-clear data-file-target="id_image_back">
                                            <i class="fas fa-times mr-1"></i>Remove
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">JPG, PNG, or WebP only. Maximum size is 5 MB.</small>
                                @error('id_image_back')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-upload mr-2"></i>Submit ID for Review
                            </button>
                        </form>
                    @else
                        <div class="info-box bg-light mb-3">
                            <span class="info-box-icon bg-info"><i class="fas fa-hourglass-half"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Verification In Progress</span>
                                <span class="info-box-number text-sm">Your account will open automatically once approval is complete.</span>
                            </div>
                        </div>

                        <div class="alert alert-secondary mb-0" id="identity-verification-waiting-message"
                            data-status-url="{{ route('identity.verification.status') }}"
                            data-dashboard-url="{{ route('citizen.dashboard') }}"
                            data-polling-enabled="{{ $waitingForReview ? 'true' : 'false' }}">
                            {{ $statusMessage }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('logout') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="btn btn-default btn-block">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </button>
                    </form>
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

            const waitingMessage = document.getElementById('identity-verification-waiting-message');
            const statusAlert = document.getElementById('identity-verification-status-alert');

            if (!waitingMessage || waitingMessage.dataset.pollingEnabled !== 'true') {
                return;
            }

            const statusUrl = waitingMessage.dataset.statusUrl;
            const dashboardUrl = waitingMessage.dataset.dashboardUrl;
            let polling = false;

            const refreshStatus = async () => {
                if (polling || document.hidden) {
                    return;
                }

                polling = true;

                try {
                    const response = await fetch(statusUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        return;
                    }

                    const payload = await response.json();

                    if (statusAlert && payload.status) {
                        statusAlert.innerHTML = 'Current verification status: <strong>' + payload.status.replace(/_/g, ' ') + '</strong>';
                    }

                    if (payload.message) {
                        waitingMessage.textContent = payload.message;
                    }

                    if (payload.should_redirect) {
                        window.location.assign(payload.redirect_url || dashboardUrl);
                    }
                } catch (error) {
                    // Waiting page polling should fail quietly.
                } finally {
                    polling = false;
                }
            };

            window.setInterval(refreshStatus, 5000);

            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    refreshStatus();
                }
            });
        })();
    </script>
@endpush
