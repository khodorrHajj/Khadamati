<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\RequestDocument;
use App\Models\RequestTimelineEntry;
use App\Models\ServiceRequest;
use App\Models\User;

class RequestTimelineService
{
    public function recordRequestSubmitted(ServiceRequest $serviceRequest, ?int $actorId = null): void
    {
        $request = $serviceRequest->loadMissing(['service', 'user']);
        $serviceName = $request->service?->name ?? 'service';

        $this->createEntry(
            $request,
            'request_submitted',
            'Request submitted',
            sprintf('A citizen submitted a request for %s.', $serviceName),
            $actorId ?? $request->user_id,
            'Citizen',
            [
                'tracking_code' => $request->tracking_code,
                'service_name' => $serviceName,
            ]
        );
    }

    public function recordDocumentUploaded(ServiceRequest $serviceRequest, RequestDocument $document): void
    {
        $request = $serviceRequest->loadMissing('service');
        $documentName = $document->original_name ?: basename((string) $document->document_path);
        $documentType = $document->document_type ?: 'Submitted document';
        $actorLabel = $document->uploader?->hasRole('citizen') ? 'Citizen' : ($document->uploader?->name ?? 'User');

        $this->createEntry(
            $request,
            'document_uploaded',
            'Document uploaded',
            sprintf('%s uploaded %s (%s).', $actorLabel, $documentName, $documentType),
            $document->uploaded_by,
            $actorLabel,
            [
                'document_id' => $document->id,
                'document_name' => $documentName,
                'document_type' => $documentType,
            ]
        );
    }

    public function recordStatusChanged(
        ServiceRequest $serviceRequest,
        string $previousStatus,
        string $actorLabel,
        ?int $actorId = null
    ): void {
        $request = $serviceRequest->loadMissing('service');
        $serviceName = $request->service?->name ?? 'service request';

        $this->createEntry(
            $request,
            'status_changed',
            'Request status updated',
            sprintf(
                '%s changed the status for %s from %s to %s.',
                $actorLabel,
                $serviceName,
                $previousStatus,
                $request->status
            ),
            $actorId,
            $actorLabel,
            [
                'previous_status' => $previousStatus,
                'status' => $request->status,
            ]
        );
    }

    public function recordOfficialResponseUploaded(
        ServiceRequest $serviceRequest,
        string $actorLabel,
        ?int $actorId = null
    ): void {
        $request = $serviceRequest->loadMissing('service');
        $serviceName = $request->service?->name ?? 'service request';
        $documentName = $request->official_response_original_name ?: 'official response';
        $documentType = $request->official_response_document_type ?: 'Official Response';

        $this->createEntry(
            $request,
            'official_response_uploaded',
            'Official response uploaded',
            sprintf(
                '%s uploaded %s for %s.',
                $actorLabel,
                $documentName,
                $serviceName
            ),
            $actorId,
            $actorLabel,
            [
                'document_name' => $documentName,
                'document_type' => $documentType,
            ]
        );
    }

    public function recordAppointmentRequested(Appointment $appointment): void
    {
        $appointment->loadMissing(['serviceRequest.service', 'timeSlot', 'user']);
        $serviceName = $appointment->serviceRequest?->service?->name ?? 'service';

        $this->createEntry(
            $appointment->serviceRequest,
            'appointment_requested',
            'Appointment requested',
            sprintf(
                'A citizen requested an appointment for %s on %s.',
                $serviceName,
                $this->slotText($appointment)
            ),
            $appointment->user_id,
            'Citizen',
            [
                'appointment_id' => $appointment->id,
                'status' => $appointment->status,
                'scheduled_for' => $appointment->timeSlot?->starts_at?->toIso8601String(),
            ]
        );
    }

    public function recordAppointmentUpdated(
        Appointment $appointment,
        string $actorLabel,
        ?int $actorId = null
    ): void {
        $appointment->loadMissing(['serviceRequest.service', 'timeSlot']);
        $serviceName = $appointment->serviceRequest?->service?->name ?? 'service';

        [$eventType, $title, $description] = match ($appointment->status) {
            Appointment::STATUS_APPROVED => [
                'appointment_approved',
                'Appointment approved',
                sprintf('%s approved the appointment for %s on %s.', $actorLabel, $serviceName, $this->slotText($appointment)),
            ],
            Appointment::STATUS_RESCHEDULED => [
                'appointment_rescheduled',
                'Appointment rescheduled',
                sprintf('%s rescheduled the appointment for %s to %s.', $actorLabel, $serviceName, $this->slotText($appointment)),
            ],
            Appointment::STATUS_CANCELLED => [
                'appointment_cancelled',
                'Appointment cancelled',
                sprintf('%s cancelled the appointment for %s.', $actorLabel, $serviceName),
            ],
            default => [
                'appointment_updated',
                'Appointment updated',
                sprintf('%s updated the appointment for %s.', $actorLabel, $serviceName),
            ],
        };

        $this->createEntry(
            $appointment->serviceRequest,
            $eventType,
            $title,
            $description,
            $actorId,
            $actorLabel,
            [
                'appointment_id' => $appointment->id,
                'status' => $appointment->status,
                'scheduled_for' => $appointment->timeSlot?->starts_at?->toIso8601String(),
            ]
        );
    }

    public function recordEscalatedToAdmin(
        ServiceRequest $serviceRequest,
        string $reason,
        string $actorLabel,
        ?int $actorId = null
    ): void {
        $this->createEntry(
            $serviceRequest->loadMissing('service'),
            'escalated_to_admin',
            'Escalated to admin',
            sprintf(
                '%s escalated this request to admin review. Reason: %s',
                $actorLabel,
                $reason
            ),
            $actorId,
            $actorLabel,
            [
                'workflow_state' => ServiceRequest::WORKFLOW_AWAITING_ADMIN,
                'escalation_reason' => $reason,
            ]
        );
    }

    public function recordReturnedToMunicipality(
        ServiceRequest $serviceRequest,
        ?User $assignee,
        string $actorLabel,
        ?int $actorId = null
    ): void {
        $description = $assignee
            ? sprintf('%s returned this request to municipality follow-up and assigned it to %s.', $actorLabel, $assignee->name)
            : sprintf('%s returned this request to the municipality queue for follow-up.', $actorLabel);

        $this->createEntry(
            $serviceRequest->loadMissing('service'),
            'returned_to_municipality',
            'Returned to municipality',
            $description,
            $actorId,
            $actorLabel,
            [
                'workflow_state' => ServiceRequest::WORKFLOW_AWAITING_MUNICIPALITY,
                'assigned_to_user_id' => $assignee?->id,
                'assigned_to_name' => $assignee?->name,
            ]
        );
    }

    public function recordAssignedToMunicipalityUser(
        ServiceRequest $serviceRequest,
        User $assignee,
        string $actorLabel,
        ?int $actorId = null
    ): void {
        $this->createEntry(
            $serviceRequest->loadMissing('service'),
            'assigned_to_municipality_user',
            'Assigned to municipality user',
            sprintf('%s assigned this request to %s.', $actorLabel, $assignee->name),
            $actorId,
            $actorLabel,
            [
                'assigned_to_user_id' => $assignee->id,
                'assigned_to_name' => $assignee->name,
            ]
        );
    }

    private function createEntry(
        ServiceRequest $serviceRequest,
        string $eventType,
        string $title,
        string $description,
        ?int $actorId = null,
        ?string $actorLabel = null,
        array $metadata = []
    ): RequestTimelineEntry {
        return $serviceRequest->timelineEntries()->create([
            'actor_id' => $actorId,
            'actor_label' => $actorLabel,
            'event_type' => $eventType,
            'title' => $title,
            'description' => $description,
            'metadata' => $metadata ?: null,
        ]);
    }

    private function slotText(Appointment $appointment): string
    {
        return $appointment->timeSlot?->starts_at
            ? $appointment->timeSlot->starts_at->format('Y-m-d H:i')
            : 'a time to be confirmed';
    }
}
