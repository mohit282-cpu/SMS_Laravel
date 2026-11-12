<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurriculumSubject extends Model
{
    protected $fillable = [
        'curriculum_id',
        'subject_id',
        'subject_code',
        'subject_name',
        'subject_name_ne',
        'subject_type',
        'theory_max',
        'theory_pass',
        'practical_max',
        'practical_pass',
        'internal_max',
        'credit_hour',
    ];

    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class, 'curriculum_id');
    }
}

