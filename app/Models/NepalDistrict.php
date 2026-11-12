<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NepalDistrict extends Model
{
    protected $fillable = ['province_id', 'name', 'name_ne', 'code'];

    public function province()
    {
        return $this->belongsTo(NepalProvince::class, 'province_id');
    }

    public function localLevels()
    {
        return $this->hasMany(NepalLocalLevel::class, 'district_id');
    }
}

