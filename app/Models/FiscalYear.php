<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiscalYear extends Model
{
    protected $fillable = [
        'name',
        'start_date_bs',
        'end_date_bs',
        'start_date_ad',
        'end_date_ad',
        'is_active',
        'is_closed',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_closed' => 'boolean',
        'start_date_ad' => 'date',
        'end_date_ad' => 'date',
    ];

    public static function current(): ?self
    {
        return static::where('is_active', true)->first();
    }
}

