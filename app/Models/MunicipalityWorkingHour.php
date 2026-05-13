<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MunicipalityWorkingHour extends Model
{
    protected $fillable = [
        'municipality_id',
        'day_of_week',
        'is_open',
        'start_time',
        'end_time',
    ];

    public function municipality()
    {
        return $this->belongsTo(Municipality::class, 'municipality_id', 'id');
    }
}