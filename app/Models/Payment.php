<?php

namespace App\Models;

use App\Services\NepaliDateService;
use Eloquent;

class Payment extends Eloquent
{
    protected $fillable = [
        'title',
        'amount',
        'my_class_id',
        'description',
        'year',
        'ref_no',
        'method',
        'period_type',
        'fee_category',
        'bs_year',
        'bs_month',
        'due_date',
        'due_date_bs',
    ];

    public function my_class()
    {
        return $this->belongsTo(MyClass::class);
    }

    public function payment_records()
    {
        return $this->hasMany(PaymentRecord::class, 'payment_id');
    }

    public function scopeMonthly($query)
    {
        return $query->where('period_type', 'monthly');
    }

    public function scopeForBsPeriod($query, $year, $month)
    {
        return $query->where('bs_year', $year)->where('bs_month', $month);
    }

    public function getBsMonthNameAttribute(): string
    {
        if (empty($this->bs_month)) {
            return '';
        }

        return NepaliDateService::NEPALI_MONTHS[$this->bs_month]['en'] ?? '';
    }

    public function getBsMonthNameNeAttribute(): string
    {
        if (empty($this->bs_month)) {
            return '';
        }

        return NepaliDateService::NEPALI_MONTHS[$this->bs_month]['ne'] ?? '';
    }
}
