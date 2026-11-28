<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Homework extends Model
{
    protected $table = 'homeworks';

    protected $fillable = [
        'teacher_id', 'my_class_id', 'section_id', 'subject_id',
        'title', 'description', 'homework_type', 'attachment_path',
        'reference_link', 'assigned_date_ad', 'assigned_date_bs',
        'due_date_ad', 'due_date_bs', 'max_marks', 'is_published',
    ];

    protected $casts = [
        'assigned_date_ad' => 'date',
        'due_date_ad' => 'date',
        'is_published' => 'boolean',
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

    public function submissions()
    {
        return $this->hasMany(HomeworkSubmission::class, 'homework_id');
    }
}
