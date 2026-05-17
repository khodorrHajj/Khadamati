<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class IdentityVerification extends Model
{
    public const STATUS_PENDING_UPLOAD = 'pending_upload';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_NEEDS_REVIEW = 'needs_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'id_image_path',
        'id_image_back_path',
        'status',
        'extracted_first_name',
        'extracted_family_name',
        'extracted_father_name',
        'extracted_mother_name',
        'extracted_mother_family_name',
        'extracted_full_name',
        'extracted_place_of_birth',
        'extracted_date_of_birth_text',
        'extracted_id_number',
        'extracted_date_of_birth',
        'extracted_gender',
        'extracted_marital_status',
        'extracted_record_number',
        'extracted_locality',
        'extracted_governorate',
        'extracted_district',
        'extracted_blood_type',
        'extracted_issue_date_text',
        'ocr_confidence',
        'ocr_raw_text',
        'ocr_raw_json',
        'quality_result_json',
        'exif_result_json',
        'validation_result_json',
        'reviewed_by',
        'reviewed_at',
        'admin_notes',
    ];

    protected $casts = [
        'extracted_date_of_birth' => 'date',
        'ocr_confidence' => 'float',
        'ocr_raw_json' => 'array',
        'quality_result_json' => 'array',
        'exif_result_json' => 'array',
        'validation_result_json' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING_UPLOAD,
            self::STATUS_PROCESSING,
            self::STATUS_NEEDS_REVIEW,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_FAILED,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'id');
    }

    public function getIdImageUrlAttribute(): ?string
    {
        if (!$this->id_image_path) {
            return null;
        }

        return Storage::disk('public')->url($this->id_image_path);
    }

    public function getIdImageBackUrlAttribute(): ?string
    {
        if (!$this->id_image_back_path) {
            return null;
        }

        return Storage::disk('public')->url($this->id_image_back_path);
    }
}
