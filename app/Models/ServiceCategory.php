<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceCategory extends Model
{
    protected $fillable = [
        'government_office_id',
        'name',
        'description',
    ];

    public function governmentOffice()
    {
        return $this->belongsTo(GovernmentOffice::class, 'government_office_id', 'id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'service_category_id', 'id');
    }
}