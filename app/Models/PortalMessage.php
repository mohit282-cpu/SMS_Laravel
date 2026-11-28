<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class PortalMessage extends Model
{
    protected $fillable = [
        'sender_id', 'recipient_id', 'student_record_id', 'subject', 'body', 'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function studentRecord()
    {
        return $this->belongsTo(StudentRecord::class, 'student_record_id');
    }
}
