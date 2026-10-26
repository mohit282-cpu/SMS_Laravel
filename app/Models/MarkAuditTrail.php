<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarkAuditTrail extends Model
{
    protected $fillable = [
        'mark_id',
        'student_id',
        'subject_id',
        'exam_id',
        'action',
        'old_values',
        'new_values',
        'user_id',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    public function student()
    {
        return $this->belongsTo(\App\User::class, 'student_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }
}
