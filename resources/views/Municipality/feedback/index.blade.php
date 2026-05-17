@extends('layouts.municipality')

@section('title', 'Citizen Feedback')
@section('page-title', 'Citizen Feedback')

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

    {{-- Stat Cards --}}
    <div class="row">
        <div class="col-md-4">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $averageRating !== null ? number_format($averageRating, 1) : '0.0' }}</h3>
                    <p>Average Rating</p>
                </div>
                <div class="icon"><i class="fas fa-star"></i></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $feedback->total() }}</h3>
                    <p>Total Feedback</p>
                </div>
                <div class="icon"><i class="fas fa-comments"></i></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $feedback->whereNull('responded_at')->count() }}</h3>
                    <p>Awaiting Response</p>
                </div>
                <div class="icon"><i class="fas fa-reply"></i></div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0"><i class="fas fa-filter mr-1"></i> Filters</h3>
            @if ($filters['service'] || $filters['rating'])
                <a href="{{ route('municipality.feedback.index') }}" class="btn btn-secondary btn-sm">Clear</a>
            @endif
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('municipality.feedback.index') }}">
                <div class="row align-items-end">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Service</label>
                            <select name="service" class="custom-select">
                                <option value="">All Services</option>
                                @foreach ($services as $service)
                                    <option value="{{ $service->id }}" {{ (string) $filters['service'] === (string) $service->id ? 'selected' : '' }}>{{ $service->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Rating</label>
                            <select name="rating" class="custom-select">
                                <option value="">All Ratings</option>
                                @foreach (range(1, 5) as $rating)
                                    <option value="{{ $rating }}" {{ (string) $filters['rating'] === (string) $rating ? 'selected' : '' }}>{{ $rating }} Star{{ $rating === 1 ? '' : 's' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">Apply</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Feedback Items --}}
    @forelse ($feedback as $feedbackItem)
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title mb-0">{{ $feedbackItem->serviceRequest->service->name ?? '-' }}</h3>
                    <div class="small text-muted">{{ $feedbackItem->serviceRequest->service?->serviceCategory?->name ?? '-' }}</div>
                </div>
                <div>
                    <span class="badge badge-warning"><i class="fas fa-star mr-1"></i>{{ $feedbackItem->rating }}/5</span>
                    @if ($feedbackItem->responded_at)
                        <span class="badge badge-success ml-1"><i class="fas fa-check mr-1"></i>Answered</span>
                    @else
                        <span class="badge badge-warning ml-1"><i class="fas fa-clock mr-1"></i>Waiting</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Citizen:</strong> {{ $feedbackItem->user->name ?? 'Unknown' }}</div>
                    <div class="col-md-4"><strong>Request:</strong> #{{ $feedbackItem->service_request_id }}</div>
                    <div class="col-md-4"><strong>Submitted:</strong> {{ optional($feedbackItem->created_at)->format('Y-m-d H:i') ?: '-' }}</div>
                </div>

                <div class="border rounded p-3 mb-3 bg-light">
                    <strong>Citizen Comment:</strong><br>
                    {!! nl2br(e($feedbackItem->comment)) !!}
                </div>

                <div class="row">
                    <div class="col-lg-6 mb-3 mb-lg-0">
                        <div class="border rounded p-3 mb-2">
                            <strong>Public Reply (Visible to Citizen):</strong><br>
                            {!! nl2br(e($feedbackItem->public_response ?: 'No public response yet.')) !!}
                        </div>
                        <div class="border rounded p-3">
                            <strong>Internal Office Note:</strong><br>
                            {!! nl2br(e($feedbackItem->private_response ?: 'No internal note yet.')) !!}
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="border rounded">
                            <div class="bg-light p-2 border-bottom">
                                <strong><i class="fas fa-reply mr-1"></i> Reply</strong>
                            </div>
                            <div class="p-3">
                                <form method="POST" action="{{ route('municipality.feedback.update', $feedbackItem) }}">
                                    @csrf
                                    @method('PATCH')
                                    <div class="form-group">
                                        <label>Reply Visible To Citizen</label>
                                        <textarea name="public_response" rows="3" class="form-control" placeholder="Write a response the citizen can see...">{{ $feedbackItem->public_response }}</textarea>
                                        <small class="text-muted">Visible on the citizen's request page.</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Internal Office Note</label>
                                        <textarea name="private_response" rows="3" class="form-control" placeholder="Internal notes for follow-up...">{{ $feedbackItem->private_response }}</textarea>
                                        <small class="text-muted">Only visible to municipality staff.</small>
                                    </div>
                                    @if ($feedbackItem->responded_at)
                                        <p class="text-muted small mb-2">
                                            Last updated {{ $feedbackItem->responded_at->format('Y-m-d H:i') }}
                                            @if ($feedbackItem->responder)
                                                by {{ $feedbackItem->responder->name }}
                                            @endif
                                        </p>
                                    @endif
                                    <button type="submit" class="btn btn-primary btn-block">Save Response</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body text-center text-muted py-4">
                <i class="fas fa-comment-slash fa-2x mb-2 d-block"></i>
                No feedback found for the selected filters.
            </div>
        </div>
    @endforelse

    @if ($feedback->hasPages())
        <div class="mt-3">
            {{ $feedback->links() }}
        </div>
    @endif
@endsection