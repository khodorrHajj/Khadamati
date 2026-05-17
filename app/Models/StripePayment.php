<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StripePayment extends Model
{
    protected $fillable = [
        'user_id',
        'service_id',
        'service_request_id',
        'stripe_session_id',
        'stripe_payment_intent_id',
        'price_amount',
        'price_amount_usd',
        'exchange_rate',
        'status',
        'payment_url',
    ];

    protected $casts = [
        'price_amount'     => 'decimal:2',
        'price_amount_usd' => 'decimal:2',
        'exchange_rate'    => 'decimal:6',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'id');
    }

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id', 'id');
    }
}
