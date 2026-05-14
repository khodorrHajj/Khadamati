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

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Request #{{ $serviceRequest->id }}</h3>
                    <a href="{{ route('tracking.show', $serviceRequest->tracking_code) }}" class="btn btn-secondary btn-sm">Public Tracking View</a>
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
                                <td>{{ $serviceRequest->status }}</td>
                            </tr>
                            <tr>
                                <th>Office</th>
                                <td>{{ $serviceRequest->service?->governmentOffice?->name ?? '-' }}</td>
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
                                <th>Notes</th>
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
                    <h3 class="card-title">Uploaded Documents</h3>
                </div>
                <div class="card-body">
                    @forelse ($serviceRequest->requestDocuments as $document)
                        <div class="mb-2">
                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($document->document_path) }}" target="_blank" rel="noopener">
                                {{ $document->original_name ?: basename($document->document_path) }}
                            </a>
                            @if ($document->document_type)
                                <span class="text-muted small">({{ $document->document_type }})</span>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">No documents uploaded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Upload Additional Document</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('citizen.requests.documents.store', $serviceRequest) }}" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group">
                            <label>Document Type</label>
                            <input type="text" name="document_type" value="{{ old('document_type') }}" class="form-control @error('document_type') is-invalid @enderror" placeholder="Optional label, e.g. ID Copy">
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

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Messages</h3>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        @forelse ($serviceRequest->requestMessages as $messageItem)
                            <div class="border rounded p-3 mb-3 {{ $messageItem->sender_id === Auth::id() ? 'bg-light' : '' }}">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong>{{ $messageItem->sender->name ?? 'Unknown User' }}</strong>
                                        <span class="text-muted small ml-2">
                                            {{ optional($messageItem->created_at)->format('Y-m-d H:i') ?: '-' }}
                                        </span>
                                    </div>
                                    @if ($messageItem->sender_id !== Auth::id() && !$messageItem->read_at)
                                        <span class="badge badge-info">Unread</span>
                                    @endif
                                </div>

                                @if (filled($messageItem->body))
                                    <div class="mb-2">{!! nl2br(e($messageItem->body)) !!}</div>
                                @endif

                                @if ($messageItem->attachment_url)
                                    <a href="{{ $messageItem->attachment_url }}" target="_blank" rel="noopener">
                                        Open attachment
                                    </a>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted mb-0">No messages yet.</p>
                        @endforelse
                    </div>

                    <form method="POST" action="{{ route('citizen.requests.messages.store', $serviceRequest) }}" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group">
                            <label>Message</label>
                            <textarea name="body" rows="4" class="form-control @error('body') is-invalid @enderror" placeholder="Write your message to the municipality.">{{ old('body') }}</textarea>
                            @error('body')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Attachment</label>
                            <input type="file" name="attachment" class="form-control-file @error('attachment') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,application/pdf,image/*">
                            <small class="form-text text-muted">Optional. PDF and image files up to 5 MB.</small>
                            @error('attachment')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
