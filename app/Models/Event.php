<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'title_ne',
        'description',
        'category',
        'start_date',
        'end_date',
        'start_date_bs',
        'end_date_bs',
        'start_time',
        'end_time',
        'is_all_day',
        'is_published',
        'is_closure',
        'academic_year_id',
        'session',
        'target_audience',
        'my_class_id',
        'section_id',
        'location',
        'instructions',
        'exam_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_all_day' => 'boolean',
        'is_published' => 'boolean',
        'is_closure' => 'boolean',
    ];

    public const CATEGORIES = [
        'school_event' => [
            'name_en' => 'School Event',
            'name_ne' => 'विद्यालय कार्यक्रम',
            'color' => '#1e88e5',
            'badge' => 'badge-primary',
        ],
        'public_holiday' => [
            'name_en' => 'Public Holiday',
            'name_ne' => 'सार्वजनिक बिदा',
            'color' => '#e53935',
            'badge' => 'badge-danger',
        ],
        'school_holiday' => [
            'name_en' => 'School Holiday',
            'name_ne' => 'विद्यालय बिदा',
            'color' => '#e53935',
            'badge' => 'badge-danger',
        ],
        'examination' => [
            'name_en' => 'Examination',
            'name_ne' => 'परीक्षा',
            'color' => '#FF5700',
            'badge' => 'badge-warning',
        ],
        'assignment_deadline' => [
            'name_en' => 'Assignment Deadline',
            'name_ne' => 'गृहकार्य समयसीमा',
            'color' => '#f59e0b',
            'badge' => 'badge-warning',
        ],
        'parent_teacher_meeting' => [
            'name_en' => 'Parent-Teacher Meeting',
            'name_ne' => 'अभिभावक-शिक्षक बैठक',
            'color' => '#6366f1',
            'badge' => 'badge-info',
        ],
        'admission_deadline' => [
            'name_en' => 'Admission Deadline',
            'name_ne' => 'भर्ना समयसीमा',
            'color' => '#00897b',
            'badge' => 'badge-success',
        ],
        'sports_extracurricular' => [
            'name_en' => 'Sports & Extracurricular',
            'name_ne' => 'खेलकुद तथा अतिरिक्त',
            'color' => '#43a047',
            'badge' => 'badge-success',
        ],
        'staff_meeting' => [
            'name_en' => 'Staff Meeting',
            'name_ne' => 'कर्मचारी बैठक',
            'color' => '#546e7a',
            'badge' => 'badge-secondary',
        ],
        'other' => [
            'name_en' => 'Other',
            'name_ne' => 'अन्य',
            'color' => '#757575',
            'badge' => 'badge-secondary',
        ],
    ];

    public const AUDIENCES = [
        'all' => 'Entire School',
        'teachers' => 'Staff / Teachers Only',
        'students' => 'All Students',
        'parents' => 'All Parents',
        'classes' => 'Class / Section Targeted',
        'class_section' => 'Class / Section Targeted',
    ];

    public function my_class()
    {
        return $this->belongsTo(MyClass::class, 'my_class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function academic_year()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getCategoryNameAttribute()
    {
        $cat = self::CATEGORIES[$this->category] ?? null;
        if (!$cat) {
            return ucfirst(str_replace('_', ' ', $this->category));
        }

        return (app()->getLocale() === 'ne') ? $cat['name_ne'] : $cat['name_en'];
    }

    public function getCategoryColorAttribute()
    {
        return self::CATEGORIES[$this->category]['color'] ?? '#1e88e5';
    }

    public function getCategoryBadgeAttribute()
    {
        return self::CATEGORIES[$this->category]['badge'] ?? 'badge-primary';
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeForSession($query, $session)
    {
        if ($session) {
            return $query->where('session', $session);
        }
        return $query;
    }

    /**
     * Scope events visible to a specific user based on roles, audience targeting, and section enrollment.
     */
    public function scopeVisibleTo($query, ?User $user = null)
    {
        if (!$user) {
            // Guest sees only explicitly public, published school-wide events
            return $query->where('is_published', true)
                ->where('target_audience', 'all')
                ->where(function ($q) {
                    $q->whereNull('exam_id')
                      ->orWhereHas('exam', function ($examQ) {
                          $examQ->where('status', 'published');
                      });
                });
        }

        // Super Admin and Admin see all events (published and draft)
        if (in_array($user->user_type, ['super_admin', 'admin'], true)) {
            return $query;
        }

        // Non-admin roles see only published events
        $query->where('is_published', true);

        // Events tied to examinations are only visible to non-admins if the exam is published
        $query->where(function ($q) {
            $q->whereNull('exam_id')
              ->orWhereHas('exam', function ($examQ) {
                  $examQ->where('status', 'published');
              });
        });

        if ($user->user_type === 'teacher') {
            $assignedSectionIds = Section::where('teacher_id', $user->id)->pluck('id')->toArray();
            $assignedClassIds = array_unique(array_merge(
                Section::where('teacher_id', $user->id)->pluck('my_class_id')->toArray(),
                Subject::where('teacher_id', $user->id)->pluck('my_class_id')->toArray()
            ));

            return $query->where(function ($q) use ($assignedClassIds, $assignedSectionIds) {
                $q->whereIn('target_audience', ['all', 'teachers'])
                  ->orWhere(function ($sub) use ($assignedClassIds, $assignedSectionIds) {
                      $sub->whereIn('target_audience', ['classes', 'class_section'])
                          ->where(function ($matchQ) use ($assignedClassIds, $assignedSectionIds) {
                              $hasMatch = false;

                              // Class-wide events (section_id is null): teacher must teach subject or section in this class
                              if (!empty($assignedClassIds)) {
                                  $hasMatch = true;
                                  $matchQ->where(function ($classQ) use ($assignedClassIds) {
                                      $classQ->whereIn('my_class_id', $assignedClassIds)
                                             ->whereNull('section_id');
                                  });
                              }

                              // Section-specific events: teacher must be assigned to that specific section
                              if (!empty($assignedSectionIds)) {
                                  if ($hasMatch) {
                                      $matchQ->orWhere(function ($secQ) use ($assignedSectionIds) {
                                          $secQ->whereIn('section_id', $assignedSectionIds);
                                      });
                                  } else {
                                      $hasMatch = true;
                                      $matchQ->where(function ($secQ) use ($assignedSectionIds) {
                                          $secQ->whereIn('section_id', $assignedSectionIds);
                                      });
                                  }
                              }

                              if (!$hasMatch) {
                                  $matchQ->whereRaw('0 = 1');
                              }
                          });
                  });
            });
        }

        if ($user->user_type === 'student') {
            $sr = StudentRecord::where('user_id', $user->id)->first();
            $myClassId = $sr ? $sr->my_class_id : null;
            $mySectionId = $sr ? $sr->section_id : null;

            return $query->where(function ($q) use ($myClassId, $mySectionId) {
                $q->whereIn('target_audience', ['all', 'students']);

                if ($myClassId) {
                    $q->orWhere(function ($sub) use ($myClassId, $mySectionId) {
                        $sub->whereIn('target_audience', ['classes', 'class_section'])
                            ->where('my_class_id', $myClassId)
                            ->where(function ($s) use ($mySectionId) {
                                $s->whereNull('section_id');
                                if ($mySectionId) {
                                    $s->orWhere('section_id', $mySectionId);
                                }
                            });
                    });
                }
            });
        }

        if ($user->user_type === 'parent') {
            $children = StudentRecord::where('my_parent_id', $user->id)
                ->whereNotNull('my_class_id')
                ->get(['my_class_id', 'section_id']);

            return $query->where(function ($q) use ($children) {
                $q->whereIn('target_audience', ['all', 'parents']);

                if ($children->isNotEmpty()) {
                    $q->orWhere(function ($sub) use ($children) {
                        $sub->whereIn('target_audience', ['classes', 'class_section'])
                            ->where(function ($matchQuery) use ($children) {
                                foreach ($children as $child) {
                                    $matchQuery->orWhere(function ($cQ) use ($child) {
                                        $cQ->where('my_class_id', $child->my_class_id)
                                           ->where(function ($sQ) use ($child) {
                                               $sQ->whereNull('section_id');
                                               if ($child->section_id) {
                                                   $sQ->orWhere('section_id', $child->section_id);
                                               }
                                           });
                                    });
                                }
                            });
                    });
                }
            });
        }

        // Other staff (accountant, librarian)
        return $query->whereIn('target_audience', ['all', 'teachers']);
    }

    /**
     * Get eligible recipient user IDs for notifications based on audience targeting.
     *
     * @return array<int>
     */
    public function getEligibleRecipientUserIds(): array
    {
        return app(\App\Services\EventNotificationService::class)->getEligibleUserIds($this);
    }
}
