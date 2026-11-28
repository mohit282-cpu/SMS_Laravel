<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HostelAllocation extends Model
{
    protected $fillable = [
        'student_record_id', 'dorm_id', 'room_no', 'bed_no',
        'monthly_fee', 'meal_plan', 'assigned_date_bs', 'status', 'remarks',
    ];

    public function studentRecord()
    {
        return $this->belongsTo(StudentRecord::class, 'student_record_id');
    }

    public function dorm()
    {
        return $this->belongsTo(Dorm::class, 'dorm_id');
    }
}
