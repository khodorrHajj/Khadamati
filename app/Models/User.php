<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role_id',
        'is_active',
        'status',
        'job_title',
        'email_verified_at',
        'google_id',
        'avatar',
        'two_factor_enabled',
        'two_factor_code',
        'two_factor_expires_at',
        'government_office_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_code',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_enabled' => 'boolean',
        'two_factor_expires_at' => 'datetime',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function hasRole($role)
    {
        return $this->role && $this->role->role === $role;
    }

    public function governmentOffice()
    {
        return $this->belongsTo(GovernmentOffice::class, 'government_office_id', 'id');
    }

    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'user_id', 'id');
    }

    public function identityVerifications()
    {
        return $this->hasMany(IdentityVerification::class, 'user_id', 'id');
    }

    public function latestIdentityVerification()
    {
        return $this->hasOne(IdentityVerification::class, 'user_id', 'id')->latestOfMany();
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class, 'user_id', 'id');
    }

    public function feedbackResponses()
    {
        return $this->hasMany(Feedback::class, 'responded_by', 'id');
    }

    public function sentRequestMessages()
    {
        return $this->hasMany(RequestMessage::class, 'sender_id', 'id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'user_id', 'id');
    }

    public function createdTimeSlots()
    {
        return $this->hasMany(TimeSlot::class, 'created_by', 'id');
    }

    public function approvedAppointments()
    {
        return $this->hasMany(Appointment::class, 'approved_by', 'id');
    }
}
