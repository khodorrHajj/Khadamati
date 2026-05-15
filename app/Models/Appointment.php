<?php

namespace App\Models;

use App\Services\RequestTimelineService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    public const STATUS_REQUESTED = 'Requested';
    public const STATUS_APPROVED = 'Approved';
    public const STATUS_RESCHEDULED = 'Rescheduled';
    public const STATUS_CANCELLED = 'Cancelled';

    protected $fillable = [
        'service_request_id',
        'user_id',
        'government_office_id',
        'time_slot_id',
        'status',
        'notes',
        'municipality_notes',
        'approved_by',
        'approved_at',
        'cancelled_at',
        'reminder_scheduled_at',
        'reminder_sent_at',
        'email_notified_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'reminder_scheduled_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'email_notified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (Appointment $appointment) {
            if ($appointment->status === self::STATUS_REQUESTED) {
                app(RequestTimelineService::class)->recordAppointmentRequested($appointment);
            }
        });
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_REQUESTED,
            self::STATUS_APPROVED,
            self::STATUS_RESCHEDULED,
            self::STATUS_CANCELLED,
        ];
    }

    public static function reminderScheduleFor(?Carbon $startsAt): ?Carbon
    {
        if (!$startsAt) {
            return null;
        }

        return $startsAt->copy()->subDay();
    }

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function governmentOffice()
    {
        return $this->belongsTo(GovernmentOffice::class, 'government_office_id', 'id');
    }

    public function timeSlot()
    {
        return $this->belongsTo(TimeSlot::class, 'time_slot_id', 'id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }
}
