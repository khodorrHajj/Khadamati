<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NationalId extends Model
{
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'uploaded_by',
        'pending_registration_id',
        'identity_verification_id',
        'national_id_number',
        'national_id_number_normalized',
        'first_name_ar',
        'family_name_ar',
        'father_name_ar',
        'mother_name_ar',
        'mother_family_name_ar',
        'place_of_birth_ar',
        'date_of_birth_text',
        'gender_ar',
        'marital_status_ar',
        'record_number',
        'locality_ar',
        'governorate_ar',
        'district_ar',
        'blood_type',
        'issue_date_text',
        'raw_ocr_text',
        'id_image_path',
        'id_image_back_path',
        'ocr_confidence',
        'status',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'ocr_confidence' => 'float',
        'reviewed_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING_REVIEW,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
        ];
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'id');
    }

    public function pendingRegistration()
    {
        return $this->belongsTo(PendingRegistration::class, 'pending_registration_id', 'id');
    }
}
