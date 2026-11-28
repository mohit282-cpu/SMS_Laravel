<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class CanteenLunchLog extends Model
{
    protected $fillable = [
        'student_record_id', 'log_date_ad', 'log_date_bs', 'meal_type',
        'status', 'unit_charge', 'remarks', 'marked_by',
    ];

    protected $casts = [
        'log_date_ad' => 'date',
    ];

    public function studentRecord()
    {
        return $this->belongsTo(StudentRecord::class, 'student_record_id');
    }

    public function marker()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
