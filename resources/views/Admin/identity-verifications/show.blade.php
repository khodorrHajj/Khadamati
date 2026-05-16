@extends('layouts.admin')

@section('title', 'Review ID Verification')
@section('page-title', 'Review ID Verification')

@section('content')
    @php
        $rawOcrText = $verification->ocr_raw_text
            ?? data_get($verification->ocr_raw_json, 'text')
            ?? data_get($verification->ocr_raw_json, 'response.responses.0.fullTextAnnotation.text')
            ?? '';
        $ocrDiagnostics = data_get($verification->ocr_raw_json, 'diagnostics', []);
        $ocrContext = data_get($verification->ocr_raw_json, 'context', []);
        $fileDiagnostics = data_get($ocrContext, 'file', []);
    @endphp

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
                    @if ($imageExists)
                        <img src="{{ route('admin.identity-verifications.image', $verification) }}" alt="Submitted ID" class="img-fluid border rounded">
                        <div class="mt-2">
                            <a href="{{ route('admin.identity-verifications.image', $verification) }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">
                                Open Image
                            </a>
                        </div>
                    @elseif ($verification->id_image_path)
                        <div class="alert alert-warning mb-0">
                            The uploaded ID image could not be found. Stored path: <code>{{ $verification->id_image_path }}</code>
                        </div>
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

                    @if ($rawOcrText === '')
                        <div class="alert alert-warning mb-2">No text was detected. Try a clearer image or approve manually.</div>
                    @endif

                    @if (!array_merge($qualityWarnings, $exifWarnings, $validationWarnings, $validationErrors))
                        <p class="text-muted mb-0">No warnings recorded.</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Raw OCR Text</h3>
                </div>
                <div class="card-body">
                    <details>
                        <summary>Show raw OCR text</summary>
                        <pre class="mt-3 p-3 bg-light border rounded" style="white-space: pre-wrap;">{{ $rawOcrText ?: 'No raw OCR text recorded.' }}</pre>
                    </details>
                </div>
            </div>

            @if (app()->environment('local'))
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">OCR Diagnostics</h3>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <tbody>
                                <tr>
                                    <th>Stored Path</th>
                                    <td><code>{{ $fileDiagnostics['stored_path'] ?? $verification->id_image_path ?? '-' }}</code></td>
                                </tr>
                                <tr>
                                    <th>Physical Path</th>
                                    <td><code>{{ $fileDiagnostics['absolute_path'] ?? '-' }}</code></td>
                                </tr>
                                <tr>
                                    <th>File Exists / Readable</th>
                                    <td>{{ ($fileDiagnostics['exists'] ?? false) ? 'Yes' : 'No' }} / {{ ($fileDiagnostics['readable'] ?? false) ? 'Yes' : 'No' }}</td>
                                </tr>
                                <tr>
                                    <th>File Size / MIME</th>
                                    <td>{{ $fileDiagnostics['size'] ?? '-' }} bytes / {{ $fileDiagnostics['mime_type'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Raw Text Length</th>
                                    <td>{{ strlen($rawOcrText) }}</td>
                                </tr>
                                <tr>
                                    <th>Annotations Count</th>
                                    <td>{{ $ocrDiagnostics['text_annotations_count'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>OCR Attempt</th>
                                    <td>{{ $ocrDiagnostics['attempt'] ?? '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
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
                                <th>First Name / الاسم</th>
                                <td>{{ $verification->extracted_first_name ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th>Family Name / الشهرة</th>
                                <td>{{ $verification->extracted_family_name ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th>Matched Name</th>
                                <td>{{ $verification->extracted_full_name ?: '-' }}</td>
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

@push('scripts')
    @if (app()->environment('local'))
        <script>
            console.log('OCR raw text:', @json($rawOcrText));
        </script>
    @endif
@endpush
