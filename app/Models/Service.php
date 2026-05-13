<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'government_office_id',
        'service_category_id',
        'name',
        'description',
        'price',
        'duration_days',
        'required_documents',
        'is_active',
    ];

    public function governmentOffice()
    {
        return $this->belongsTo(GovernmentOffice::class, 'government_office_id', 'id');
    }

    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id', 'id');
    }

    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'service_id', 'id');
    }
}