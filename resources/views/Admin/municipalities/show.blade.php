@extends('layouts.admin')

@section('title', $municipality->name)
@section('page-title', 'Municipality Details')

@section('content')

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    {{-- Top Action Buttons --}}
    <div class="mb-3 d-flex justify-content-between">
        <a href="{{ route('admin.municipalities.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
        <div>
            <a href="{{ route('admin.municipalities.edit', $municipality) }}" class="btn btn-warning btn-sm">
                <i class="fas fa-edit"></i> Edit
            </a>
            <form action="{{ route('admin.municipalities.destroy', $municipality) }}"
                  method="POST"
                  style="display:inline-block;"
                  onsubmit="return confirm('Are you sure you want to delete this municipality?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm ml-1">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </form>
        </div>
    </div>

    <div class="row">

        {{-- Left Column: Main Info --}}
        <div class="col-md-8">

            {{-- Basic Info Card --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        {{ $municipality->name }}
                    </h3>
                    @if($municipality->status === 'active')
                        <span class="badge badge-success">Active</span>
                    @else
                        <span class="badge badge-secondary">Inactive</span>
                    @endif
                </div>
                <div class="card-body">
                    <table class="table table-bordered mb-0">
                        <tr>
                            <th style="width:180px">Phone</th>
                            <td>{{ $municipality->phone ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{{ $municipality->email ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>City</th>
                            <td>{{ $municipality->city ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>Street</th>
                            <td>{{ $municipality->street ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>Building</th>
                            <td>{{ $municipality->building ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>Full Address</th>
                            <td>
                                @php
                                    $parts = array_filter([
                                        $municipality->building,
                                        $municipality->street,
                                        $municipality->city,
                                    ]);
                                @endphp
                                {{ count($parts) ? implode(', ', $parts) : '—' }}
                            </td>
                        </tr>
                        <tr>
                            <th>Google Maps</th>
                            <td>
                                @if($municipality->google_maps_url)
                                    <a href="{{ $municipality->google_maps_url }}"
                                       target="_blank"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-map-marker-alt"></i> Open in Maps
                                    </a>
                                @else
                                    <span class="text-muted">No map location added</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Notes</th>
                            <td>{{ $municipality->notes ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>Created At</th>
                            <td>{{ $municipality->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                        <tr>
                            <th>Updated At</th>
                            <td>{{ $municipality->updated_at->format('d M Y, h:i A') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- Working Hours Card --}}
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-clock"></i> Working Hours
                    </h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Day</th>
                                <th>Status</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($workingHours as $day => $wh)
                                <tr>
                                    <td><strong>{{ $day }}</strong></td>
                                    <td>
                                        @if($wh->is_open)
                                            <span class="badge badge-success">Open</span>
                                        @else
                                            <span class="badge badge-secondary">Closed</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($wh->is_open && $wh->start_time)
                                            {{ \Carbon\Carbon::createFromFormat('H:i:s', $wh->start_time)->format('h:i A') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if($wh->is_open && $wh->end_time)
                                            {{ \Carbon\Carbon::createFromFormat('H:i:s', $wh->end_time)->format('h:i A') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        {{-- Right Column: Stats --}}
        <div class="col-md-4">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Related Information</h3>
                </div>
                <div class="card-body">

                    {{-- Government Offices Count --}}
                    <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded">
                        <div>
                            <div class="text-muted small">Government Offices</div>
                            <div class="h4 mb-0">{{ $municipality->governmentOffices()->count() }}</div>
                        </div>
                        <i class="fas fa-building fa-2x text-muted"></i>
                    </div>

                    {{-- Municipality Users Count --}}
                    @php
                        $usersCount = 0;
                        foreach($municipality->governmentOffices as $office) {
                            $usersCount += $office->users()->count() ?? 0;
                        }
                    @endphp
                    <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                        <div>
                            <div class="text-muted small">Municipality Users</div>
                            <div class="h4 mb-0">{{ $usersCount }}</div>
                        </div>
                        <i class="fas fa-users fa-2x text-muted"></i>
                    </div>

                </div>
            </div>

        </div>
    </div>

@endsection