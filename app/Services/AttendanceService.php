<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\AttendanceAuditTrail;
use App\Models\Holiday;
use App\Models\Section;
use App\Models\StaffAttendance;
use App\Models\StudentAttendance;
use App\Models\StudentRecord;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    protected $bsService;

    public function __construct(BikramSambatService $bsService)
    {
        $this->bsService = $bsService;
    }

    /**
     * Convert BS date to AD and inspect calendar for holiday / Saturday warnings.
     *
     * @param string $bsDate YYYY-MM-DD
     * @return array
     */
    public function inspectAttendanceDate($bsDate)
    {
        $adConv = $this->bsService->bsToAd($bsDate);
        $adDate = $adConv['ad_date'];
        $carbon = Carbon::parse($adDate);

        // Check configured school holidays
        $holiday = Holiday::where('start_date', '<=', $adDate)
            ->where('end_date', '>=', $adDate)
            ->first();

        // Check if Saturday (dayOfWeek = 6 in Carbon where 0=Sunday, 6=Saturday)
        $isSaturday = ($carbon->dayOfWeek === Carbon::SATURDAY);

        $warning = null;
        $isSchoolClosed = false;
        if ($holiday) {
            if ($holiday->type === 'custom_working_day' || !$holiday->is_closure) {
                $warning = __('Approved Special Working Day: :title.', ['title' => $holiday->title]);
                $isSchoolClosed = false;
            } else {
                $isSchoolClosed = true;
                $warning = __('Warning: This date is a configured school holiday: :title (:type).', [
                    'title' => $holiday->title,
                    'type' => ucfirst(str_replace('_', ' ', $holiday->type))
                ]);
            }
        } elseif ($isSaturday) {
            $warning = __('Note: Saturday is the standard weekly non-working day in Nepal. Confirm if this is an approved special working day.');
        }

        return [
            'bs_date' => $bsDate,
            'ad_date' => $adDate,
            'carbon' => $carbon,
            'is_holiday' => $isSchoolClosed,
            'holiday' => $holiday,
            'is_saturday' => $isSaturday,
            'warning' => $warning,
        ];
    }

    /**
     * Check if a user is authorized to manage attendance for a class & section.
     *
     * @param User $user
     * @param int $classId
     * @param int|null $sectionId
     * @return bool
     */
    public function canUserManageAttendance(User $user, $classId, $sectionId = null)
    {
        if (in_array($user->user_type, ['super_admin', 'admin'])) {
            return true;
        }

        if ($user->user_type === 'teacher') {
            if ($sectionId) {
                return Section::where('id', $sectionId)
                    ->where('my_class_id', $classId)
                    ->where('teacher_id', $user->id)
                    ->exists();
            }

            // If no specific section provided, check if teacher is assigned to any section in this class
            return Section::where('my_class_id', $classId)
                ->where('teacher_id', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Check if a user can view student attendance records (privacy protection).
     *
     * @param User $user
     * @param int $studentRecordId
     * @return bool
     */
    public function canUserViewStudentAttendance(User $user, $studentRecordId)
    {
        if (in_array($user->user_type, ['super_admin', 'admin'])) {
            return true;
        }

        $sr = StudentRecord::find($studentRecordId);
        if (!$sr) {
            return false;
        }

        if ($user->user_type === 'teacher') {
            return Section::where('id', $sr->section_id)
                ->where('teacher_id', $user->id)
                ->exists();
        }

        if ($user->user_type === 'student') {
            return ($sr->user_id === $user->id);
        }

        if ($user->user_type === 'parent') {
            return ($sr->my_parent_id === $user->id);
        }

        return false;
    }

    /**
     * Save or update bulk student attendance with audit trail and transaction safety.
     *
     * @param array $data
     * @param User $actor
     * @param string|null $ipAddress
     * @return array [count_created, count_updated]
     */
    public function recordStudentAttendance(array $data, User $actor, $ipAddress = null)
    {
        $classId = $data['my_class_id'];
        $sectionId = $data['section_id'] ?? null;
        $bsDate = $data['attendance_date_bs'];
        $session = $data['session'] ?? null;
        $academicYearId = $data['academic_year_id'] ?? null;

        // Verify actor permission server-side
        if (!$this->canUserManageAttendance($actor, $classId, $sectionId)) {
            abort(403, __('Unauthorized: You are not authorized to manage attendance for this class or section.'));
        }

        $dateInfo = $this->inspectAttendanceDate($bsDate);
        $adDate = $dateInfo['ad_date'];

        $studentsData = $data['students'] ?? [];
        if (empty($studentsData)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'students' => [__('No student attendance records were submitted.')]
            ]);
        }

        $created = 0;
        $updated = 0;

        $studentIds = array_keys($studentsData);
        $studentRecords = StudentRecord::whereIn('id', $studentIds)->get()->keyBy('id');
        $existingAttendances = StudentAttendance::whereIn('student_record_id', $studentIds)
            ->where('attendance_date', $adDate)
            ->get()
            ->keyBy('student_record_id');

        $teacherAssignedSectionIds = ($actor->user_type === 'teacher')
            ? Section::where('teacher_id', $actor->id)->pluck('id')->map(fn($id) => (int)$id)->all()
            : [];

        DB::transaction(function () use (
            $studentsData, $studentRecords, $existingAttendances, $teacherAssignedSectionIds,
            $classId, $sectionId, $academicYearId, $session,
            $adDate, $bsDate, $actor, $ipAddress, &$created, &$updated
        ) {
            foreach ($studentsData as $studentId => $row) {
                // Reject invalid status with validation exception; never silently convert
                if (!isset($row['status']) || !in_array($row['status'], ['present', 'absent', 'late', 'leave', 'half_day'], true)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "students.{$studentId}.status" => [__('Invalid attendance status provided. Allowed: present, absent, late, leave, half_day.')]
                    ]);
                }
                $status = $row['status'];
                $remarks = !empty($row['remarks']) ? trim($row['remarks']) : null;

                $sr = $studentRecords->get($studentId);
                if (!$sr) {
                    abort(404, __('Student record not found.'));
                }

                // Verify student belongs to target class and section
                if ((int)$sr->my_class_id !== (int)$classId) {
                    abort(403, __('Unauthorized: Student does not belong to the selected class.'));
                }

                if ($sectionId && (int)$sr->section_id !== (int)$sectionId) {
                    abort(403, __('Unauthorized: Student does not belong to the selected section.'));
                }

                // If teacher, verify teacher is assigned to this student's section
                if ($actor->user_type === 'teacher') {
                    if (!in_array((int)$sr->section_id, $teacherAssignedSectionIds, true)) {
                        abort(403, __('Unauthorized: You are not assigned to record attendance for this student.'));
                    }
                }

                $existing = $existingAttendances->get($studentId);

                if ($existing) {
                    $hasChanged = ($existing->status !== $status || $existing->remarks !== $remarks);

                    if ($hasChanged) {
                        AttendanceAuditTrail::create([
                            'attendance_type' => 'student',
                            'attendance_id' => $existing->id,
                            'user_id' => $sr->user_id,
                            'attendance_date' => $adDate,
                            'attendance_date_bs' => $bsDate,
                            'old_status' => $existing->status,
                            'new_status' => $status,
                            'old_remarks' => $existing->remarks,
                            'new_remarks' => $remarks,
                            'action' => 'update',
                            'modified_by' => $actor->id,
                            'ip_address' => $ipAddress,
                        ]);

                        $existing->update([
                            'status' => $status,
                            'remarks' => $remarks,
                            'updated_by' => $actor->id,
                        ]);

                        $updated++;
                    }
                } else {
                    $record = StudentAttendance::create([
                        'student_record_id' => $studentId,
                        'user_id' => $sr->user_id,
                        'my_class_id' => $classId,
                        'section_id' => $sr->section_id,
                        'academic_year_id' => $academicYearId,
                        'session' => $session,
                        'attendance_date' => $adDate,
                        'attendance_date_bs' => $bsDate,
                        'status' => $status,
                        'remarks' => $remarks,
                        'recorded_by' => $actor->id,
                    ]);

                    AttendanceAuditTrail::create([
                        'attendance_type' => 'student',
                        'attendance_id' => $record->id,
                        'user_id' => $sr->user_id,
                        'attendance_date' => $adDate,
                        'attendance_date_bs' => $bsDate,
                        'old_status' => null,
                        'new_status' => $status,
                        'old_remarks' => null,
                        'new_remarks' => $remarks,
                        'action' => 'create',
                        'modified_by' => $actor->id,
                        'ip_address' => $ipAddress,
                    ]);

                    $created++;
                }
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'date_info' => $dateInfo,
        ];
    }

    /**
     * Check if attendance records for a class & section on a given date were modified after a given client timestamp.
     *
     * @param int $classId
     * @param string $adDate
     * @param array $studentIds
     * @param \Carbon\Carbon $clientTime
     * @return StudentAttendance|null Returns conflicting server record if conflict exists
     */
    public function checkAttendanceConflict($classId, $adDate, array $studentIds, Carbon $clientTime)
    {
        $latest = StudentAttendance::where('my_class_id', $classId)
            ->where('attendance_date', $adDate)
            ->whereIn('student_record_id', $studentIds)
            ->with('updater')
            ->latest('updated_at')
            ->first();

        // If the server record was updated more than 2 seconds after the draft was created, flag conflict
        if ($latest && $latest->updated_at && $latest->updated_at->gt($clientTime->copy()->addSeconds(2))) {
            return $latest;
        }

        return null;
    }

    /**
     * Save or update staff attendance with audit trail and duplicate protection.
     *
     * @param array $data
     * @param User $actor
     * @param string|null $ipAddress
     * @return array [created, updated]
     */
    public function recordStaffAttendance(array $data, User $actor, $ipAddress = null)
    {
        $bsDate = $data['attendance_date_bs'];
        $session = $data['session'] ?? null;
        $academicYearId = $data['academic_year_id'] ?? null;

        $dateInfo = $this->inspectAttendanceDate($bsDate);
        $adDate = $dateInfo['ad_date'];

        $staffData = $data['staff'] ?? [];
        $created = 0;
        $updated = 0;

        $userIds = array_keys($staffData);
        $existingStaffAttendances = StaffAttendance::whereIn('user_id', $userIds)
            ->where('attendance_date', $adDate)
            ->get()
            ->keyBy('user_id');

        DB::transaction(function () use (
            $staffData, $existingStaffAttendances, $academicYearId, $session,
            $adDate, $bsDate, $actor, $ipAddress, &$created, &$updated
        ) {
            foreach ($staffData as $userId => $row) {
                if (!isset($row['status']) || !in_array($row['status'], ['present', 'absent', 'late', 'leave', 'half_day'], true)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "staff.{$userId}.status" => [__('Invalid attendance status provided. Allowed: present, absent, late, leave, half_day.')]
                    ]);
                }
                $status = $row['status'];
                $remarks = !empty($row['remarks']) ? trim($row['remarks']) : null;

                $existing = $existingStaffAttendances->get($userId);

                if ($existing) {
                    $hasChanged = ($existing->status !== $status || $existing->remarks !== $remarks);

                    if ($hasChanged) {
                        AttendanceAuditTrail::create([
                            'attendance_type' => 'staff',
                            'attendance_id' => $existing->id,
                            'user_id' => $userId,
                            'attendance_date' => $adDate,
                            'attendance_date_bs' => $bsDate,
                            'old_status' => $existing->status,
                            'new_status' => $status,
                            'old_remarks' => $existing->remarks,
                            'new_remarks' => $remarks,
                            'action' => 'update',
                            'modified_by' => $actor->id,
                            'ip_address' => $ipAddress,
                        ]);

                        $existing->update([
                            'status' => $status,
                            'remarks' => $remarks,
                            'updated_by' => $actor->id,
                        ]);

                        $updated++;
                    }
                } else {
                    $staffUser = User::findOrFail($userId);
                    $staffRecord = $staffUser->staff_record ? $staffUser->staff_record->first() : null;

                    $record = StaffAttendance::create([
                        'user_id' => $userId,
                        'staff_record_id' => $staffRecord ? $staffRecord->id : null,
                        'academic_year_id' => $academicYearId,
                        'session' => $session,
                        'attendance_date' => $adDate,
                        'attendance_date_bs' => $bsDate,
                        'status' => $status,
                        'remarks' => $remarks,
                        'recorded_by' => $actor->id,
                    ]);

                    AttendanceAuditTrail::create([
                        'attendance_type' => 'staff',
                        'attendance_id' => $record->id,
                        'user_id' => $userId,
                        'attendance_date' => $adDate,
                        'attendance_date_bs' => $bsDate,
                        'old_status' => null,
                        'new_status' => $status,
                        'old_remarks' => null,
                        'new_remarks' => $remarks,
                        'action' => 'create',
                        'modified_by' => $actor->id,
                        'ip_address' => $ipAddress,
                    ]);

                    $created++;
                }
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'date_info' => $dateInfo,
        ];
    }

    /**
     * Compute statistics and percentage from attendance records.
     *
     * Rules:
     * - total_working_days = distinct days where attendance was recorded.
     * - present_effective = present + late + (half_day * 0.5)
     * - percentage = (present_effective / total_working_days) * 100
     * - Missing dates are NEVER counted as absent.
     *
     * @param \Illuminate\Support\Collection $records
     * @return array
     */
    public function computeAttendanceStats($records)
    {
        $totalDays = $records->count();
        $present = $records->where('status', 'present')->count();
        $late = $records->where('status', 'late')->count();
        $leave = $records->where('status', 'leave')->count();
        $halfDay = $records->where('status', 'half_day')->count();
        $absent = $records->where('status', 'absent')->count();

        $effectivePresent = $present + $late + ($halfDay * 0.5);
        $percentage = $totalDays > 0 ? round(($effectivePresent / $totalDays) * 100, 2) : 0.0;

        // Excused percentage (where approved leaves are not penalized)
        $netWorkingDays = max(1, $totalDays - $leave);
        $excusedPercentage = $totalDays > 0 ? round(($effectivePresent / $netWorkingDays) * 100, 2) : 0.0;
        if ($excusedPercentage > 100) {
            $excusedPercentage = 100.0;
        }

        return [
            'total_days' => $totalDays,
            'present' => $present,
            'late' => $late,
            'leave' => $leave,
            'half_day' => $halfDay,
            'absent' => $absent,
            'effective_present' => $effectivePresent,
            'percentage' => $percentage,
            'excused_percentage' => $excusedPercentage,
        ];
    }

    /**
     * Generate Monthly Attendance Matrix for a class/section.
     *
     * @param int $classId
     * @param int|null $sectionId
     * @param int $yearBs
     * @param int $monthBs (1..12)
     * @param array|null $allowedSectionIds
     * @return array
     */
    public function getMonthlyClassAttendanceMatrix($classId, $sectionId, $yearBs, $monthBs, $allowedSectionIds = null)
    {
        // 1. Get days in this BS month
        $calendar = BikramSambatService::BS_CALENDAR_DATA;
        $daysInMonth = (isset($calendar[$yearBs]) && isset($calendar[$yearBs][$monthBs - 1]))
            ? $calendar[$yearBs][$monthBs - 1]
            : 30;

        // 2. Build dates list with weekday and holiday status
        $dates = [];
        $adDates = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $bsDateStr = sprintf('%04d-%02d-%02d', $yearBs, $monthBs, $d);
            try {
                $info = $this->inspectAttendanceDate($bsDateStr);
                $dates[$d] = [
                    'day' => $d,
                    'bs_date' => $bsDateStr,
                    'ad_date' => $info['ad_date'],
                    'is_holiday' => $info['is_holiday'],
                    'holiday_title' => $info['holiday'] ? $info['holiday']->title : null,
                    'is_saturday' => $info['is_saturday'],
                ];
                $adDates[] = $info['ad_date'];
            } catch (\Exception $e) {
                // Fallback for edge cases
                $dates[$d] = [
                    'day' => $d,
                    'bs_date' => $bsDateStr,
                    'ad_date' => null,
                    'is_holiday' => false,
                    'holiday_title' => null,
                    'is_saturday' => false,
                ];
            }
        }

        // 3. Load students
        $query = StudentRecord::where('my_class_id', $classId);
        if ($sectionId) {
            $query->where('section_id', $sectionId);
        } elseif (!empty($allowedSectionIds)) {
            $query->whereIn('section_id', $allowedSectionIds);
        }
        $students = $query->with('user')->get();

        // 4. Load all attendance records in this date range
        $attendances = StudentAttendance::where('my_class_id', $classId)
            ->when($sectionId, function ($q) use ($sectionId) {
                return $q->where('section_id', $sectionId);
            })
            ->when(!$sectionId && !empty($allowedSectionIds), function ($q) use ($allowedSectionIds) {
                return $q->whereIn('section_id', $allowedSectionIds);
            })
            ->whereIn('attendance_date', $adDates)
            ->get()
            ->groupBy('student_record_id');

        // 5. Build grid
        $matrix = [];
        foreach ($students as $sr) {
            $userAttendances = $attendances->get($sr->id, collect());
            $attendanceByBs = $userAttendances->keyBy('attendance_date_bs');

            $studentRow = [
                'student_record' => $sr,
                'name' => $sr->user ? $sr->user->name : 'N/A',
                'adm_no' => $sr->adm_no,
                'days' => [],
            ];

            foreach ($dates as $d => $dInfo) {
                $rec = $attendanceByBs->get($dInfo['bs_date']);
                $studentRow['days'][$d] = $rec ? $rec->status : null;
            }

            $studentRow['stats'] = $this->computeAttendanceStats($userAttendances);
            $matrix[] = $studentRow;
        }

        return [
            'year_bs' => $yearBs,
            'month_bs' => $monthBs,
            'month_name' => BikramSambatService::MONTHS_NE[$monthBs] ?? '',
            'month_name_en' => BikramSambatService::MONTHS_EN[$monthBs] ?? '',
            'days_in_month' => $daysInMonth,
            'dates' => $dates,
            'matrix' => $matrix,
        ];
    }
}
