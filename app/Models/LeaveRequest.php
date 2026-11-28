<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $fillable = [
        'user_id', 'student_record_id', 'leave_scope', 'leave_type',
        'from_date_ad', 'from_date_bs', 'to_date_ad', 'to_date_bs',
        'total_days', 'reason', 'status', 'reviewed_by', 'reviewed_at', 'review_remarks',
    ];

    protected $casts = [
        'from_date_ad' => 'date',
        'to_date_ad' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function applicant()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function studentRecord()
    {
        return $this->belongsTo(StudentRecord::class, 'student_record_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
