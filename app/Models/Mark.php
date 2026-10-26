<?php

namespace App\Models;

use App\User;
use Eloquent;

class Mark extends Eloquent
{
    protected $fillable = [
        't1', 't2', 't3', 't4', 'tca', 'exm', 'tex1', 'tex2', 'tex3',
        'sub_pos', 'cum', 'cum_ave', 'grade_id', 'year', 'exam_id',
        'subject_id', 'my_class_id', 'student_id', 'section_id',
        'theory_mark', 'practical_mark', 'total_mark', 'gpa_point', 'grade_name',
        'is_absent', 'is_exempt', 'is_passed', 'remarks', 'updated_by'
    ];

    protected $casts = [
        'is_absent' => 'boolean',
        'is_exempt' => 'boolean',
        'is_passed' => 'boolean',
        'theory_mark' => 'float',
        'practical_mark' => 'float',
        'total_mark' => 'float',
        'gpa_point' => 'float',
    ];

    public function audit_trails()
    {
        return $this->hasMany(MarkAuditTrail::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function my_class()
    {
        return $this->belongsTo(MyClass::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }
}
