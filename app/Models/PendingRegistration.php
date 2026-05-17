<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingRegistration extends Model
{
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'national_id_id',
        'status',
        'admin_notes',
    ];

    public function nationalId()
    {
        return $this->belongsTo(NationalId::class, 'national_id_id', 'id');
    }
}
