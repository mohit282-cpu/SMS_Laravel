<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportRoute extends Model
{
    protected $fillable = [
        'name',
        'name_ne',
        'vehicle_number',
        'driver_name',
        'driver_phone',
        'monthly_fee',
        'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function assignments()
    {
        return $this->hasMany(StudentTransport::class, 'transport_route_id');
    }
}

