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
    ];

    public function governmentOffices()
    {
        return $this->hasMany(GovernmentOffice::class, 'municipality_id', 'id');
    }
}