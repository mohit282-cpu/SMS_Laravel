<?php

namespace App\Models;

use App\Services\NepaliDateService;
use App\User;
use Eloquent;

class PaymentRecord extends Eloquent
{
    protected $fillable = [
        'student_id',
        'payment_id',
        'amt_paid',
        'year',
        'paid',
        'balance',
        'ref_no',
        'bs_year',
        'bs_month',
        'due_date',
        'due_date_bs',
        'discount_amt',
        'waiver_reason',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function receipt()
    {
        return $this->hasMany(Receipt::class, 'pr_id');
    }

    public function scopeForBsPeriod($query, $year, $month)
    {
        return $query->where('bs_year', $year)->where('bs_month', $month);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('paid', 0);
    }

    public function scopePaid($query)
    {
        return $query->where('paid', 1);
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
