<?php

namespace App\Models;

use App\Services\RequestTimelineService;
use Illuminate\Database\Eloquent\Model;

class RequestDocument extends Model
{
    protected $fillable = [
        'service_request_id',
        'uploaded_by',
        'document_path',
        'original_name',
        'document_type',
    ];

    protected static function booted(): void
    {
        static::created(function (RequestDocument $requestDocument) {
            $requestDocument->loadMissing(['serviceRequest.service', 'uploader.role']);

            if ($requestDocument->serviceRequest) {
                app(RequestTimelineService::class)->recordDocumentUploaded($requestDocument->serviceRequest, $requestDocument);
            }
        });
    }

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id', 'id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id');
    }
}
