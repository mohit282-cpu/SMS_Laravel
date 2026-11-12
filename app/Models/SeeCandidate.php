<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeeCandidate extends Model
{
    protected $fillable = [
        'student_record_id',
        'academic_year',
        'symbol_number',
        'registration_number',
        'school_code',
        'is_eligible',
        'optional_subject_1',
        'optional_subject_2',
        'internal_marks_total',
        'practical_marks_total',
        'theory_marks_total',
        'gpa',
        'overall_grade',
        'result_status',
        'remarks',
    ];

    protected $casts = ['is_eligible' => 'boolean'];

    public function studentRecord()
    {
        return $this->belongsTo(StudentRecord::class, 'student_record_id');
    }
}

