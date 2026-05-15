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

    <div class="row">
        <div class="col-md-4">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $averageRating !== null ? number_format($averageRating, 1) : '0.0' }}</h3>
                    <p>Average Rating</p>
                </div>
                <div class="icon">
                    <i class="fas fa-star"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $feedback->total() }}</h3>
                    <p>Total Feedback Items</p>
                </div>
                <div class="icon">
                    <i class="fas fa-comments"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $feedback->whereNull('responded_at')->count() }}</h3>
                    <p>Still Waiting For Response</p>
                </div>
                <div class="icon">
                    <i class="fas fa-reply"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filters for {{ $office->name }}</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('municipality.feedback.index') }}">
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Service</label>
                            <select name="service" class="custom-select">
                                <option value="">All Services</option>
                                @foreach ($services as $service)
                                    <option value="{{ $service->id }}" {{ (string) $filters['service'] === (string) $service->id ? 'selected' : '' }}>
                                        {{ $service->name }}
                                    </option>
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
                                    <option value="{{ $rating }}" {{ (string) $filters['rating'] === (string) $rating ? 'selected' : '' }}>
                                        {{ $rating }} Star{{ $rating === 1 ? '' : 's' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="btn-group mb-3 w-100">
                            <button type="submit" class="btn btn-primary">Apply</button>
                            @if ($filters['service'] || $filters['rating'])
                                <a href="{{ route('municipality.feedback.index') }}" class="btn btn-secondary">Clear</a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Feedback Queue</h3>
        </div>
        <div class="card-body">
            @forelse ($feedback as $feedbackItem)
                <div class="border rounded p-3 mb-4">
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h4 class="mb-1">{{ $feedbackItem->serviceRequest->service->name ?? '-' }}</h4>
                                    <div class="text-muted">
                                        {{ $feedbackItem->serviceRequest->service?->serviceCategory?->name ?? '-' }}
                                    </div>
                                </div>
                                <span class="badge badge-warning">{{ $feedbackItem->rating }}/5</span>
                            </div>

                            <p class="mb-2">
                                <strong>Citizen:</strong>
                                {{ $feedbackItem->user->name ?? 'Unknown Citizen' }}
                                <span class="text-muted">({{ $feedbackItem->user->email ?? '-' }})</span>
                            </p>
                            <p class="mb-2"><strong>Request:</strong> #{{ $feedbackItem->service_request_id }}</p>
                            <p class="mb-3">
                                <strong>Submitted:</strong> {{ optional($feedbackItem->created_at)->format('Y-m-d H:i') ?: '-' }}
                                @if ($feedbackItem->responded_at)
                                    <span class="badge badge-success ml-2">Answered</span>
                                @else
                                    <span class="badge badge-warning ml-2">Waiting</span>
                                @endif
                            </p>

                            <div class="mb-3">
                                <strong>Citizen Comment</strong>
                                <div class="border rounded p-3 mt-2 bg-light">{!! nl2br(e($feedbackItem->comment)) !!}</div>
                            </div>

                            <div class="mb-3">
                                <strong>Current Reply Visible To Citizen</strong>
                                <div class="border rounded p-3 mt-2">
                                    {!! nl2br(e($feedbackItem->public_response ?: 'No public response yet.')) !!}
                                </div>
                            </div>

                            <div>
                                <strong>Internal Office Note</strong>
                                <div class="border rounded p-3 mt-2">
                                    {!! nl2br(e($feedbackItem->private_response ?: 'No internal note yet.')) !!}
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5 mt-4 mt-lg-0">
                            <div class="card card-outline card-primary h-100 mb-0">
                                <div class="card-header">
                                    <h3 class="card-title">Reply</h3>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="{{ route('municipality.feedback.update', $feedbackItem) }}">
                                        @csrf
                                        @method('PATCH')

                                        <div class="form-group">
                                            <label>Reply Visible To Citizen</label>
                                            <textarea name="public_response" rows="4" class="form-control">{{ $feedbackItem->public_response }}</textarea>
                                            <small class="form-text text-muted">Use this if you want the citizen to see your answer on their request page.</small>
                                        </div>

                                        <div class="form-group">
                                            <label>Internal Office Note</label>
                                            <textarea name="private_response" rows="4" class="form-control">{{ $feedbackItem->private_response }}</textarea>
                                            <small class="form-text text-muted">Optional internal note for municipality follow-up.</small>
                                        </div>

                                        @if ($feedbackItem->responded_at)
                                            <p class="text-muted small">
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
            @empty
                <p class="text-muted mb-0">No feedback found for the selected filters.</p>
            @endforelse
        </div>

        @if ($feedback->hasPages())
            <div class="card-footer">
                {{ $feedback->links() }}
            </div>
        @endif
    </div>
@endsection
