<?php

namespace App\Services;

use App\Models\Event;
use App\Models\PushSubscription;
use App\Models\Section;
use App\Models\StudentRecord;
use App\Models\Subject;
use App\User;

class EventNotificationService
{
    /**
     * Resolve eligible recipient user IDs for an event based on visibility and audience rules.
     *
     * @param Event $event
     * @return array<int>
     */
    public function getEligibleUserIds(Event $event): array
    {
        // 1. Unpublished / draft events must NEVER dispatch notifications
        if (!$event->is_published) {
            return [];
        }

        $audience = $event->target_audience;

        // Base administrators always have visibility
        $adminIds = User::whereIn('user_type', ['super_admin', 'admin'])->pluck('id')->toArray();

        switch ($audience) {
            case 'all':
                // Entire school: all users
                $allIds = User::pluck('id')->toArray();
                return array_values(array_unique($allIds));

            case 'teachers':
                // Staff only: teachers, admins, accountants, librarians
                $staffIds = User::whereIn('user_type', ['super_admin', 'admin', 'teacher', 'accountant', 'librarian'])
                    ->pluck('id')
                    ->toArray();
                return array_values(array_unique($staffIds));

            case 'students':
                // Students and administrators only
                $studentIds = User::where('user_type', 'student')->pluck('id')->toArray();
                return array_values(array_unique(array_merge($adminIds, $studentIds)));

            case 'parents':
                // Parents and administrators only
                $parentIds = User::where('user_type', 'parent')->pluck('id')->toArray();
                return array_values(array_unique(array_merge($adminIds, $parentIds)));

            case 'classes':
            case 'class_section':
                // Targeted to specific class and optional section
                if (!$event->my_class_id) {
                    return array_values(array_unique($adminIds));
                }

                $studentQuery = StudentRecord::where('my_class_id', $event->my_class_id);
                if ($event->section_id) {
                    $studentQuery->where('section_id', $event->section_id);
                }

                $studentRecords = $studentQuery->get(['user_id', 'my_parent_id']);
                $studentUserIds = $studentRecords->pluck('user_id')->filter()->toArray();
                $parentUserIds = $studentRecords->pluck('my_parent_id')->filter()->toArray();

                // Teachers assigned to this class or section
                $sectionTeacherIds = Section::where('my_class_id', $event->my_class_id)
                    ->when($event->section_id, function ($q) use ($event) {
                        $q->where('id', $event->section_id);
                    })
                    ->pluck('teacher_id')
                    ->filter()
                    ->toArray();

                $subjectTeacherIds = Subject::where('my_class_id', $event->my_class_id)
                    ->pluck('teacher_id')
                    ->filter()
                    ->toArray();

                $teacherIds = array_unique(array_merge($sectionTeacherIds, $subjectTeacherIds));

                $eligible = array_merge($adminIds, $studentUserIds, $parentUserIds, $teacherIds);
                return array_values(array_unique($eligible));

            default:
                return array_values(array_unique($adminIds));
        }
    }

    /**
     * Resolve subscriber user IDs who are eligible to receive the event notification.
     *
     * @param Event $event
     * @return array<int>
     */
    public function getSubscribedRecipientIds(Event $event): array
    {
        $eligibleUserIds = $this->getEligibleUserIds($event);

        if (empty($eligibleUserIds)) {
            return [];
        }

        return PushSubscription::whereIn('user_id', $eligibleUserIds)
            ->pluck('user_id')
            ->unique()
            ->values()
            ->toArray();
    }
}
