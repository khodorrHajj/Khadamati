@extends('layouts.citizen')

@section('title', 'Browse Offices')
@section('page-title', 'Browse Government Offices')

@section('content')
    <div class="row">
        @forelse ($offices as $office)
            <div class="col-md-6 col-lg-4">
                <div class="card card-outline card-primary h-100">
                    <div class="card-body d-flex flex-column">
                        <h3 class="h5">{{ $office->name }}</h3>
                        <p class="text-muted mb-2">{{ $office->municipality?->name ?? 'Municipality not assigned' }}</p>
                        <p class="mb-3">{{ $office->service_type ?: 'Public services available through this office.' }}</p>
                        <div class="mt-auto">
                            <a href="{{ route('citizen.offices.show', $office) }}" class="btn btn-primary btn-sm">View Categories</a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info mb-0">No active offices with services are available right now.</div>
            </div>
        @endforelse
    </div>

    @if ($offices->hasPages())
        <div class="mt-3">
            {{ $offices->links() }}
        </div>
    @endif
@endsection
