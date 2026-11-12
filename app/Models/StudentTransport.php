<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentTransport extends Model
{
    protected $fillable = [
        'student_record_id',
        'transport_route_id',
        'pickup_point',
        'academic_year',
        'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function route()
    {
        return $this->belongsTo(TransportRoute::class, 'transport_route_id');
    }

    public function studentRecord()
    {
        return $this->belongsTo(StudentRecord::class, 'student_record_id');
    }
}

