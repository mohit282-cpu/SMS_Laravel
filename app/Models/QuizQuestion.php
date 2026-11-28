<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    protected $fillable = [
        'online_quiz_id', 'question_text', 'option_a', 'option_b',
        'option_c', 'option_d', 'correct_option', 'marks', 'explanation',
    ];

    public function quiz()
    {
        return $this->belongsTo(OnlineQuiz::class, 'online_quiz_id');
    }
}
