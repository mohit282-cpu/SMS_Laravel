<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'inventory_item_id', 'transaction_type', 'quantity', 'unit_cost',
        'vendor_or_recipient', 'transaction_date_ad', 'transaction_date_bs',
        'reference_no', 'remarks', 'recorded_by',
    ];

    protected $casts = [
        'transaction_date_ad' => 'date',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
