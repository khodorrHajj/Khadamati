@extends('layouts.citizen')

@section('title', 'Start Request')
@section('page-title', 'Start Request')

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
                    <h3 class="card-title">Service Summary</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th style="width: 200px;">Service</th>
                                <td>{{ $service->name }}</td>
                            </tr>
                            <tr>
                                <th>Office</th>
                                <td>{{ $service->governmentOffice?->name ?? '-' }}</td>
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
                                <th>Duration</th>
                                <td>{{ $service->durationLabel() }}</td>
                            </tr>
                            <tr>
                                <th>Required Documents</th>
                                <td>
                                    @php($documents = $service->requiredDocumentList())
                                    @if (count($documents))
                                        <ul class="pl-3 mb-0">
                                            @foreach ($documents as $document)
                                                <li>{{ $document }}</li>
                                            @endforeach
                                        </ul>
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
                    <h3 class="card-title">Request Information</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('citizen.services.request.store', $service) }}" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group">
                            <label for="notes">Notes / Message</label>
                            <textarea name="notes" id="notes" rows="5" class="form-control @error('notes') is-invalid @enderror" placeholder="Add any details the office should know.">{{ old('notes') }}</textarea>
                            @error('notes')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="documents">Upload Documents</label>
                            <input type="file" name="documents[]" id="documents" multiple class="form-control-file @error('documents.*') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,application/pdf,image/*">
                            <small class="form-text text-muted">Upload one or more PDF or image files. Maximum size is 5 MB per file.</small>
                            @error('documents.*')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            Submit Request
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
