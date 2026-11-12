<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NepalEducationLevel extends Model
{
    protected $fillable = ['code', 'name', 'name_ne', 'grade_range', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function curricula()
    {
        return $this->hasMany(Curriculum::class, 'education_level_id');
    }
}

