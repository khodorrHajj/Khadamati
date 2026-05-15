<?php

namespace App\Models;

use App\Services\RequestTimelineService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ServiceRequest extends Model
{
    public const STATUS_PENDING = 'Pending';
    public const STATUS_IN_REVIEW = 'In Review';
    public const STATUS_MISSING_DOCUMENTS = 'Missing Documents';
    public const STATUS_APPROVED = 'Approved';
    public const STATUS_REJECTED = 'Rejected';
    public const STATUS_COMPLETED = 'Completed';
    public const WORKFLOW_AWAITING_MUNICIPALITY = 'awaiting_municipality';
    public const WORKFLOW_AWAITING_ADMIN = 'awaiting_admin';

    protected $fillable = [
        'user_id',
        'service_id',
        'tracking_code',
        'status',
        'message',
        'missing_document_items',
        'admin_internal_note',
        'assigned_to_user_id',
        'assigned_by_user_id',
        'assigned_at',
        'workflow_state',
        'escalated_to_admin_at',
        'escalation_reason',
        'official_response_path',
        'official_response_original_name',
        'official_response_uploaded_by',
        'official_response_document_type',
    ];

    protected $appends = [
        'notes',
        'official_response_url',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'escalated_to_admin_at' => 'datetime',
        'missing_document_items' => 'array',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_IN_REVIEW,
            self::STATUS_MISSING_DOCUMENTS,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_COMPLETED,
        ];
    }

    public static function terminalStatuses(): array
    {
        return [
            self::STATUS_COMPLETED,
            self::STATUS_REJECTED,
        ];
    }

    public static function workflowStates(): array
    {
        return [
            self::WORKFLOW_AWAITING_MUNICIPALITY,
            self::WORKFLOW_AWAITING_ADMIN,
        ];
    }

    public static function resetHandoffContextAttributes(): array
    {
        return [
            'assigned_to_user_id' => null,
            'assigned_by_user_id' => null,
            'assigned_at' => null,
            'workflow_state' => self::WORKFLOW_AWAITING_MUNICIPALITY,
            'escalated_to_admin_at' => null,
            'escalation_reason' => null,
        ];
    }

    public static function clearEscalationContextAttributes(): array
    {
        return [
            'workflow_state' => self::WORKFLOW_AWAITING_MUNICIPALITY,
            'escalated_to_admin_at' => null,
            'escalation_reason' => null,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ServiceRequest $serviceRequest) {
            if (!$serviceRequest->tracking_code) {
                $serviceRequest->tracking_code = static::generateTrackingCode();
            }

            if (!$serviceRequest->status) {
                $serviceRequest->status = self::STATUS_PENDING;
            }

            if (!$serviceRequest->workflow_state) {
                $serviceRequest->workflow_state = self::WORKFLOW_AWAITING_MUNICIPALITY;
            }
        });

        static::created(function (ServiceRequest $serviceRequest) {
            app(RequestTimelineService::class)->recordRequestSubmitted($serviceRequest, $serviceRequest->user_id);
        });
    }

    public function getNotesAttribute(): ?string
    {
        return $this->message;
    }

    public function getOfficialResponseUrlAttribute(): ?string
    {
        if (!$this->official_response_path) {
            return null;
        }

        return Storage::disk('public')->url($this->official_response_path);
    }

    public static function generateTrackingCode(): string
    {
        do {
            $code = 'REQ-' . Str::upper(Str::random(10));
        } while (static::where('tracking_code', $code)->exists());

        return $code;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'id');
    }

    public function requestDocuments()
    {
        return $this->hasMany(RequestDocument::class, 'service_request_id', 'id');
    }

    public function feedback()
    {
        return $this->hasOne(Feedback::class, 'service_request_id', 'id');
    }

    public function requestMessages()
    {
        return $this->hasMany(RequestMessage::class, 'service_request_id', 'id')
            ->orderBy('created_at');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'service_request_id', 'id')
            ->latest();
    }

    public function officialResponseUploader()
    {
        return $this->belongsTo(User::class, 'official_response_uploaded_by', 'id');
    }

    public function cryptoPayment()
    {
        return $this->hasOne(CryptoPayment::class, 'service_request_id', 'id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id', 'id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id', 'id');
    }

    public function needsCitizenDocuments(): bool
    {
        return $this->status === self::STATUS_MISSING_DOCUMENTS;
    }

    public function missingDocumentList(): array
    {
        return collect($this->missing_document_items ?? [])
            ->map(fn ($document) => trim((string) $document))
            ->filter()
            ->values()
            ->all();
    }

    public function isAwaitingAdmin(): bool
    {
        return $this->workflow_state === self::WORKFLOW_AWAITING_ADMIN;
    }

    public function isAwaitingMunicipality(): bool
    {
        return $this->workflow_state === self::WORKFLOW_AWAITING_MUNICIPALITY;
    }

    public function isAssigned(): bool
    {
        return (bool) $this->assigned_to_user_id;
    }

    public function hasEscalation(): bool
    {
        return (bool) $this->escalated_to_admin_at;
    }

    public function isClosed(): bool
    {
        return in_array($this->status, self::terminalStatuses(), true);
    }

    public function dueAt(): ?Carbon
    {
        $durationDays = (int) ($this->service?->durationToDays() ?? 0);

        if (!$this->created_at || $durationDays <= 0) {
            return null;
        }

        return $this->created_at->copy()->addDays($durationDays);
    }

    public function isOverdue(): bool
    {
        $dueAt = $this->dueAt();

        if (!$dueAt || $this->isClosed() || $this->needsCitizenDocuments()) {
            return false;
        }

        return now()->greaterThan($dueAt);
    }

    public function overdueDays(): int
    {
        $dueAt = $this->dueAt();

        if (!$dueAt || !$this->isOverdue()) {
            return 0;
        }

        return $dueAt->diffInDays(now());
    }

    public function timelineEntries()
    {
        return $this->hasMany(RequestTimelineEntry::class, 'service_request_id', 'id')
            ->latest();
    }

    public function timelineForDisplay(): Collection
    {
        $entries = $this->relationLoaded('timelineEntries')
            ? $this->timelineEntries
            : $this->timelineEntries()->with('actor.role')->get();

        if ($entries->contains(fn (RequestTimelineEntry $entry) => $entry->event_type === 'request_submitted')) {
            return $entries;
        }

        $syntheticEntry = new RequestTimelineEntry([
            'service_request_id' => $this->id,
            'actor_id' => $this->user_id,
            'actor_label' => 'Citizen',
            'event_type' => 'request_submitted',
            'title' => 'Request submitted',
            'description' => sprintf(
                'A citizen submitted a request for %s.',
                $this->service?->name ?? 'service'
            ),
        ]);
        $syntheticEntry->created_at = $this->created_at;
        $syntheticEntry->setRelation('actor', $this->user);

        return $entries->push($syntheticEntry)
            ->sortByDesc(fn (RequestTimelineEntry $entry) => $entry->created_at?->getTimestamp() ?? 0)
            ->values();
    }
}
