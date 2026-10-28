<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntranceResult extends Model
{
    protected $table = 'entrance_results';

    protected $fillable = [
        'batch_year',
        'exam_name',
        'class_name',
        'section_name',
        'symbol_no',
        'roll_no',
        'student_name',
        'attendance',
        'gpa',
        'remarks',
        'marks_json',
    ];

    protected $casts = [
        'gpa' => 'float',
    ];

    /**
     * Decode subject marks JSON into PHP array
     */
    public function getMarksAttribute()
    {
        return json_decode($this->marks_json, true) ?: [];
    }
}
