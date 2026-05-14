@extends('layouts.citizen')

@section('title', 'Track Request')
@section('page-title', 'Track Request')

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Tracking Code: {{ $serviceRequest->tracking_code }}</h3>
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

                    @auth
                        @if (auth()->user()->hasRole('citizen') && auth()->id() === $serviceRequest->user_id)
                            <div class="mt-3">
                                <a href="{{ route('citizen.requests.show', $serviceRequest) }}" class="btn btn-primary btn-sm">
                                    Manage My Documents
                                </a>
                            </div>
                        @endif
                    @endauth

                    {{-- TODO: Install a QR package such as simplesoftwareio/simple-qrcode to render a QR code for this public tracking URL. --}}
                </div>
            </div>
        </div>
    </div>
@endsection
