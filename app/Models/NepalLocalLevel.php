<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NepalLocalLevel extends Model
{
    protected $fillable = ['district_id', 'name', 'name_ne', 'code', 'level_type', 'total_wards', 'is_active', 'effective_date', 'data_source'];

    public function district()
    {
        return $this->belongsTo(NepalDistrict::class, 'district_id');
    }
}

