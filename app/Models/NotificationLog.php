<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $fillable = [
        'sender_id', 'recipient_user_id', 'channel', 'category',
        'title', 'message', 'status', 'target_role',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }
}
