<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceAuditTrail extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_type',
        'attendance_id',
        'user_id',
        'attendance_date',
        'attendance_date_bs',
        'old_status',
        'new_status',
        'old_remarks',
        'new_remarks',
        'action',
        'modified_by',
        'ip_address',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function modifier()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }
}
