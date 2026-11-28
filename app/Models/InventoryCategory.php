<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryCategory extends Model
{
    protected $fillable = ['name', 'description'];

    public function items()
    {
        return $this->hasMany(InventoryItem::class, 'inventory_category_id');
    }
}
