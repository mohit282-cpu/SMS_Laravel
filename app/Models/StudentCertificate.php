<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentCertificate extends Model
{
    protected $fillable = [
        'student_record_id',
        'certificate_type',
        'certificate_number',
        'verification_code',
        'issued_date_bs',
        'issued_date_ad',
        'issued_by',
        'remarks',
    ];

    protected $casts = [
        'issued_date_ad' => 'date',
    ];

    public function studentRecord()
    {
        return $this->belongsTo(StudentRecord::class, 'student_record_id');
    }
}

