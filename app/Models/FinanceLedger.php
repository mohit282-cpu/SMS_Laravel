<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceLedger extends Model
{
    protected $fillable = [
        'fiscal_year_id',
        'entry_date_bs',
        'entry_date_ad',
        'entry_type',
        'category',
        'account_head',
        'amount',
        'reference_no',
        'description',
        'recorded_by',
    ];

    protected $casts = [
        'entry_date_ad' => 'date',
    ];

    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class, 'fiscal_year_id');
    }
}

