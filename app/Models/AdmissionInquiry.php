<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionInquiry extends Model
{
    protected $table = 'admission_inquiries';

    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'academic_level',
        'message',
        'status',
        'admin_notes',
    ];
}
