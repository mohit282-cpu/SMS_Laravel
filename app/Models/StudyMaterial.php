<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudyMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'file_path',
        'file_name',
        'file_size',
        'file_type',
        'my_class_id',
        'section_id',
        'subject_id',
        'teacher_id',
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

    public function scopeVisibleTo($query, $user)
    {
        if (!$user) {
            return $query->whereRaw('0 = 1');
        }

        if (in_array($user->user_type, ['super_admin', 'admin'])) {
            return $query;
        }

        if ($user->user_type === 'teacher') {
            $assignedSectionIds = Section::where('teacher_id', $user->id)->pluck('id')->toArray();
            $assignedClassIds = Section::where('teacher_id', $user->id)->pluck('my_class_id')->toArray();
            $taughtSubjectIds = Subject::where('teacher_id', $user->id)->pluck('id')->toArray();

            return $query->where(function ($q) use ($user, $assignedSectionIds, $assignedClassIds, $taughtSubjectIds) {
                // 1. Materials uploaded by this teacher
                $q->where('teacher_id', $user->id);

                // 2. Section-specific materials: must match teacher's assigned section
                if (!empty($assignedSectionIds)) {
                    $q->orWhere(function ($secQ) use ($assignedSectionIds) {
                        $secQ->whereNotNull('section_id')
                             ->whereIn('section_id', $assignedSectionIds);
                    });
                }

                // 3. Class-wide materials (section_id IS NULL):
                // Permitted if teacher teaches a section in that class or teaches the subject
                $q->orWhere(function ($classQ) use ($assignedClassIds, $taughtSubjectIds) {
                    $classQ->whereNull('section_id')
                           ->where(function ($subQ) use ($assignedClassIds, $taughtSubjectIds) {
                               if (!empty($assignedClassIds)) {
                                   $subQ->whereIn('my_class_id', $assignedClassIds);
                               }
                               if (!empty($taughtSubjectIds)) {
                                   $subQ->orWhereIn('subject_id', $taughtSubjectIds);
                               }
                           });
                });
            });
        }

        if ($user->user_type === 'student') {
            $studentRecord = StudentRecord::where('user_id', $user->id)->first();
            if (!$studentRecord) {
                return $query->whereRaw('0 = 1');
            }

            return $query->where('my_class_id', $studentRecord->my_class_id)
                ->where(function ($q) use ($studentRecord) {
                    $q->whereNull('section_id')
                      ->orWhere('section_id', $studentRecord->section_id);
                });
        }

        if ($user->user_type === 'parent') {
            $children = StudentRecord::where('my_parent_id', $user->id)->get();
            if ($children->isEmpty()) {
                return $query->whereRaw('0 = 1');
            }

            return $query->where(function ($q) use ($children) {
                foreach ($children as $child) {
                    $q->orWhere(function ($sub) use ($child) {
                        $sub->where('my_class_id', $child->my_class_id)
                            ->where(function ($s) use ($child) {
                                $s->whereNull('section_id')
                                  ->orWhere('section_id', $child->section_id);
                            });
                    });
                }
            });
        }

        return $query->whereRaw('0 = 1');
    }
}
