<?php

namespace App\Models;

use App\User;
use Eloquent;

class Subject extends Eloquent
{
    protected $fillable = [
        'name', 'my_class_id', 'teacher_id', 'slug',
        'theory_max', 'theory_pass', 'practical_max', 'practical_pass',
        'total_max', 'total_pass', 'credit_hour', 'has_practical'
    ];

    protected $casts = [
        'has_practical' => 'boolean',
        'credit_hour' => 'float',
    ];

    public function my_class()
    {
        return $this->belongsTo(MyClass::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
