@extends('layouts.citizen')

@section('title', 'Service Details')
@section('page-title', $service->name)

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Service Details</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th style="width: 180px;">Office</th>
                                <td>{{ $service->governmentOffice?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Municipality</th>
                                <td>{{ $service->governmentOffice?->municipality?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Category</th>
                                <td>{{ $service->serviceCategory?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Price</th>
                                <td>${{ number_format((float) $service->price, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Estimated Duration</th>
                                <td>{{ $service->duration_days }} day{{ $service->duration_days === 1 ? '' : 's' }}</td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td>{{ $service->description ?: 'No service description available.' }}</td>
                            </tr>
                            <tr>
                                <th>Required Documents</th>
                                <td>
                                    @php($documents = preg_split('/\r\n|\r|\n/', (string) $service->required_documents, -1, PREG_SPLIT_NO_EMPTY))
                                    @if (count($documents))
                                        @foreach ($documents as $document)
                                            <div>{{ $document }}</div>
                                        @endforeach
                                    @else
                                        <span class="text-muted">No required documents listed.</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Start Request</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">Review the required documents and add your notes before submitting this request.</p>
                    <a href="{{ route('citizen.services.request.create', $service) }}" class="btn btn-primary btn-block">
                        Start Request
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
