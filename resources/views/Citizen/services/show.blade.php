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
                                <td>{{ $service->formattedPrice() }}</td>
                            </tr>
                            <tr>
                                <th>Estimated Duration</th>
                                <td>{{ $service->durationLabel() }}</td>
                            </tr>
                            <tr>
                                <th>Office Location</th>
                                <td>{{ $service->governmentOffice?->formatted_address ?: ($service->governmentOffice?->city ?: 'Open the office page to view location details.') }}</td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td>{{ $service->description ?: 'No service description available.' }}</td>
                            </tr>
                            <tr>
                                <th>Required Documents</th>
                                <td>
                                    @php($documents = $service->requiredDocumentList())
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
                    @if ((float) $service->price > 0)
                        <p class="text-muted">This service requires payment. You will be redirected to Stripe to pay securely. Your request will be created automatically once payment is confirmed.</p>
                        <a href="{{ route('citizen.payment.show', $service) }}" class="btn btn-success btn-block">
                            Pay & Submit Request
                        </a>
                    @else
                        <p class="text-muted">Review the required documents and add your notes before submitting this request.</p>
                        <a href="{{ route('citizen.services.request.create', $service) }}" class="btn btn-primary btn-block">
                            Start Request
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
