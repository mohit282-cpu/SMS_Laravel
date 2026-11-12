<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NepalLocalLevel extends Model
{
    protected $fillable = ['district_id', 'name', 'name_ne', 'level_type', 'total_wards'];

    public function district()
    {
        return $this->belongsTo(NepalDistrict::class, 'district_id');
    }
}

