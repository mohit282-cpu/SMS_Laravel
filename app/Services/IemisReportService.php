<?php

namespace App\Services;

use App\Models\MyClass;
use App\Models\SchoolProfile;
use App\Models\StudentRecord;
use App\User;
use Illuminate\Support\Facades\DB;

class IemisReportService
{
    /**
     * Compile school-level statistical summary structured for Nepal EMIS/IEMIS readiness.
     * Note: Generates school-internal export data; does not claim live government API submission.
     */
    public function generateSummary(?string $session = null): array
    {
        $profile = SchoolProfile::current();

        $studentsQuery = StudentRecord::query()
            ->join('users', 'student_records.user_id', '=', 'users.id')
            ->where('student_records.grad', 0);

        if ($session !== null) {
            $studentsQuery->where('student_records.session', $session);
        }

        $totalStudents = (clone $studentsQuery)->count();
        $genderBreakdown = (clone $studentsQuery)
            ->select('users.gender', DB::raw('COUNT(*) as total'))
            ->groupBy('users.gender')
            ->pluck('total', 'gender')
            ->toArray();

        $classBreakdown = (clone $studentsQuery)
            ->join('my_classes', 'student_records.my_class_id', '=', 'my_classes.id')
            ->select('my_classes.name as class_name', DB::raw('COUNT(*) as total'))
            ->groupBy('my_classes.name')
            ->orderBy('my_classes.name')
            ->pluck('total', 'class_name')
            ->toArray();

        $scholarshipCount = (clone $studentsQuery)
            ->whereNotNull('student_records.scholarship_category')
            ->where('student_records.scholarship_category', '!=', '')
            ->count();

        $teacherCount = User::where('user_type', 'teacher')->count();

        return [
            'generated_at' => now()->toIso8601String(),
            'session' => $session,
            'school' => $profile ? [
                'name' => $profile->name,
                'name_ne' => $profile->name_ne,
                'emis_code' => $profile->emis_code,
                'school_type' => $profile->school_type,
                'boarding_type' => $profile->boarding_type,
                'pan_number' => $profile->pan_number,
            ] : null,
            'enrollment' => [
                'total_students' => $totalStudents,
                'gender_distribution' => $genderBreakdown,
                'class_distribution' => $classBreakdown,
                'scholarship_recipients' => $scholarshipCount,
            ],
            'staffing' => [
                'total_teachers' => $teacherCount,
            ],
        ];
    }
}

