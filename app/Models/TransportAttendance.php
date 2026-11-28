<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class TransportAttendance extends Model
{
    protected $fillable = [
        'transport_route_id', 'student_record_id', 'attendance_date_ad',
        'attendance_date_bs', 'trip_type', 'status', 'remarks', 'marked_by',
    ];

    protected $casts = [
        'attendance_date_ad' => 'date',
    ];

    public function route()
    {
        return $this->belongsTo(TransportRoute::class, 'transport_route_id');
    }

    public function studentRecord()
    {
        return $this->belongsTo(StudentRecord::class, 'student_record_id');
    }

    public function marker()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
