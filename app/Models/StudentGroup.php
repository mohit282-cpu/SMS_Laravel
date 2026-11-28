<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class StudentGroup extends Model
{
    protected $fillable = [
        'name', 'category', 'description', 'incharge_user_id', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function incharge()
    {
        return $this->belongsTo(User::class, 'incharge_user_id');
    }

    public function members()
    {
        return $this->hasMany(StudentGroupMember::class, 'student_group_id');
    }
}
