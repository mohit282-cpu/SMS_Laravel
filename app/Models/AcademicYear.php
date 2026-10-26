<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'year_bs',
        'start_date',
        'end_date',
        'start_date_bs',
        'end_date_bs',
        'is_active',
        'is_closed',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'is_closed' => 'boolean',
        'year_bs' => 'integer',
    ];

    /**
     * Scope for active academic year.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for open (not closed) academic years.
     */
    public function scopeOpen($query)
    {
        return $query->where('is_closed', false);
    }
}
