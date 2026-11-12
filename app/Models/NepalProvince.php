<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NepalProvince extends Model
{
    protected $fillable = ['country_code', 'name', 'name_ne', 'code', 'is_active', 'effective_date', 'data_source'];

    public function districts()
    {
        return $this->hasMany(NepalDistrict::class, 'province_id');
    }
}

