@extends('layouts.admin')

@section('title', 'Review ID Verification')
@section('page-title', 'Review ID Verification')

@section('content')
    @php
        $rawOcrText = $verification->ocr_raw_text ?? '';
        $validation = $verification->validation_result_json ?? [];
        $warnings = collect();

        foreach ([
            'extracted_first_name' => 'First name was not detected.',
            'extracted_family_name' => 'Family name was not detected.',
            'extracted_father_name' => 'Father name was not detected.',
            'extracted_mother_name' => 'Mother name was not detected.',
            'extracted_mother_family_name' => 'Mother family name was not detected.',
            'extracted_place_of_birth' => 'Place of birth was not detected.',
            'extracted_date_of_birth_text' => 'Date of birth was not detected.',
            'extracted_id_number' => 'National ID number was not detected.',
            'extracted_gender' => 'Gender was not detected on the back side.',
            'extracted_marital_status' => 'Family status was not detected on the back side.',
            'extracted_record_number' => 'Record number was not detected on the back side.',
            'extracted_locality' => 'Locality was not detected on the back side.',
            'extracted_governorate' => 'Governorate was not detected on the back side.',
            'extracted_district' => 'District was not detected on the back side.',
            'extracted_blood_type' => 'Blood type was not detected on the back side.',
            'extracted_issue_date_text' => 'Issue date was not detected on the back side.',
        ] as $field => $message) {
            if (!$verification->{$field}) {
                $warnings->push($message);
            }
        }

        if ($rawOcrText === '') {
            $warnings->push('No OCR text was recorded for this verification.');
        }

        foreach (($validation['warnings'] ?? []) as $warning) {
            $warnings->push((string) $warning);
        }

        foreach (($validation['errors'] ?? []) as $error) {
            $warnings->push((string) $error);
        }

        $warnings = $warnings->unique()->values();
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

    <div data-admin-live-region="identity-verification-review">
        <div class="row">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Submitted ID Images</h3>
                        <div class="card-tools">
                            <span class="text-muted small mr-2">Auto-refreshing every 10 seconds</span>
                            <a href="{{ route('admin.identity-verifications.index') }}" class="btn btn-secondary btn-sm">Back to Queue</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-4 mb-md-0">
                                <h5 class="mb-3">Front Side</h5>
                                @if ($frontImageExists)
                                    <img src="{{ route('admin.identity-verifications.image', $verification) }}" alt="Submitted ID front" class="img-fluid border rounded">
                                    <div class="mt-2">
                                        <a href="{{ route('admin.identity-verifications.image', $verification) }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">
                                            Open Front Image
                                        </a>
                                    </div>
                                @elseif ($verification->id_image_path)
                                    <div class="alert alert-warning mb-0">
                                        The uploaded front image could not be found. Stored path: <code>{{ $verification->id_image_path }}</code>
                                    </div>
                                @else
                                    <p class="text-muted mb-0">No front image uploaded.</p>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <h5 class="mb-3">Back Side</h5>
                                @if ($backImageExists)
                                    <img src="{{ route('admin.identity-verifications.image', [$verification, 'side' => 'back']) }}" alt="Submitted ID back" class="img-fluid border rounded">
                                    <div class="mt-2">
                                        <a href="{{ route('admin.identity-verifications.image', [$verification, 'side' => 'back']) }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">
                                            Open Back Image
                                        </a>
                                    </div>
                                @elseif ($verification->id_image_back_path)
                                    <div class="alert alert-warning mb-0">
                                        The uploaded back image could not be found. Stored path: <code>{{ $verification->id_image_back_path }}</code>
                                    </div>
                                @else
                                    <p class="text-muted mb-0">No back image uploaded.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">OCR and Validation Warnings</h3>
                    </div>
                    <div class="card-body">
                        @forelse ($warnings as $warning)
                            <div class="alert alert-warning mb-2">{{ $warning }}</div>
                        @empty
                            <p class="text-muted mb-0">No warnings recorded.</p>
                        @endforelse
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
                                    <th>Father Name / اسم الأب</th>
                                    <td>{{ $verification->extracted_father_name ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Mother Name / اسم الأم</th>
                                    <td>{{ $verification->extracted_mother_name ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Mother Family Name / شهرة الأم</th>
                                    <td>{{ $verification->extracted_mother_family_name ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Place of Birth / محل الولادة</th>
                                    <td>{{ $verification->extracted_place_of_birth ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Date of Birth / تاريخ الولادة</th>
                                    <td>{{ $verification->extracted_date_of_birth_text ?: (optional($verification->extracted_date_of_birth)->format('Y-m-d') ?: '-') }}</td>
                                </tr>
                                <tr>
                                    <th>National ID Number</th>
                                    <td>{{ $verification->extracted_id_number ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Gender / الجنس</th>
                                    <td>{{ $verification->extracted_gender ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Family Status / الوضع العائلي</th>
                                    <td>{{ $verification->extracted_marital_status ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Record Number / رقم السجل</th>
                                    <td>{{ $verification->extracted_record_number ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Locality / المحلة أو القرية</th>
                                    <td>{{ $verification->extracted_locality ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Governorate / المحافظة</th>
                                    <td>{{ $verification->extracted_governorate ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>District / القضاء</th>
                                    <td>{{ $verification->extracted_district ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Blood Type / فئة الدم</th>
                                    <td>{{ $verification->extracted_blood_type ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Issue Date / تاريخ الإصدار</th>
                                    <td>{{ $verification->extracted_issue_date_text ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>OCR Confidence</th>
                                    <td>{{ $verification->ocr_confidence !== null ? number_format($verification->ocr_confidence * 100, 1) . '%' : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Reviewed By</th>
                                    <td>{{ $verification->reviewer->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Reviewed At</th>
                                    <td>{{ optional($verification->reviewed_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Admin Notes</th>
                                    <td>{{ $verification->admin_notes ?: '-' }}</td>
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
    </div>
@endsection

@push('scripts')
    @if (app()->environment('local'))
        <script>
            console.log('OCR raw text:', @json($rawOcrText));
        </script>
    @endif
@endpush
