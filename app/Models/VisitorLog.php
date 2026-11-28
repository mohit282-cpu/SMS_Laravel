<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class VisitorLog extends Model
{
    protected $fillable = [
        'visitor_name', 'phone', 'visitor_type', 'student_record_id',
        'person_to_meet', 'purpose', 'id_card_no', 'visit_date_ad',
        'visit_date_bs', 'check_in_time', 'check_out_time', 'remarks', 'recorded_by',
    ];

    protected $casts = [
        'visit_date_ad' => 'date',
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
