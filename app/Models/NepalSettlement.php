<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NepalSettlement extends Model
{
    protected $fillable = ['local_level_id', 'ward_number', 'name', 'name_ne', 'settlement_type', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function localLevel()
    {
        return $this->belongsTo(NepalLocalLevel::class, 'local_level_id');
    }
}
