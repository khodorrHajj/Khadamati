@extends('layouts.citizen')

@section('title', 'Browse Services')
@section('page-title', 'Browse Services')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Find a Service</h3>
        </div>
        <form method="GET" action="{{ route('citizen.services.index') }}">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search">Search</label>
                            <input type="text" name="search" id="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Service name">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="municipality">Municipality</label>
                            <select name="municipality" id="municipality" class="form-control">
                                <option value="">All municipalities</option>
                                @foreach ($municipalities as $municipality)
                                    <option value="{{ $municipality->id }}" @selected((string) ($filters['municipality'] ?? '') === (string) $municipality->id)>
                                        {{ $municipality->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="office">Office</label>
                            <select name="office" id="office" class="form-control">
                                <option value="">All offices</option>
                                @foreach ($offices as $office)
                                    <option value="{{ $office->id }}" @selected((string) ($filters['office'] ?? '') === (string) $office->id)>
                                        {{ $office->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select name="category" id="category" class="form-control">
                                <option value="">All categories</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) ($filters['category'] ?? '') === (string) $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('citizen.services.index') }}" class="btn btn-secondary">Reset</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search mr-1"></i> Apply Filters
                </button>
            </div>
        </form>
    </div>

    <div class="row">
        @forelse ($services as $service)
            <div class="col-xl-4 col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title">{{ $service->name }}</h3>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <dl class="row mb-3">
                            <dt class="col-sm-5">Category</dt>
                            <dd class="col-sm-7">{{ $service->serviceCategory?->name ?? '-' }}</dd>

                            <dt class="col-sm-5">Office</dt>
                            <dd class="col-sm-7">{{ $service->governmentOffice?->name ?? '-' }}</dd>

                            <dt class="col-sm-5">Municipality</dt>
                            <dd class="col-sm-7">{{ $service->governmentOffice?->municipality?->name ?? '-' }}</dd>

                            <dt class="col-sm-5">Price</dt>
                            <dd class="col-sm-7">${{ number_format((float) $service->price, 2) }}</dd>

                            <dt class="col-sm-5">Duration</dt>
                            <dd class="col-sm-7">{{ $service->duration_days }} day{{ (int) $service->duration_days === 1 ? '' : 's' }}</dd>
                        </dl>

                        <div class="mb-3">
                            <strong>Required Documents</strong>
                            @php($documents = preg_split('/\r\n|\r|\n/', (string) $service->required_documents, -1, PREG_SPLIT_NO_EMPTY))
                            @if (count($documents))
                                <ul class="pl-3 mb-0">
                                    @foreach ($documents as $document)
                                        <li>{{ $document }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-muted mb-0">No required documents listed.</p>
                            @endif
                        </div>

                        <div class="mt-auto">
                            <a href="{{ route('citizen.services.request.create', $service) }}" class="btn btn-primary btn-block">
                                Start Request
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info mb-0">No active services match your filters.</div>
            </div>
        @endforelse
    </div>

    @if ($services->hasPages())
        <div class="mt-3">
            {{ $services->links() }}
        </div>
    @endif
@endsection
