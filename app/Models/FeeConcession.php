<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class FeeConcession extends Model
{
    protected $fillable = [
        'student_record_id', 'academic_year', 'title', 'concession_type',
        'amount_or_percent', 'reason', 'is_active', 'approved_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function studentRecord()
    {
        return $this->belongsTo(StudentRecord::class, 'student_record_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
