<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolProfile extends Model
{
    protected $fillable = [
        'name',
        'name_ne',
        'emis_code',
        'school_type',
        'boarding_type',
        'province_id',
        'district_id',
        'local_level_id',
        'local_level_manual',
        'ward_no',
        'tole',
        'pan_number',
        'vat_number',
        'established_bs',
        'established_ad',
        'phone',
        'email',
        'website',
        'calendar_preference',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'established_ad' => 'date',
    ];

    public function province()
    {
        return $this->belongsTo(NepalProvince::class, 'province_id');
    }

    public function district()
    {
        return $this->belongsTo(NepalDistrict::class, 'district_id');
    }

    public function localLevel()
    {
        return $this->belongsTo(NepalLocalLevel::class, 'local_level_id');
    }

    public static function current(): ?self
    {
        return static::where('is_active', true)->first();
    }
}

