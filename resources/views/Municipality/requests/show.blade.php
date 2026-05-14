@extends('layouts.municipality')

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
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Request #{{ $serviceRequest->id }}</h3>
                    <a href="{{ route('municipality.requests.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Requests
                    </a>
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
                                        <td><span class="badge badge-light border">{{ $serviceRequest->status }}</span></td>
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
                                                ${{ number_format((float) $serviceRequest->service->price, 2) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Duration</th>
                                        <td>
                                            @if ($serviceRequest->service)
                                                {{ $serviceRequest->service->duration_days }} day{{ $serviceRequest->service->duration_days === 1 ? '' : 's' }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Official Response</th>
                                        <td>
                                            @if ($serviceRequest->official_response_url)
                                                <a href="{{ $serviceRequest->official_response_url }}" target="_blank" rel="noopener">
                                                    {{ $serviceRequest->official_response_original_name ?: 'Download file' }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title">Submitted Documents</h3>
                        </div>
                        <div class="card-body">
                            @forelse ($serviceRequest->requestDocuments as $document)
                                <div class="mb-2">
                                    @if ($document->document_path)
                                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($document->document_path) }}" target="_blank" rel="noopener">
                                            {{ $document->original_name ?: basename($document->document_path) }}
                                        </a>
                                    @else
                                        {{ $document->original_name ?: 'Document' }}
                                    @endif
                                </div>
                            @empty
                                <p class="text-muted mb-0">No submitted documents.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title">Notes</h3>
                        </div>
                        <div class="card-body">
                            {!! nl2br(e($serviceRequest->notes ?: 'No notes added yet.')) !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Update Request</h3>
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
                            @error('notes')
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

                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-save"></i> Update Request
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Appointment</h3>
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

    <div class="card mt-3">
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
                                @if ($messageItem->sender && $messageItem->sender->role)
                                    <span class="badge badge-light border ml-2">{{ ucfirst($messageItem->sender->role->role) }}</span>
                                @endif
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

            <form method="POST" action="{{ route('municipality.requests.messages.store', $serviceRequest) }}" enctype="multipart/form-data">
                @csrf

                <div class="form-group">
                    <label>Message</label>
                    <textarea name="body" rows="4" class="form-control @error('body') is-invalid @enderror" placeholder="Write your message to the citizen.">{{ old('body') }}</textarea>
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

                <button type="submit" class="btn btn-primary">Send Message</button>
            </form>
        </div>
    </div>
@endsection
