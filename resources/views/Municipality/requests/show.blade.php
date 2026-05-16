@extends('layouts.municipality')

@section('title', 'Request Details')
@section('page-title', 'Request Details')

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if ($serviceRequest->needsCitizenDocuments())
        <div class="alert alert-warning">
            <h5 class="mb-2"><i class="fas fa-file-upload mr-1"></i> Waiting For Citizen Documents</h5>
            <p class="mb-0">This request is paused until the citizen uploads the requested files. Your latest note is shown to the citizen as the missing-documents explanation.</p>
        </div>
    @elseif ($serviceRequest->isOverdue())
        <div class="alert alert-danger">
            <h5 class="mb-2"><i class="fas fa-exclamation-circle mr-1"></i> Request Overdue</h5>
            <p class="mb-0">Expected by {{ optional($serviceRequest->dueAt())->format('Y-m-d H:i') ?: '-' }}. This request is overdue by {{ $serviceRequest->overdueDays() }} day{{ $serviceRequest->overdueDays() === 1 ? '' : 's' }}.</p>
        </div>
    @endif

    <div class="row">
        {{-- Main Content --}}
        <div class="col-lg-8">
            {{-- Request Info --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Request #{{ $serviceRequest->id }}</h3>
                    <div>
                        <a href="{{ route('municipality.requests.receipt.download', $serviceRequest) }}" class="btn btn-outline-primary btn-sm mr-2">
                            <i class="fas fa-file-pdf"></i> Receipt PDF
                        </a>
                        <a href="{{ route('municipality.requests.index') }}" class="btn btn-secondary btn-sm">
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
                                        <th style="width: 180px;">Request ID</th>
                                        <td>#{{ $serviceRequest->id }}</td>
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
                                            @if ($serviceRequest->isOverdue())
                                                <span class="badge badge-danger ml-1">Overdue</span>
                                            @endif
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
                                        <td>{{ $serviceRequest->service->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Category</th>
                                        <td>{{ $serviceRequest->service && $serviceRequest->service->serviceCategory ? $serviceRequest->service->serviceCategory->name : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Office</th>
                                        <td>{{ $office->name }}</td>
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
                                        <th>Escalation Reason</th>
                                        <td>{!! nl2br(e($serviceRequest->escalation_reason ?: 'No active escalation.')) !!}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Citizen Documents --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-upload mr-1"></i> Citizen Uploaded Documents</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-bordered table-striped mb-0">
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
                                        <a href="{{ route('municipality.requests.documents.download', [$serviceRequest, $document]) }}" class="btn btn-primary btn-sm">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No submitted documents.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Official Response Document --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-stamp mr-1"></i> Official Response Document</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-bordered table-striped mb-0">
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
                                    <td>{{ $serviceRequest->officialResponseUploader?->name ?? 'Municipality user' }}</td>
                                    <td>
                                        <a href="{{ route('municipality.requests.official-response.download', $serviceRequest) }}" class="btn btn-primary btn-sm">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </td>
                                </tr>
                            @else
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No official response document uploaded yet.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Notes --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sticky-note mr-1"></i> Notes</h3>
                </div>
                <div class="card-body">
                    {!! nl2br(e($serviceRequest->notes ?: 'No notes added yet.')) !!}
                </div>
            </div>

            {{-- Timeline --}}
            @include('shared.request-timeline', [
                'entries' => $serviceRequest->timelineForDisplay(),
                'title' => 'Request History',
            ])
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Update Request --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-edit mr-1"></i> Update Request</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('municipality.requests.update', $serviceRequest) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="custom-select @error('status') is-invalid @enderror">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" {{ old('status', $serviceRequest->status) === $status ? 'selected' : '' }}>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" rows="6" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $serviceRequest->notes) }}</textarea>
                            <small class="form-text text-muted">
                                If you set the status to <strong>Missing Documents</strong>, this note becomes the citizen-facing instruction about what they need to upload.
                            </small>
                            @error('notes')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            @include('shared.document-picker', [
                                'pickerId' => 'missing-documents-picker',
                                'inputName' => 'missing_document_items',
                                'label' => 'Requested Missing Documents',
                                'placeholder' => 'Search and add the missing document',
                                'presetDocuments' => $requiredDocumentChoices,
                                'selectedDocuments' => old('missing_document_items', $serviceRequest->missingDocumentList()),
                                'helpText' => 'Select the exact documents the citizen still needs to upload when the request is marked Missing Documents.',
                            ])
                            @error('missing_document_items')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                            @error('missing_document_items.*')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" name="escalate_to_admin" value="1" class="custom-control-input" id="escalate_to_admin" {{ old('escalate_to_admin') ? 'checked' : '' }}>
                                <label class="custom-control-label" for="escalate_to_admin">Escalate this request to admin review</label>
                            </div>
                            <small class="form-text text-muted">Use this when the municipality needs admin intervention or a higher-level decision.</small>
                        </div>

                        <div class="form-group">
                            <label>Escalation Reason</label>
                            <textarea name="escalation_reason" rows="4" class="form-control @error('escalation_reason') is-invalid @enderror" placeholder="Explain why admin review is needed.">{{ old('escalation_reason', $serviceRequest->isAwaitingAdmin() ? $serviceRequest->escalation_reason : '') }}</textarea>
                            @error('escalation_reason')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Official Response File</label>
                            <input type="file" name="official_response" class="form-control-file @error('official_response') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,application/pdf,image/*">
                            <small class="form-text text-muted">PDF and image files only. Stored in public storage.</small>
                            @error('official_response')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" name="generate_official_response_pdf" value="1" class="custom-control-input" id="generate_official_response_pdf" {{ old('generate_official_response_pdf') ? 'checked' : '' }}>
                                <label class="custom-control-label" for="generate_official_response_pdf">Generate official response PDF</label>
                            </div>
                            <small class="form-text text-muted">Use this when you want the platform to create a PDF instead of uploading one manually.</small>
                        </div>

                        <div class="form-group">
                            <label>Official Response Summary</label>
                            <textarea name="official_response_summary" rows="4" class="form-control @error('official_response_summary') is-invalid @enderror" placeholder="Optional summary that will be placed inside the generated PDF. If left empty, the platform will create a standard confirmation message.">{{ old('official_response_summary') }}</textarea>
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

            {{-- Conversation --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-comments mr-1"></i> Conversation</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">Open the dedicated chat screen to follow the citizen conversation in a cleaner layout.</p>
                    <a href="{{ route('municipality.messages.show', $serviceRequest) }}" class="btn btn-primary btn-block">
                        Open Chat
                    </a>
                </div>
            </div>

            {{-- Appointment --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calendar-alt mr-1"></i> Appointment</h3>
                </div>
                <div class="card-body">
                    @if ($currentAppointment)
                        <div class="mb-3">
                            <strong>Status:</strong>
                            <span class="badge badge-info">{{ $currentAppointment->status }}</span>
                        </div>

                        <div class="mb-3">
                            <strong>Current Slot</strong>
                            <div class="border rounded p-3 mt-2">
                                {{ optional($currentAppointment->timeSlot?->starts_at)->format('Y-m-d H:i') ?: 'TBD' }}
                            </div>
                        </div>

                        @if ($currentAppointment->notes)
                            <div class="mb-3">
                                <strong>Citizen Note</strong>
                                <div class="border rounded p-3 mt-2 bg-light">{!! nl2br(e($currentAppointment->notes)) !!}</div>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('municipality.appointments.update', $currentAppointment) }}">
                            @csrf
                            @method('PATCH')

                            <div class="form-group">
                                <label>Action</label>
                                <select name="action" class="custom-select @error('action') is-invalid @enderror">
                                    <option value="approve">Approve</option>
                                    <option value="reschedule">Reschedule</option>
                                    <option value="cancel">Cancel</option>
                                </select>
                                @error('action')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>Replacement Slot</label>
                                <select name="time_slot_id" class="custom-select @error('time_slot_id') is-invalid @enderror">
                                    <option value="">Keep current slot unless rescheduling</option>
                                    @foreach ($availableSlots as $slot)
                                        <option value="{{ $slot->id }}" {{ (string) old('time_slot_id', $currentAppointment->time_slot_id) === (string) $slot->id ? 'selected' : '' }}>
                                            {{ $slot->starts_at->format('Y-m-d H:i') }} to {{ $slot->ends_at->format('H:i') }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('time_slot_id')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>Municipality Notes</label>
                                <textarea name="municipality_notes" rows="4" class="form-control @error('municipality_notes') is-invalid @enderror">{{ old('municipality_notes', $currentAppointment->municipality_notes) }}</textarea>
                                @error('municipality_notes')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">Save Appointment Update</button>
                        </form>
                    @else
                        <p class="text-muted mb-0">No active appointment has been booked for this request yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection