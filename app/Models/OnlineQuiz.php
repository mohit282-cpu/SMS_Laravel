<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class OnlineQuiz extends Model
{
    protected $fillable = [
        'created_by', 'my_class_id', 'section_id', 'subject_id',
        'title', 'description', 'duration_minutes', 'pass_marks',
        'scheduled_date_ad', 'scheduled_date_bs', 'is_published',
    ];

    protected $casts = [
        'scheduled_date_ad' => 'date',
        'is_published' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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

    public function questions()
    {
        return $this->hasMany(QuizQuestion::class, 'online_quiz_id');
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class, 'online_quiz_id');
    }
}
