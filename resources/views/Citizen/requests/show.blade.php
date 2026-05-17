@extends('layouts.citizen')

@section('title', 'My Request')
@section('page-title', 'My Request')

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

    @if ($serviceRequest->needsCitizenDocuments())
        <div class="alert alert-warning">
            <h5 class="mb-2"><i class="fas fa-file-upload mr-1"></i> Missing Documents</h5>
            <p class="mb-2">The municipality needs more documents before this request can continue.</p>
            <p class="mb-3">{!! nl2br(e($serviceRequest->notes ?: 'Please upload the requested files and message the municipality if you need clarification.')) !!}</p>
            @if (count($serviceRequest->missingDocumentList()))
                <div class="mb-3">
                    <strong>Requested Documents</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($serviceRequest->missingDocumentList() as $documentItem)
                            <li>{{ $documentItem }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <a href="#documents-upload" class="btn btn-warning btn-sm mr-2">Upload Requested Documents</a>
            <a href="{{ route('citizen.messages.show', $serviceRequest) }}" class="btn btn-outline-dark btn-sm">Ask The Municipality</a>
        </div>
    @elseif ($serviceRequest->isOverdue())
        <div class="alert alert-danger">
            <h5 class="mb-2"><i class="fas fa-exclamation-circle mr-1"></i> Request Overdue</h5>
            <p class="mb-2">
                This request has passed the expected service duration of {{ $serviceRequest->service?->durationLabel() ?? 'the expected timeframe' }}.
            </p>
            <p class="mb-3">
                Expected by {{ optional($serviceRequest->dueAt())->format('Y-m-d H:i') ?: '-' }}.
                It is currently overdue by {{ $serviceRequest->overdueDays() }} day{{ $serviceRequest->overdueDays() === 1 ? '' : 's' }}.
            </p>
            <a href="{{ route('citizen.messages.show', $serviceRequest) }}" class="btn btn-danger btn-sm">Message Municipality</a>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Request #{{ $serviceRequest->id }}</h3>
                    <div class="d-flex align-items-center">
                        <a href="{{ route('citizen.requests.receipt.download', $serviceRequest) }}" class="btn btn-outline-primary btn-sm mr-2">
                            <i class="fas fa-file-pdf"></i> Receipt PDF
                        </a>
                        <a href="{{ route('citizen.requests.index') }}" class="btn btn-secondary btn-sm">Back to My Requests</a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th style="width: 200px;">Tracking Code</th>
                                <td>{{ $serviceRequest->tracking_code }}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <span class="badge badge-light border">{{ $serviceRequest->status }}</span>
                                    @if ($serviceRequest->isOverdue())
                                        <span class="badge badge-danger ml-1">Overdue</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Public Tracking URL</th>
                                <td>
                                    <a href="{{ route('tracking.show', $serviceRequest->tracking_code) }}" target="_blank" rel="noopener">
                                        {{ route('tracking.show', $serviceRequest->tracking_code) }}
                                    </a>
                                </td>
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
                                <th>Category</th>
                                <td>{{ $serviceRequest->service?->serviceCategory?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Service</th>
                                <td>{{ $serviceRequest->service?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Submitted Notes</th>
                                <td>{!! nl2br(e($serviceRequest->notes ?: 'No notes provided.')) !!}</td>
                            </tr>
                            <tr>
                                <th>Last Updated</th>
                                <td>{{ optional($serviceRequest->updated_at)->format('Y-m-d H:i') ?: '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
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
                                        <a href="{{ route('citizen.requests.documents.download', [$serviceRequest, $document]) }}" class="btn btn-primary btn-sm">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No documents uploaded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Official Response Documents</h3>
                </div>
                <div class="card-body">
                    @if ($serviceRequest->official_response_path)
                        <a href="{{ route('citizen.requests.official-response.download', $serviceRequest) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-download"></i>
                            {{ $serviceRequest->official_response_original_name ?: 'Download official response' }}
                        </a>
                        <div class="text-muted small mt-2">
                            {{ $serviceRequest->official_response_document_type ?: 'Official Response' }}
                        </div>
                    @else
                        <p class="text-muted mb-0">No official response document has been uploaded yet.</p>
                    @endif
                </div>
            </div>

            @include('shared.request-timeline', [
                'entries' => $serviceRequest->timelineForDisplay(),
                'title' => 'Request History',
            ])

            @include('shared.request-tracking-qr', [
                'serviceRequest' => $serviceRequest,
                'title' => 'Track With QR Code',
            ])
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Upload Additional Document</h3>
                </div>
                <div class="card-body" id="documents-upload">
                    @if ($serviceRequest->needsCitizenDocuments())
                        <div class="alert alert-warning">
                            Upload the files requested by the municipality here. They will be attached directly to this request.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('citizen.requests.documents.store', $serviceRequest) }}" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group">
                            <label>Document Type</label>
                            @if ($serviceRequest->needsCitizenDocuments() && count($serviceRequest->missingDocumentList()))
                                <select name="document_type" class="custom-select @error('document_type') is-invalid @enderror">
                                    <option value="">Select the requested document</option>
                                    @foreach ($serviceRequest->missingDocumentList() as $documentItem)
                                        <option value="{{ $documentItem }}" @selected(old('document_type') === $documentItem)>{{ $documentItem }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" name="document_type" value="{{ old('document_type') }}" class="form-control @error('document_type') is-invalid @enderror" placeholder="Optional label, e.g. ID Copy">
                            @endif
                            @error('document_type')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Document File</label>
                            <input type="file" name="document" class="form-control-file @error('document') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,application/pdf,image/*">
                            @error('document')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Upload Document</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Appointment</h3>
                </div>
                <div class="card-body">
                    @error('appointment')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror

                    @if ($currentAppointment)
                        <div class="mb-3">
                            <strong>Current Appointment Status:</strong>
                            <span class="badge badge-info">{{ $currentAppointment->status }}</span>
                        </div>

                        <div class="mb-3">
                            <strong>Scheduled Time</strong>
                            <div class="border rounded p-3 mt-2">
                                {{ optional($currentAppointment->timeSlot?->starts_at)->format('Y-m-d H:i') ?: 'To be confirmed' }}
                            </div>
                        </div>

                        @if ($currentAppointment->notes)
                            <div class="mb-3">
                                <strong>Your Appointment Note</strong>
                                <div class="border rounded p-3 mt-2 bg-light">{!! nl2br(e($currentAppointment->notes)) !!}</div>
                            </div>
                        @endif

                        @if ($currentAppointment->municipality_notes)
                            <div class="mb-3">
                                <strong>Municipality Note</strong>
                                <div class="border rounded p-3 mt-2">{!! nl2br(e($currentAppointment->municipality_notes)) !!}</div>
                            </div>
                        @endif
                    @elseif ($availableSlots->isNotEmpty())
                        <form method="POST" action="{{ route('citizen.requests.appointments.store', $serviceRequest) }}">
                            @csrf

                            <div class="form-group">
                                <label>Book Appointment</label>
                                <select name="time_slot_id" class="custom-select @error('time_slot_id') is-invalid @enderror">
                                    <option value="">Select a time slot</option>
                                    @foreach ($availableSlots as $slot)
                                        <option value="{{ $slot->id }}" {{ (string) old('time_slot_id') === (string) $slot->id ? 'selected' : '' }}>
                                            {{ $slot->starts_at->format('Y-m-d H:i') }} to {{ $slot->ends_at->format('H:i') }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('time_slot_id')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>Appointment Note</label>
                                <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror" placeholder="Optional note for the appointment.">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">Book Appointment</button>
                        </form>
                    @else
                        <p class="text-muted mb-0">No appointment slots are available yet for this office.</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Conversation</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">Use the dedicated chat screen to follow the full conversation with the municipality.</p>
                    <a href="{{ route('citizen.messages.show', $serviceRequest) }}" class="btn btn-primary btn-block">
                        Open Chat
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Service Feedback</h3>
                </div>
                <div class="card-body">
                    @error('feedback')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror

                    @if ($serviceRequest->feedback)
                        <div class="mb-3">
                            <strong>Your Rating:</strong>
                            <span class="badge badge-warning">{{ $serviceRequest->feedback->rating }}/5</span>
                        </div>

                        <div class="mb-3">
                            <strong>Your Comment</strong>
                            <div class="border rounded p-3 mt-2 bg-light">{!! nl2br(e($serviceRequest->feedback->comment)) !!}</div>
                        </div>

                        <div class="mb-3">
                            <strong>Public Response</strong>
                            <div class="border rounded p-3 mt-2">
                                {!! nl2br(e($serviceRequest->feedback->public_response ?: 'No public response yet.')) !!}
                            </div>
                        </div>

                        <div>
                            <strong>Private Response</strong>
                            <div class="border rounded p-3 mt-2">
                                {!! nl2br(e($serviceRequest->feedback->private_response ?: 'No private response yet.')) !!}
                            </div>
                        </div>
                    @elseif ($serviceRequest->status === \App\Models\ServiceRequest::STATUS_COMPLETED)
                        <form method="POST" action="{{ route('citizen.requests.feedback.store', $serviceRequest) }}">
                            @csrf

                            <div class="form-group">
                                <label>Rating</label>
                                <select name="rating" class="custom-select @error('rating') is-invalid @enderror">
                                    <option value="">Select rating</option>
                                    @foreach (range(1, 5) as $rating)
                                        <option value="{{ $rating }}" {{ (string) old('rating') === (string) $rating ? 'selected' : '' }}>
                                            {{ $rating }} / 5
                                        </option>
                                    @endforeach
                                </select>
                                @error('rating')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>Comment</label>
                                <textarea name="comment" rows="4" class="form-control @error('comment') is-invalid @enderror" placeholder="Share your experience with this service.">{{ old('comment') }}</textarea>
                                @error('comment')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">Submit Feedback</button>
                        </form>
                    @else
                        <p class="text-muted mb-0">Feedback becomes available after this request is marked Completed.</p>
                    @endif
                </div>
            </div>

        </div>
    </div>
@endsection
