@extends('layouts.admin')

@section('title', 'Request Details')
@section('page-title', 'Request Details')

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- HEADER BAR: Always visible, key info at a glance --}}
    {{-- ============================================================ --}}
    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <div class="d-flex flex-wrap align-items-center mb-2 mb-md-0">
                    <h4 class="mb-0 mr-3">
                        Request #{{ $serviceRequest->id }}
                        <code class="ml-2">{{ $serviceRequest->tracking_code }}</code>
                    </h4>
                    <span class="badge badge-light border mr-2">{{ $serviceRequest->status }}</span>
                    @if ($serviceRequest->isClosed())
                        <span class="badge badge-success mr-2">Closed</span>
                    @elseif ($serviceRequest->isAwaitingAdmin())
                        <span class="badge badge-danger mr-2">Awaiting Admin</span>
                    @else
                        <span class="badge badge-info mr-2">Awaiting Municipality</span>
                    @endif
                    <span class="text-muted small">
                        <i class="fas fa-user mr-1"></i>{{ $serviceRequest->assignedTo?->name ?? 'Unassigned' }}
                    </span>
                </div>
                <div class="d-flex">
                    <a href="{{ route('admin.requests.receipt.download', $serviceRequest) }}" class="btn btn-outline-primary btn-sm mr-2">
                        <i class="fas fa-file-pdf"></i> Receipt PDF
                    </a>
                    <a href="{{ route('admin.requests.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Requests
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- TABBED CONTENT --}}
    {{-- ============================================================ --}}
    <div class="card mt-3">
        <div class="card-header p-0">
            <ul class="nav nav-tabs" id="requestDetailTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="overview-tab" data-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">
                        <i class="fas fa-info-circle mr-1"></i> Overview
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="documents-tab" data-toggle="tab" href="#documents" role="tab" aria-controls="documents" aria-selected="false">
                        <i class="fas fa-paperclip mr-1"></i> Documents
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="timeline-tab" data-toggle="tab" href="#timeline" role="tab" aria-controls="timeline" aria-selected="false">
                        <i class="fas fa-history mr-1"></i> Timeline
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="action-tab" data-toggle="tab" href="#action" role="tab" aria-controls="action" aria-selected="false">
                        <i class="fas fa-edit mr-1"></i> Take Action
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body tab-content" id="requestDetailTabContent">

            {{-- ======================================================== --}}
            {{-- TAB 1: OVERVIEW --}}
            {{-- ======================================================== --}}
            <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                <div class="row">
                    {{-- Left: Detail Tables --}}
                    <div class="col-lg-8">
                        {{-- Request Info --}}
                        <h6 class="text-muted text-uppercase mb-2"><i class="fas fa-file-alt mr-1"></i> Request Information</h6>
                        <table class="table table-bordered table-sm mb-4">
                            <tbody>
                                <tr>
                                    <th style="width: 200px;" class="bg-light">Tracking Code</th>
                                    <td><code>{{ $serviceRequest->tracking_code }}</code></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Status</th>
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
                                    <th class="bg-light">Submitted At</th>
                                    <td>{{ optional($serviceRequest->created_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Last Updated</th>
                                    <td>{{ optional($serviceRequest->updated_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                </tr>
                            </tbody>
                        </table>

                        {{-- Service Info --}}
                        <h6 class="text-muted text-uppercase mb-2"><i class="fas fa-concierge-bell mr-1"></i> Service Information</h6>
                        <table class="table table-bordered table-sm mb-4">
                            <tbody>
                                <tr>
                                    <th style="width: 200px;" class="bg-light">Service</th>
                                    <td>{{ $serviceRequest->service?->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Category</th>
                                    <td>{{ $serviceRequest->service?->serviceCategory?->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Office</th>
                                    <td>{{ $serviceRequest->service?->governmentOffice?->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Municipality</th>
                                    <td>{{ $serviceRequest->service?->governmentOffice?->municipality?->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Price</th>
                                    <td>
                                        @if ($serviceRequest->service)
                                            {{ $serviceRequest->service->formattedPrice() }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Duration</th>
                                    <td>
                                        @if ($serviceRequest->service)
                                            {{ $serviceRequest->service->durationLabel() }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        {{-- Assignment Info --}}
                        <h6 class="text-muted text-uppercase mb-2"><i class="fas fa-user-cog mr-1"></i> Assignment & Escalation</h6>
                        <table class="table table-bordered table-sm mb-0">
                            <tbody>
                                <tr>
                                    <th style="width: 200px;" class="bg-light">Assigned Municipality User</th>
                                    <td>{{ $serviceRequest->assignedTo?->name ?? 'Unassigned' }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Last Assigned By</th>
                                    <td>{{ $serviceRequest->assignedBy?->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Escalation Reason</th>
                                    <td>{!! nl2br(e($serviceRequest->escalation_reason ?: 'No active escalation.')) !!}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Right: Citizen Info --}}
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-user mr-1"></i> Citizen</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mr-3" style="width: 48px; height: 48px; font-size: 1.2rem; font-weight: bold;">
                                        {{ strtoupper(substr($serviceRequest->user?->name ?? '?', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-weight-bold">{{ $serviceRequest->user?->name ?? 'Unknown Citizen' }}</div>
                                        <div class="text-muted small">{{ $serviceRequest->user?->email ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-sticky-note mr-1"></i> Citizen Note</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">{!! nl2br(e($serviceRequest->notes ?: 'No citizen note provided.')) !!}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ======================================================== --}}
            {{-- TAB 2: DOCUMENTS --}}
            {{-- ======================================================== --}}
            <div class="tab-pane fade" id="documents" role="tabpanel" aria-labelledby="documents-tab">
                {{-- Citizen Uploaded Documents --}}
                <h6 class="text-muted text-uppercase mb-2"><i class="fas fa-upload mr-1"></i> Citizen Uploaded Documents</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-hover table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Document</th>
                                <th>Type</th>
                                <th>Uploaded</th>
                                <th style="width: 120px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($serviceRequest->requestDocuments as $document)
                                <tr>
                                    <td>{{ $document->original_name ?: basename($document->document_path) }}</td>
                                    <td>{{ $document->document_type ?: 'Submitted document' }}</td>
                                    <td>{{ optional($document->created_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                    <td>
                                        <a href="{{ route('admin.requests.documents.download', [$serviceRequest, $document]) }}" class="btn btn-primary btn-sm btn-block">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="fas fa-folder-open fa-2x mb-2 d-block"></i>
                                        No submitted documents.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Official Response Document --}}
                <h6 class="text-muted text-uppercase mb-2"><i class="fas fa-stamp mr-1"></i> Official Response Document</h6>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Document</th>
                                <th>Type</th>
                                <th>Uploaded By</th>
                                <th style="width: 120px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($serviceRequest->official_response_path)
                                <tr>
                                    <td>{{ $serviceRequest->official_response_original_name ?: basename($serviceRequest->official_response_path) }}</td>
                                    <td>{{ $serviceRequest->official_response_document_type ?: 'Official Response' }}</td>
                                    <td>{{ $serviceRequest->officialResponseUploader?->name ?? 'System user' }}</td>
                                    <td>
                                        <a href="{{ route('admin.requests.official-response.download', $serviceRequest) }}" class="btn btn-primary btn-sm btn-block">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </td>
                                </tr>
                            @else
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="fas fa-file-alt fa-2x mb-2 d-block"></i>
                                        No official response document uploaded yet.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ======================================================== --}}
            {{-- TAB 3: TIMELINE --}}
            {{-- ======================================================== --}}
            <div class="tab-pane fade" id="timeline" role="tabpanel" aria-labelledby="timeline-tab">
                @include('shared.request-timeline', [
                    'entries' => $serviceRequest->timelineForDisplay(),
                    'title' => 'Request History',
                ])
            </div>

            {{-- ======================================================== --}}
            {{-- TAB 4: TAKE ACTION --}}
            {{-- ======================================================== --}}
            <div class="tab-pane fade" id="action" role="tabpanel" aria-labelledby="action-tab">
                <form method="POST" action="{{ route('admin.requests.update', $serviceRequest) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        {{-- Left Column: Status, Assignment, Response --}}
                        <div class="col-lg-8">
                            {{-- Status & Workflow --}}
                            <h6 class="text-muted text-uppercase mb-3"><i class="fas fa-exchange-alt mr-1"></i> Status & Workflow</h6>
                            <div class="row">
                                <div class="col-md-6">
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
                                </div>
                                <div class="col-md-6">
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
                                        <small class="form-text text-muted">Keep in admin review or return to municipality follow-up.</small>
                                        @error('workflow_state')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Assignment --}}
                            <h6 class="text-muted text-uppercase mb-3 mt-4"><i class="fas fa-user-plus mr-1"></i> Assignment</h6>
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
                                <small class="form-text text-muted">Limited to municipality users from this request's office.</small>
                                @error('assigned_to_user_id')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Official Response --}}
                            <h6 class="text-muted text-uppercase mb-3 mt-4"><i class="fas fa-file-signature mr-1"></i> Official Response</h6>
                            <div class="form-group">
                                <label>Official Response File</label>
                                <div class="custom-file">
                                    <input type="file" name="official_response" class="custom-file-input @error('official_response') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,application/pdf,image/*" id="official_response_file">
                                    <label class="custom-file-label" for="official_response_file" data-browse="Browse">Choose file (PDF or image)</label>
                                </div>
                                <small class="form-text text-muted">Uploading a new file replaces the old one.</small>
                                @error('official_response')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" name="generate_official_response_pdf" value="1" class="custom-control-input" id="admin_generate_official_response_pdf" {{ old('generate_official_response_pdf') ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="admin_generate_official_response_pdf">Generate official response PDF</label>
                                </div>
                                <small class="form-text text-muted">Create a PDF from the summary below instead of uploading manually.</small>
                            </div>

                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label>Official Response Summary</label>
                                        <textarea name="official_response_summary" rows="4" class="form-control @error('official_response_summary') is-invalid @enderror" placeholder="Optional summary for the generated official response PDF.">{{ old('official_response_summary') }}</textarea>
                                        @error('official_response_summary')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Response Type</label>
                                        <input type="text" name="official_response_document_type" value="{{ old('official_response_document_type', $serviceRequest->official_response_document_type ?: 'Official Response') }}" class="form-control @error('official_response_document_type') is-invalid @enderror">
                                        @error('official_response_document_type')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Right Column: Internal Note + Submit --}}
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-lock mr-1"></i> Admin Internal Note</h6>
                                </div>
                                <div class="card-body">
                                    <textarea name="admin_internal_note" rows="8" class="form-control @error('admin_internal_note') is-invalid @enderror">{{ old('admin_internal_note', $serviceRequest->admin_internal_note) }}</textarea>
                                    <small class="form-text text-muted">Visible to admins only.</small>
                                    @error('admin_internal_note')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block mt-3 btn-lg">
                                <i class="fas fa-save"></i> Update Request
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </div>{{-- end tab-content --}}
    </div>{{-- end card --}}
@endsection

@push('scripts')
<script>
    // Auto-switch to Take Action tab if there are validation errors on the form
    @if ($errors->any() && old('_method') === 'PUT')
        $('#requestDetailTabs a[href="#action"]').tab('show');
    @endif

    // Show file name in custom file input
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName || 'Choose file');
    });
</script>
@endpush
