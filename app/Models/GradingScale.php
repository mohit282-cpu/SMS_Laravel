<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradingScale extends Model
{
    protected $fillable = [
        'name',
        'authority',
        'academic_year',
        'education_level_code',
        'grade_letter',
        'min_percentage',
        'max_percentage',
        'grade_point',
        'remark',
        'remark_ne',
        'is_passing',
        'is_active',
    ];

    protected $casts = [
        'is_passing' => 'boolean',
        'is_active' => 'boolean',
    ];
}

