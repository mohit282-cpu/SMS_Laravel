<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class StaffPayroll extends Model
{
    protected $fillable = [
        'user_id', 'fiscal_year_id', 'salary_month_bs', 'basic_salary',
        'allowances', 'deductions', 'provident_fund', 'tax_amount', 'net_salary',
        'payment_status', 'paid_date_ad', 'paid_date_bs', 'payment_method',
        'remarks', 'processed_by',
    ];

    protected $casts = [
        'paid_date_ad' => 'date',
    ];

    public function staff()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class, 'fiscal_year_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
