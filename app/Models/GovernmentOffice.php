<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GovernmentOffice extends Model
{
    protected $fillable = [
        'municipality_id',
        'name',
        'service_type',
        'phone',
        'email',
        'address',
        'city',
        'street',
        'building',
        'google_maps_location',
        'google_maps_url',
        'latitude',
        'longitude',
        'place_id',
        'formatted_address',
        'status',
        'notes',
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

    public function staff()
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

    public function workingHours()
    {
        return $this->hasMany(GovernmentOfficeWorkingHour::class, 'government_office_id', 'id');
    }

    public function timeSlots()
    {
        return $this->hasMany(TimeSlot::class, 'government_office_id', 'id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'government_office_id', 'id');
    }
}
