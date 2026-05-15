<?php

namespace App\Models;

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

    protected $fillable = [
        'user_id',
        'service_id',
        'tracking_code',
        'status',
        'message',
        'official_response_path',
        'official_response_original_name',
    ];

    protected $appends = [
        'notes',
        'official_response_url',
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

    protected static function booted(): void
    {
        static::creating(function (ServiceRequest $serviceRequest) {
            if (!$serviceRequest->tracking_code) {
                $serviceRequest->tracking_code = static::generateTrackingCode();
            }

            if (!$serviceRequest->status) {
                $serviceRequest->status = self::STATUS_PENDING;
            }
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

    public function cryptoPayment()
    {
        return $this->hasOne(CryptoPayment::class, 'service_request_id', 'id');
    }
}
