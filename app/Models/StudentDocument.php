<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class StudentDocument extends Model
{
    protected $fillable = [
        'student_record_id', 'document_type', 'title', 'file_path',
        'original_name', 'uploaded_by', 'remarks',
    ];

    public function studentRecord()
    {
        return $this->belongsTo(StudentRecord::class, 'student_record_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
