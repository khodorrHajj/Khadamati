@extends('template.base')

@section('title', 'Track Request')

@section('body')
<body class="hold-transition bg-light">
    <div class="container py-5">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-4">
                    <h1 class="h3 mb-2">Track Request</h1>
                    <p class="text-muted mb-0">Use the tracking code or QR below to check the latest public request status.</p>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Tracking Code: {{ $serviceRequest->tracking_code }}</h3>
                        <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-sm">Back to Login</a>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th style="width: 220px;">Request Status</th>
                                    <td>{{ $serviceRequest->status }}</td>
                                </tr>
                                <tr>
                                    <th>Service Name</th>
                                    <td>{{ $serviceRequest->service?->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Office Name</th>
                                    <td>{{ $serviceRequest->service?->governmentOffice?->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Last Updated</th>
                                    <td>{{ optional($serviceRequest->updated_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="alert alert-light border mb-0">
                            Public tracking intentionally hides private citizen information.
                        </div>

                        <div class="mt-3">
                            @include('shared.request-tracking-qr', [
                                'serviceRequest' => $serviceRequest,
                                'title' => 'Scan This Tracking QR',
                            ])
                        </div>

                        @auth
                            @if (auth()->user()->hasRole('citizen') && auth()->id() === $serviceRequest->user_id)
                                <div class="mt-3">
                                    <a href="{{ route('citizen.requests.show', $serviceRequest) }}" class="btn btn-primary btn-sm">
                                        Manage My Documents
                                    </a>
                                </div>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
@endsection
