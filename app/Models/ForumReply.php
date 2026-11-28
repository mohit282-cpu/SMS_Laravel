<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class ForumReply extends Model
{
    protected $fillable = [
        'forum_thread_id', 'user_id', 'body', 'is_solution',
    ];

    protected $casts = [
        'is_solution' => 'boolean',
    ];

    public function thread()
    {
        return $this->belongsTo(ForumThread::class, 'forum_thread_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
