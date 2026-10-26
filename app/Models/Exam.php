<?php

namespace App\Models;

use Eloquent;

class Exam extends Eloquent
{
    protected $fillable = [
        'name',
        'term',
        'year',
        'exam_type',
        'start_date',
        'end_date',
        'start_date_bs',
        'end_date_bs',
        'status',
        'description',
        'published_by',
        'published_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'published_at' => 'datetime',
        'term' => 'integer',
    ];

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function publisher()
    {
        return $this->belongsTo(\App\User::class, 'published_by');
    }
}
