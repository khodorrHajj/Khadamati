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
                    <h3 class="card-title">Submit Request</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('citizen.requests.store', $service) }}" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" rows="5" class="form-control @error('notes') is-invalid @enderror" placeholder="Optional notes or request details">{{ old('notes') }}</textarea>
                            @error('notes')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Supporting Documents</label>
                            <input type="file" name="documents[]" multiple class="form-control-file @error('documents.*') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,application/pdf,image/*">
                            <small class="form-text text-muted">PDF and image files only. Upload the documents listed by the office when available.</small>
                            @error('documents.*')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Submit Request</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
