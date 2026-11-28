<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class ComplaintFeedback extends Model
{
    protected $table = 'complaints_feedback';

    protected $fillable = [
        'submitted_by', 'student_record_id', 'entry_type', 'subject',
        'description', 'priority', 'status', 'resolution_notes',
        'handled_by', 'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function studentRecord()
    {
        return $this->belongsTo(StudentRecord::class, 'student_record_id');
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
