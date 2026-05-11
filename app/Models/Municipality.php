<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Municipality extends Model
{
    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'city',
        'street',
        'building',
        'google_maps_url',
        'latitude',
        'longitude',
        'place_id',
        'formatted_address',
        'status',
        'notes',
    ];

    public function governmentOffices()
    {
        return $this->hasMany(GovernmentOffice::class, 'municipality_id', 'id');
    }

    public function workingHours()
    {
        return $this->hasMany(MunicipalityWorkingHour::class, 'municipality_id', 'id');
    }
}
