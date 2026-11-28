<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    protected $fillable = [
        'online_quiz_id', 'student_record_id', 'answers_json',
        'score', 'total_marks', 'percentage', 'passed', 'submitted_at',
    ];

    protected $casts = [
        'answers_json' => 'array',
        'passed' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    public function quiz()
    {
        return $this->belongsTo(OnlineQuiz::class, 'online_quiz_id');
    }

    public function studentRecord()
    {
        return $this->belongsTo(StudentRecord::class, 'student_record_id');
    }
}
