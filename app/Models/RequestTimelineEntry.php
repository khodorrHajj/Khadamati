<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestTimelineEntry extends Model
{
    protected $fillable = [
        'service_request_id',
        'actor_id',
        'actor_label',
        'event_type',
        'title',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id', 'id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id', 'id');
    }
}
