<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GovernmentOfficeWorkingHour extends Model
{
    protected $fillable = [
        'government_office_id',
        'day_of_week',
        'is_open',
        'start_time',
        'end_time',
    ];

    public function governmentOffice()
    {
        return $this->belongsTo(GovernmentOffice::class, 'government_office_id', 'id');
    }
}
