@extends('layouts.admin')

@section('title', 'Review ID Verification')
@section('page-title', 'Review ID Verification')

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
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Submitted ID Image</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.identity-verifications.index') }}" class="btn btn-secondary btn-sm">Back to Queue</a>
                    </div>
                </div>
                <div class="card-body">
                    @if ($verification->id_image_url)
                        <img src="{{ $verification->id_image_url }}" alt="Submitted ID" class="img-fluid border rounded">
                    @else
                        <p class="text-muted mb-0">No ID image uploaded.</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">OCR and Validation Warnings</h3>
                </div>
                <div class="card-body">
                    @php($qualityWarnings = $verification->quality_result_json['warnings'] ?? [])
                    @php($exifWarnings = $verification->exif_result_json['warnings'] ?? [])
                    @php($validationWarnings = $verification->validation_result_json['warnings'] ?? [])
                    @php($validationErrors = $verification->validation_result_json['errors'] ?? [])

                    @foreach (array_merge($qualityWarnings, $exifWarnings, $validationWarnings, $validationErrors) as $warning)
                        <div class="alert alert-warning mb-2">{{ $warning }}</div>
                    @endforeach

                    @if (!array_merge($qualityWarnings, $exifWarnings, $validationWarnings, $validationErrors))
                        <p class="text-muted mb-0">No warnings recorded.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Citizen and Extracted Fields</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th>Citizen</th>
                                <td>{{ $verification->user->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td>{{ $verification->user->email ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>{{ ucwords(str_replace('_', ' ', $verification->status)) }}</td>
                            </tr>
                            <tr>
                                <th>Extracted Name</th>
                                <td>{{ $verification->extracted_full_name ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th>ID Number</th>
                                <td>{{ $verification->extracted_id_number ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th>Date of Birth</th>
                                <td>{{ optional($verification->extracted_date_of_birth)->format('Y-m-d') ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th>OCR Confidence</th>
                                <td>{{ $verification->ocr_confidence !== null ? number_format($verification->ocr_confidence * 100, 1) . '%' : '-' }}</td>
                            </tr>
                            <tr>
                                <th>Reviewed By</th>
                                <td>{{ $verification->reviewer->name ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Review Decision</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.identity-verifications.approve', $verification) }}" class="mb-3">
                        @csrf
                        @method('PATCH')
                        <div class="form-group">
                            <label>Admin Notes</label>
                            <textarea name="admin_notes" rows="3" class="form-control">{{ old('admin_notes', $verification->admin_notes) }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-success btn-block">Approve and Activate Citizen</button>
                    </form>

                    <form method="POST" action="{{ route('admin.identity-verifications.reject', $verification) }}">
                        @csrf
                        @method('PATCH')
                        <div class="form-group">
                            <label>Rejection Reason</label>
                            <textarea name="admin_notes" rows="3" class="form-control @error('admin_notes') is-invalid @enderror">{{ old('admin_notes') }}</textarea>
                            @error('admin_notes')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-danger btn-block">Reject Verification</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
