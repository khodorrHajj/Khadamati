@extends('layouts.admin')

@section('title', 'Feedback Details')
@section('page-title', 'Feedback Details')

@section('content')
    <div class="row">
        {{-- Feedback Details --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0"><i class="fas fa-comment-dots mr-1"></i> Feedback Details</h3>
                    <a href="{{ route('admin.feedback.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Feedback
                    </a>
                </div>
                <div class="card-body">
                    {{-- Rating --}}
                    <div class="mb-3">
                        <label class="text-muted small font-weight-bold">RATING</label>
                        <div class="d-flex align-items-center">
                            <div class="mr-2" style="font-size: 1.5rem;">
                                @foreach(range(1, 5) as $star)
                                    <i class="fas fa-star {{ $star <= $feedback->rating ? 'text-warning' : 'text-muted' }}"></i>
                                @endforeach
                            </div>
                            <span class="badge badge-{{ $feedback->rating >= 4 ? 'success' : ($feedback->rating >= 3 ? 'warning' : 'danger') }} px-3 py-2" style="font-size: 1rem;">
                                {{ $feedback->rating }}/5
                            </span>
                        </div>
                    </div>

                    {{-- Citizen --}}
                    <div class="mb-3">
                        <label class="text-muted small font-weight-bold">CITIZEN</label>
                        <p class="mb-0">
                            <i class="fas fa-user mr-1 text-muted"></i>
                            {{ $feedback->user?->name ?? 'N/A' }}
                            <span class="text-muted ml-1">({{ $feedback->user?->email ?? 'N/A' }})</span>
                        </p>
                    </div>

                    {{-- Comment --}}
                    <div class="mb-3">
                        <label class="text-muted small font-weight-bold">COMMENT</label>
                        <div class="p-3 bg-light rounded">
                            {{ $feedback->comment }}
                        </div>
                    </div>

                    {{-- Service Request Info --}}
                    <div class="mb-3">
                        <label class="text-muted small font-weight-bold">SERVICE REQUEST</label>
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <th class="pl-0" style="width: 120px;">Tracking Code</th>
                                <td><code>{{ $feedback->serviceRequest?->tracking_code ?? 'N/A' }}</code></td>
                            </tr>
                            <tr>
                                <th class="pl-0">Service</th>
                                <td>{{ $feedback->serviceRequest?->service?->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="pl-0">Category</th>
                                <td>
                                    @if($feedback->serviceRequest?->service?->serviceCategory)
                                        <span class="badge badge-info">{{ $feedback->serviceRequest->service->serviceCategory->name }}</span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="pl-0">Office</th>
                                <td>{{ $feedback->serviceRequest?->service?->governmentOffice?->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="pl-0">Municipality</th>
                                <td>{{ $feedback->serviceRequest?->service?->governmentOffice?->municipality?->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="pl-0">Request Status</th>
                                <td>
                                    @php
                                        $statusColors = [
                                            'Pending' => 'warning',
                                            'In Review' => 'info',
                                            'Missing Documents' => 'orange',
                                            'Approved' => 'success',
                                            'Rejected' => 'danger',
                                            'Completed' => 'primary',
                                        ];
                                    @endphp
                                    <span class="badge badge-{{ $statusColors[$feedback->serviceRequest?->status ?? ''] ?? 'secondary' }}">
                                        {{ $feedback->serviceRequest?->status ?? 'N/A' }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>

                    {{-- Submitted Date --}}
                    <div class="mb-0">
                        <label class="text-muted small font-weight-bold">SUBMITTED</label>
                        <p class="mb-0"><i class="fas fa-calendar mr-1 text-muted"></i> {{ $feedback->created_at->format('F d, Y \a\t H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Response Panel --}}
        <div class="col-lg-4">
            @if($feedback->responded_at)
                {{-- Existing Response --}}
                <div class="card card-success card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-reply mr-1"></i> Response</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="text-muted small font-weight-bold">PUBLIC RESPONSE</label>
                            <div class="p-2 bg-light rounded">
                                {{ $feedback->public_response ?? 'No public response provided.' }}
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small font-weight-bold">PRIVATE RESPONSE (Internal)</label>
                            <div class="p-2 bg-light rounded">
                                {{ $feedback->private_response ?? 'No private response provided.' }}
                            </div>
                        </div>
                        <hr>
                        <p class="text-muted small mb-1">
                            <i class="fas fa-user-shield mr-1"></i>
                            Responded by: <strong>{{ $feedback->responder?->name ?? 'N/A' }}</strong>
                        </p>
                        <p class="text-muted small mb-0">
                            <i class="fas fa-clock mr-1"></i>
                            {{ $feedback->responded_at->format('F d, Y \a\t H:i') }}
                        </p>
                    </div>
                </div>
            @else
                {{-- Respond Form --}}
                <div class="card card-warning card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-pen mr-1"></i> Respond to Feedback</h3>
                    </div>
                    <form method="POST" action="{{ route('admin.feedback.respond', $feedback) }}">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label>Public Response</label>
                                <textarea name="public_response" rows="4" class="form-control @error('public_response') is-invalid @enderror" placeholder="This response will be visible to the citizen...">{{ old('public_response') }}</textarea>
                                @error('public_response')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                                <small class="text-muted">Visible to the citizen who submitted the feedback.</small>
                            </div>
                            <div class="form-group">
                                <label>Private Response (Internal)</label>
                                <textarea name="private_response" rows="4" class="form-control @error('private_response') is-invalid @enderror" placeholder="Internal notes, not visible to the citizen...">{{ old('private_response') }}</textarea>
                                @error('private_response')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                                <small class="text-muted">Only visible to admins and municipality users.</small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-paper-plane mr-1"></i> Submit Response
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- Quick Actions --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-link mr-1"></i> Quick Links</h3>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @if($feedback->serviceRequest)
                            <a href="{{ route('admin.requests.show', $feedback->serviceRequest) }}" class="list-group-item list-group-item-action">
                                <i class="fas fa-file-signature mr-2 text-info"></i> View Service Request
                            </a>
                        @endif
                        @if($feedback->user)
                            <a href="{{ route('admin.citizens.show', $feedback->user) }}" class="list-group-item list-group-item-action">
                                <i class="fas fa-user mr-2 text-primary"></i> View Citizen Profile
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/toastr/toastr.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/adminlte/plugins/toastr/toastr.min.js') }}"></script>
    <script>
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: 4000,
        };
        @if(session('success'))
            toastr.success('{{ session('success') }}');
        @endif
        @if(session('error'))
            toastr.error('{{ session('error') }}');
        @endif
    </script>
@endpush
