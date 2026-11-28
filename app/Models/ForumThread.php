<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class ForumThread extends Model
{
    protected $fillable = [
        'user_id', 'my_class_id', 'subject_id', 'title', 'body', 'is_pinned', 'is_locked',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function my_class()
    {
        return $this->belongsTo(MyClass::class, 'my_class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function replies()
    {
        return $this->hasMany(ForumReply::class, 'forum_thread_id')->orderBy('created_at', 'asc');
    }
}
