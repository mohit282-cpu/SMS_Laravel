<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentGroupMember extends Model
{
    protected $fillable = [
        'student_group_id', 'student_record_id', 'role_in_group',
    ];

    public function group()
    {
        return $this->belongsTo(StudentGroup::class, 'student_group_id');
    }

    public function studentRecord()
    {
        return $this->belongsTo(StudentRecord::class, 'student_record_id');
    }
}
