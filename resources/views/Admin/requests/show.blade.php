@extends('layouts.admin')

@section('title', 'Request Details')
@section('page-title', 'Request Details')

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
        <div class="col-12">
            <div class="alert alert-light border">
                <div class="d-flex flex-wrap justify-content-between align-items-start">
                    <div class="mb-2">
                        <strong>Quick Summary:</strong>
                        {{ $serviceRequest->tracking_code }} is currently <strong>{{ $serviceRequest->status }}</strong>.
                        @if ($serviceRequest->isClosed())
                            This request is closed.
                        @elseif ($serviceRequest->isAwaitingAdmin())
                            Admin action is currently required.
                        @else
                            The municipality is currently expected to continue this request.
                        @endif
                    </div>
                    <div class="small text-muted">
                        Assigned to: {{ $serviceRequest->assignedTo?->name ?? 'Unassigned' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="row">
                <div class="col-md-4">
                    <div class="small-box bg-light border">
                        <div class="inner">
                            <h3 class="h5 mb-1">{{ $serviceRequest->tracking_code }}</h3>
                            <p class="mb-0">Tracking Code</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="small-box bg-light border">
                        <div class="inner">
                            <h3 class="h5 mb-1">{{ $serviceRequest->status }}</h3>
                            <p class="mb-0">Current Status</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="small-box bg-light border">
                        <div class="inner">
                            <h3 class="h5 mb-1">{{ $serviceRequest->assignedTo?->name ?? 'Unassigned' }}</h3>
                            <p class="mb-0">Current Owner</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Request #{{ $serviceRequest->id }}</h3>
                    <div class="d-flex align-items-center">
                        <a href="{{ route('admin.requests.receipt.download', $serviceRequest) }}" class="btn btn-outline-primary btn-sm mr-2">
                            <i class="fas fa-file-pdf"></i> Receipt PDF
                        </a>
                        <a href="{{ route('admin.requests.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Requests
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th style="width: 180px;">Tracking Code</th>
                                        <td>{{ $serviceRequest->tracking_code }}</td>
                                    </tr>
                                    <tr>
                                        <th>Citizen</th>
                                        <td>{{ $serviceRequest->user->name ?? 'Unknown Citizen' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Citizen Email</th>
                                        <td>{{ $serviceRequest->user->email ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <span class="badge badge-light border">{{ $serviceRequest->status }}</span>
                                            @if ($serviceRequest->isClosed())
                                                <span class="badge badge-success ml-1">Closed</span>
                                            @elseif ($serviceRequest->isAwaitingAdmin())
                                                <span class="badge badge-danger ml-1">Awaiting Admin</span>
                                            @else
                                                <span class="badge badge-info ml-1">Awaiting Municipality</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Submitted At</th>
                                        <td>{{ optional($serviceRequest->created_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Last Updated</th>
                                        <td>{{ optional($serviceRequest->updated_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th style="width: 180px;">Service</th>
                                        <td>{{ $serviceRequest->service?->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Category</th>
                                        <td>{{ $serviceRequest->service?->serviceCategory?->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Office</th>
                                        <td>{{ $serviceRequest->service?->governmentOffice?->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Municipality</th>
                                        <td>{{ $serviceRequest->service?->governmentOffice?->municipality?->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Price</th>
                                        <td>
                                            @if ($serviceRequest->service)
                                                {{ $serviceRequest->service->formattedPrice() }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Duration</th>
                                        <td>
                                            @if ($serviceRequest->service)
                                                {{ $serviceRequest->service->durationLabel() }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Assigned Municipality User</th>
                                        <td>{{ $serviceRequest->assignedTo?->name ?? 'Unassigned' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Last Assigned By</th>
                                        <td>{{ $serviceRequest->assignedBy?->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Escalation Reason</th>
                                        <td>{!! nl2br(e($serviceRequest->escalation_reason ?: 'No active escalation.')) !!}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title">Citizen Submitted Note</h3>
                        </div>
                        <div class="card-body">
                            {!! nl2br(e($serviceRequest->notes ?: 'No citizen note provided.')) !!}
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title">Citizen Uploaded Documents</h3>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover text-nowrap mb-0">
                                <thead>
                                    <tr>
                                        <th>Document</th>
                                        <th>Type</th>
                                        <th>Uploaded</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($serviceRequest->requestDocuments as $document)
                                        <tr>
                                            <td>{{ $document->original_name ?: basename($document->document_path) }}</td>
                                            <td>{{ $document->document_type ?: 'Submitted document' }}</td>
                                            <td>{{ optional($document->created_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                            <td>
                                                <a href="{{ route('admin.requests.documents.download', [$serviceRequest, $document]) }}" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">No submitted documents.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title">Official Response Document</h3>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover text-nowrap mb-0">
                                <thead>
                                    <tr>
                                        <th>Document</th>
                                        <th>Type</th>
                                        <th>Uploaded By</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($serviceRequest->official_response_path)
                                        <tr>
                                            <td>{{ $serviceRequest->official_response_original_name ?: basename($serviceRequest->official_response_path) }}</td>
                                            <td>{{ $serviceRequest->official_response_document_type ?: 'Official Response' }}</td>
                                            <td>{{ $serviceRequest->officialResponseUploader?->name ?? 'System user' }}</td>
                                            <td>
                                                <a href="{{ route('admin.requests.official-response.download', $serviceRequest) }}" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                            </td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">No official response document uploaded yet.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @include('shared.request-timeline', [
                        'entries' => $serviceRequest->timelineForDisplay(),
                        'title' => 'Request History',
                    ])
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Take Action</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        Update the status, assign the request if needed, and upload or generate the final response from this panel.
                    </div>
                    <form method="POST" action="{{ route('admin.requests.update', $serviceRequest) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="custom-select @error('status') is-invalid @enderror">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected(old('status', $serviceRequest->status) === $status)>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Admin Internal Note</label>
                            <textarea name="admin_internal_note" rows="6" class="form-control @error('admin_internal_note') is-invalid @enderror">{{ old('admin_internal_note', $serviceRequest->admin_internal_note) }}</textarea>
                            <small class="form-text text-muted">Visible to admins only.</small>
                            @error('admin_internal_note')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Workflow State</label>
                            <select name="workflow_state" class="custom-select @error('workflow_state') is-invalid @enderror">
                                <option value="{{ \App\Models\ServiceRequest::WORKFLOW_AWAITING_ADMIN }}" @selected(old('workflow_state', $serviceRequest->workflow_state) === \App\Models\ServiceRequest::WORKFLOW_AWAITING_ADMIN)>
                                    Awaiting Admin
                                </option>
                                <option value="{{ \App\Models\ServiceRequest::WORKFLOW_AWAITING_MUNICIPALITY }}" @selected(old('workflow_state', $serviceRequest->workflow_state) === \App\Models\ServiceRequest::WORKFLOW_AWAITING_MUNICIPALITY)>
                                    Awaiting Municipality
                                </option>
                            </select>
                            <small class="form-text text-muted">Use this to keep the request in admin review or return it to municipality follow-up.</small>
                            @error('workflow_state')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Assign Municipality User</label>
                            <select name="assigned_to_user_id" class="custom-select @error('assigned_to_user_id') is-invalid @enderror">
                                <option value="">Leave unassigned</option>
                                @foreach ($municipalityUsers as $municipalityUser)
                                    <option value="{{ $municipalityUser->id }}" @selected((string) old('assigned_to_user_id', $serviceRequest->assigned_to_user_id) === (string) $municipalityUser->id)>
                                        {{ $municipalityUser->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Assignment is limited to municipality users from this request's office.</small>
                            @error('assigned_to_user_id')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Official Response File</label>
                            <input type="file" name="official_response" class="form-control-file @error('official_response') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,application/pdf,image/*">
                            <small class="form-text text-muted">PDF and image files only. Uploading a new file replaces the old one.</small>
                            @error('official_response')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" name="generate_official_response_pdf" value="1" class="custom-control-input" id="admin_generate_official_response_pdf" {{ old('generate_official_response_pdf') ? 'checked' : '' }}>
                                <label class="custom-control-label" for="admin_generate_official_response_pdf">Generate official response PDF</label>
                            </div>
                            <small class="form-text text-muted">Create a PDF directly from the summary below instead of uploading a file manually.</small>
                        </div>

                        <div class="form-group">
                            <label>Official Response Summary</label>
                            <textarea name="official_response_summary" rows="4" class="form-control @error('official_response_summary') is-invalid @enderror" placeholder="Optional summary for the generated official response PDF.">{{ old('official_response_summary') }}</textarea>
                            @error('official_response_summary')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Official Response Type</label>
                            <input type="text" name="official_response_document_type" value="{{ old('official_response_document_type', $serviceRequest->official_response_document_type ?: 'Official Response') }}" class="form-control @error('official_response_document_type') is-invalid @enderror">
                            @error('official_response_document_type')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-save"></i> Update Request
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
