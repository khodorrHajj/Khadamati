@extends('layouts.admin')

@section('title', 'Government Office Details')
@section('page-title', 'Government Office Details')

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="card-title mb-0">{{ $office->name }}</h3>
            </div>
            <span class="badge {{ $office->status === 'active' ? 'badge-success' : 'badge-secondary' }}">
                {{ ucfirst($office->status) }}
            </span>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th style="width: 180px;">Municipality</th>
                                <td>
                                    @if($office->municipality)
                                        <a href="{{ route('admin.municipalities.show', $office->municipality) }}">
                                            {{ $office->municipality->name }}
                                        </a>
                                    @else
                                        <span class="text-muted">Not assigned</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Service Type</th>
                                <td>{{ $office->service_type ?: 'Not added' }}</td>
                            </tr>
                            <tr>
                                <th>Phone</th>
                                <td>{{ $office->phone ?: 'Not added' }}</td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td>
                                    @if($office->email)
                                        <a href="mailto:{{ $office->email }}">{{ $office->email }}</a>
                                    @else
                                        Not added
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Assigned Users</th>
                                <td>{{ $office->users()->count() }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th style="width: 180px;">City</th>
                                <td>{{ $office->city ?: 'Not added' }}</td>
                            </tr>
                            <tr>
                                <th>Street</th>
                                <td>{{ $office->street ?: 'Not added' }}</td>
                            </tr>
                            <tr>
                                <th>Building</th>
                                <td>{{ $office->building ?: 'Not added' }}</td>
                            </tr>
                            <tr>
                                <th>Full Address</th>
                                <td>
                                    @php
                                        $addressParts = array_filter([$office->building, $office->street, $office->city]);
                                    @endphp
                                    {{ $office->formatted_address ?: (count($addressParts) ? implode(', ', $addressParts) : 'Not added') }}
                                </td>
                            </tr>
                            <tr>
                                <th>Coordinates</th>
                                <td>
                                    @if($office->latitude && $office->longitude)
                                        {{ $office->latitude }}, {{ $office->longitude }}
                                    @else
                                        <span class="text-muted">Not added</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Google Maps</th>
                                <td>
                                    @if($office->google_maps_url)
                                        <a href="{{ $office->google_maps_url }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm">
                                            <i class="fas fa-map-marker-alt"></i> Open Map
                                        </a>
                                    @else
                                        <span class="text-muted">No map location added</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Notes</h3>
                </div>
                <div class="card-body">
                    {{ $office->notes ?: 'No notes added.' }}
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Working Hours</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Status</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($workingHours as $workingHour)
                                <tr>
                                    <td>{{ $workingHour->day_of_week }}</td>
                                    <td>
                                        <span class="badge {{ $workingHour->is_open ? 'badge-success' : 'badge-secondary' }}">
                                            {{ $workingHour->is_open ? 'Open' : 'Closed' }}
                                        </span>
                                    </td>
                                    <td>{{ $workingHour->is_open ? ($workingHour->start_time ?: '-') : '-' }}</td>
                                    <td>{{ $workingHour->is_open ? ($workingHour->end_time ?: '-') : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th style="width: 180px;">Created At</th>
                                <td>{{ $office->created_at ? $office->created_at->format('Y-m-d H:i') : '-' }}</td>
                            </tr>
                            <tr>
                                <th>Updated At</th>
                                <td>{{ $office->updated_at ? $office->updated_at->format('Y-m-d H:i') : '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 d-flex flex-wrap align-items-center">
                <a href="{{ route('admin.offices.index') }}" class="btn btn-primary mr-2 mb-2">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
                <a href="{{ route('admin.offices.edit', $office) }}" class="btn btn-warning mr-2 mb-2">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <form action="{{ route('admin.offices.destroy', $office) }}" method="POST" class="mb-2" onsubmit="return confirm('Are you sure you want to delete this government office?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
