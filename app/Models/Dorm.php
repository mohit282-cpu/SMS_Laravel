<?php

namespace App\Models;

use Eloquent;

class Dorm extends Eloquent
{
    protected $fillable = ['name', 'description'];

    public function students()
    {
        return $this->hasMany(StudentRecord::class, 'dorm_id');
    }
}
