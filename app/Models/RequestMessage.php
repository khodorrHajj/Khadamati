<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class RequestMessage extends Model
{
    protected $fillable = [
        'service_request_id',
        'sender_id',
        'body',
        'attachment_path',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    protected $appends = [
        'attachment_url',
    ];

    public function getAttachmentUrlAttribute(): ?string
    {
        if (!$this->attachment_path) {
            return null;
        }

        return Storage::disk('public')->url($this->attachment_path);
    }

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id', 'id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id', 'id');
    }

    public function scopeForMunicipalityOffice(Builder $query, int $officeId): Builder
    {
        return $query->whereHas('serviceRequest.service', function ($serviceQuery) use ($officeId) {
            $serviceQuery->where('government_office_id', $officeId);
        });
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeFromCitizens(Builder $query): Builder
    {
        return $query->whereHas('sender.role', function ($roleQuery) {
            $roleQuery->where('role', 'citizen');
        });
    }
}
