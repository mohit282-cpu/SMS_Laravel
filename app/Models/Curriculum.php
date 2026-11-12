<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Curriculum extends Model
{
    protected $fillable = [
        'name',
        'version',
        'academic_year_id',
        'education_level_id',
        'stream',
        'effective_from_bs',
        'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function educationLevel()
    {
        return $this->belongsTo(NepalEducationLevel::class, 'education_level_id');
    }

    public function subjects()
    {
        return $this->hasMany(CurriculumSubject::class, 'curriculum_id');
    }
}

