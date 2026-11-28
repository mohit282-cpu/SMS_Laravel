<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class OnlineClass extends Model
{
    protected $fillable = [
        'teacher_id', 'my_class_id', 'section_id', 'subject_id',
        'title', 'platform', 'meeting_url', 'meeting_code', 'meeting_passcode',
        'scheduled_date_ad', 'scheduled_date_bs', 'start_time', 'duration_minutes',
        'status', 'description',
    ];

    protected $casts = [
        'scheduled_date_ad' => 'date',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function my_class()
    {
        return $this->belongsTo(MyClass::class, 'my_class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
