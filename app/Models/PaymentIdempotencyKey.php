<?php

namespace App\Models;

use App\User;
use Eloquent;

class PaymentIdempotencyKey extends Eloquent
{
    protected $fillable = [
        'user_id',
        'operation',
        'idempotency_key',
        'payload_hash',
        'status',
        'payment_record_id',
        'amount',
        'response_code',
        'response_body',
    ];

    protected $casts = [
        'amount' => 'float',
        'response_code' => 'integer',
        'response_body' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payment_record()
    {
        return $this->belongsTo(PaymentRecord::class, 'payment_record_id');
    }
}
