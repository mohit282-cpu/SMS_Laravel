<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class StudentWellnessLog extends Model
{
    protected $fillable = [
        'student_record_id', 'recorded_by', 'category', 'title', 'points',
        'description', 'log_date_ad', 'log_date_bs', 'vitals_summary', 'action_taken',
    ];

    protected $casts = [
        'log_date_ad' => 'date',
    ];

    public function studentRecord()
    {
        return $this->belongsTo(StudentRecord::class, 'student_record_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
