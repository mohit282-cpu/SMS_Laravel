<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class LessonPlan extends Model
{
    protected $fillable = [
        'teacher_id', 'my_class_id', 'section_id', 'subject_id',
        'unit_title', 'lesson_topic', 'learning_objectives', 'teaching_aids',
        'planned_date_ad', 'planned_date_bs', 'completed_date_ad', 'completed_date_bs',
        'completion_status', 'daily_report_notes',
    ];

    protected $casts = [
        'planned_date_ad' => 'date',
        'completed_date_ad' => 'date',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function my_class()
    {
        return $this->belongsTo(MyClass::class, 'my_class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
