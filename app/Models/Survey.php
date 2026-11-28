<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    protected $fillable = [
        'created_by', 'title', 'description', 'target_role',
        'question_1', 'question_2', 'question_3', 'is_active', 'closes_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'closes_at' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responses()
    {
        return $this->hasMany(SurveyResponse::class, 'survey_id');
    }
}
