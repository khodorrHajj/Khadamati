<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CryptoPayment extends Model
{
    protected $fillable = [
        'user_id',
        'service_id',
        'service_request_id',
        'nowpayments_payment_id',
        'nowpayments_invoice_id',
        'price_amount',
        'status',
        'payment_url',
        'payin_address',
        'actually_paid',
    ];

    protected $casts = [
        'price_amount'  => 'decimal:2',
        'actually_paid' => 'decimal:8',
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
