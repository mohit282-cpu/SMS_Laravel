<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NepalProvince extends Model
{
    protected $fillable = ['name', 'name_ne', 'code'];

    public function districts()
    {
        return $this->hasMany(NepalDistrict::class, 'province_id');
    }
}

