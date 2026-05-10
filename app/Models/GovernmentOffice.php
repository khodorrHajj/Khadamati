<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GovernmentOffice extends Model
{
    protected $fillable = [
        'municipality_id',
        'name',
        'address',
        'google_maps_location',
        'working_hours',
        'contact_info',
    ];

    public function municipality()
    {
        return $this->belongsTo(Municipality::class, 'municipality_id', 'id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'government_office_id', 'id');
    }

    public function serviceCategories()
    {
        return $this->hasMany(ServiceCategory::class, 'government_office_id', 'id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'government_office_id', 'id');
    }
}