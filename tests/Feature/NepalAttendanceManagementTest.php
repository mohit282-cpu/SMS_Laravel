<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceAuditTrail;
use App\Models\Holiday;
use App\Models\MyClass;
use App\Models\Section;
use App\Models\StaffAttendance;
use App\Models\StudentAttendance;
use App\Models\StudentRecord;
use App\Services\AttendanceService;
use App\User;
use Tests\TestCase;

class NepalAttendanceManagementTest extends TestCase
{
    protected function getAdmin()
    {
        return User::where('user_type', 'super_admin')->first();
    }

    protected function getTeacher()
    {
        return User::where('user_type', 'teacher')->first();
    }

    protected function getStudent()
    {
        return User::where('user_type', 'student')->first();
    }

    protected function getParent()
    {
        return User::where('user_type', 'parent')->first();
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Clean test attendances and audit trails
        StudentAttendance::whereIn('attendance_date_bs', ['2083-07-08', '2083-07-09'])->delete();
        StaffAttendance::whereIn('attendance_date_bs', ['2083-07-08', '2083-07-09'])->delete();
        AttendanceAuditTrail::whereIn('attendance_date_bs', ['2083-07-08', '2083-07-09'])->delete();
    }

    /**
     * Test admin can view student daily attendance page.
     */
    public function test_admin_can_view_student_attendance_page()
    {
        $admin = $this->getAdmin();
        $response = $this->actingAs($admin)->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee(__('Student Daily Attendance'));
    }

    /**
     * Test recording daily student attendance with BS date and AD conversion.
     */
    public function test_admin_can_record_bulk_student_attendance()
    {
        $admin = $this->getAdmin();
        $class = MyClass::first();
        $students = StudentRecord::where('my_class_id', $class->id)->take(3)->get();

        $this->assertNotEmpty($students, 'There must be students in the class.');

        $studentsData = [];
        foreach ($students as $idx => $s) {
            $status = ($idx === 0) ? 'present' : (($idx === 1) ? 'absent' : 'late');
            $studentsData[$s->id] = [
                'status' => $status,
                'remarks' => ($status === 'late') ? 'Late by 15 mins' : '',
            ];
        }

        $postData = [
            'my_class_id' => $class->id,
            'attendance_date_bs' => '2083-07-08',
            'students' => $studentsData,
        ];

        $response = $this->actingAs($admin)->post(route('attendance.store'), $postData);
        $response->assertSessionHas('flash_success');

        // Verify records in DB
        foreach ($students as $idx => $s) {
            $expectedStatus = ($idx === 0) ? 'present' : (($idx === 1) ? 'absent' : 'late');
            $att = StudentAttendance::where('student_record_id', $s->id)
                ->where('attendance_date_bs', '2083-07-08')
                ->first();

            $this->assertNotNull($att);
            $this->assertEquals($expectedStatus, $att->status);
            $this->assertEquals('2026-10-25', $att->attendance_date->format('Y-m-d')); // 2083-07-08 BS is 2026-10-25 AD
        }

        // Verify audit trail created
        $auditCount = AttendanceAuditTrail::where('attendance_date_bs', '2083-07-08')
            ->where('attendance_type', 'student')
            ->where('action', 'create')
            ->count();
        $this->assertEquals(count($students), $auditCount);
    }

    /**
     * Test duplicate attendance prevention and updating with audit trail.
     */
    public function test_updating_existing_attendance_creates_update_audit_trail()
    {
        $admin = $this->getAdmin();
        $class = MyClass::first();
        $student = StudentRecord::where('my_class_id', $class->id)->first();

        // 1. First record as absent
        $this->actingAs($admin)->post(route('attendance.store'), [
            'my_class_id' => $class->id,
            'attendance_date_bs' => '2083-07-08',
            'students' => [
                $student->id => ['status' => 'absent', 'remarks' => 'Fever'],
            ],
        ]);

        $att = StudentAttendance::where('student_record_id', $student->id)
            ->where('attendance_date_bs', '2083-07-08')
            ->first();
        $this->assertEquals('absent', $att->status);

        // 2. Teacher/Admin corrects to present with doctor note
        $this->actingAs($admin)->post(route('attendance.store'), [
            'my_class_id' => $class->id,
            'attendance_date_bs' => '2083-07-08',
            'students' => [
                $student->id => ['status' => 'leave', 'remarks' => 'Approved medical leave'],
            ],
        ]);

        $att->refresh();
        $this->assertEquals('leave', $att->status);
        $this->assertEquals('Approved medical leave', $att->remarks);

        // Verify audit trail captured old status and new status
        $updateAudit = AttendanceAuditTrail::where('attendance_id', $att->id)
            ->where('action', 'update')
            ->first();

        $this->assertNotNull($updateAudit);
        $this->assertEquals('absent', $updateAudit->old_status);
        $this->assertEquals('leave', $updateAudit->new_status);
        $this->assertEquals($admin->id, $updateAudit->modified_by);
    }

    /**
     * Test assigned class teacher can view their assigned class and section attendance.
     */
    public function test_assigned_teacher_can_view_their_class_attendance()
    {
        $teacher = $this->getTeacher();
        $class = MyClass::first();
        $section = Section::where('my_class_id', $class->id)->first();

        // Assign teacher to this section
        Section::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);
        Section::where('id', $section->id)->update(['teacher_id' => $teacher->id]);

        $response = $this->actingAs($teacher)->get(route('attendance.index', [
            'my_class_id' => $class->id,
            'section_id' => $section->id,
            'attendance_date_bs' => '2083-07-08',
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('can_manage', true);
        $response->assertViewHas('selected_class', $class->id);
        $response->assertViewHas('selected_section', $section->id);
    }

    /**
     * Test assigned class teacher auto-selection when they have exactly 1 assigned section.
     */
    public function test_assigned_teacher_auto_selected_when_single_assignment()
    {
        $teacher = $this->getTeacher();
        $class = MyClass::first();
        $section = Section::where('my_class_id', $class->id)->first();

        // Assign to exactly one section
        Section::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);
        Section::where('id', $section->id)->update(['teacher_id' => $teacher->id]);

        $response = $this->actingAs($teacher)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertViewHas('has_assignment', true);
        $response->assertViewHas('selected_class', $class->id);
        $response->assertViewHas('selected_section', $section->id);
    }

    /**
     * Test assigned class teacher can record attendance for assigned students.
     */
    public function test_assigned_teacher_can_record_attendance_for_assigned_students()
    {
        $teacher = $this->getTeacher();
        $class = MyClass::first();
        $section = Section::where('my_class_id', $class->id)->first();

        Section::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);
        Section::where('id', $section->id)->update(['teacher_id' => $teacher->id]);

        $student = StudentRecord::where('my_class_id', $class->id)
            ->where('section_id', $section->id)
            ->first();

        $this->assertNotNull($student, 'Must have a student in this section.');

        $postData = [
            'my_class_id' => $class->id,
            'section_id' => $section->id,
            'attendance_date_bs' => '2083-07-08',
            'students' => [
                $student->id => ['status' => 'present', 'remarks' => 'Present on time'],
            ],
        ];

        $response = $this->actingAs($teacher)->post(route('attendance.store'), $postData);
        $response->assertSessionHas('flash_success');

        $record = StudentAttendance::where('student_record_id', $student->id)
            ->where('attendance_date_bs', '2083-07-08')
            ->first();

        $this->assertNotNull($record);
        $this->assertEquals('present', $record->status);
        $this->assertEquals($teacher->id, $record->recorded_by);
    }

    /**
     * Test assigned class teacher can update attendance in their assigned class.
     */
    public function test_assigned_teacher_can_update_attendance_in_assigned_class()
    {
        $teacher = $this->getTeacher();
        $class = MyClass::first();
        $section = Section::where('my_class_id', $class->id)->first();

        Section::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);
        Section::where('id', $section->id)->update(['teacher_id' => $teacher->id]);

        $student = StudentRecord::where('my_class_id', $class->id)
            ->where('section_id', $section->id)
            ->first();

        // 1. Initial entry: absent
        $this->actingAs($teacher)->post(route('attendance.store'), [
            'my_class_id' => $class->id,
            'section_id' => $section->id,
            'attendance_date_bs' => '2083-07-08',
            'students' => [
                $student->id => ['status' => 'absent', 'remarks' => 'Not in class'],
            ],
        ]);

        $att = StudentAttendance::where('student_record_id', $student->id)
            ->where('attendance_date_bs', '2083-07-08')
            ->first();
        $this->assertNotNull($att, 'Attendance record must exist after first post');
        $this->assertEquals('absent', $att->status);

        // 2. Teacher updates to late
        $this->actingAs($teacher)->post(route('attendance.store'), [
            'my_class_id' => $class->id,
            'section_id' => $section->id,
            'attendance_date_bs' => '2083-07-08',
            'students' => [
                $student->id => ['status' => 'late', 'remarks' => 'Arrived late after bell'],
            ],
        ]);

        $att->refresh();
        $this->assertEquals('late', $att->status);
        $this->assertEquals('Arrived late after bell', $att->remarks);
        $this->assertEquals($teacher->id, $att->updated_by);

        // Audit trail check
        $audit = AttendanceAuditTrail::where('attendance_id', $att->id)
            ->where('action', 'update')
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals('absent', $audit->old_status);
        $this->assertEquals('late', $audit->new_status);
        $this->assertEquals($teacher->id, $audit->modified_by);
    }

    /**
     * Test teacher cannot access another class by changing request parameters.
     */
    public function test_teacher_cannot_access_another_class_by_changing_request_parameters()
    {
        $teacher = $this->getTeacher();
        $class1 = MyClass::first();
        $class2 = MyClass::where('id', '!=', $class1->id)->first();
        $section1 = Section::where('my_class_id', $class1->id)->first();

        Section::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);
        Section::where('id', $section1->id)->update(['teacher_id' => $teacher->id]);

        // Attempt to access unassigned class in index
        $resIndex = $this->actingAs($teacher)->get(route('attendance.index', [
            'my_class_id' => $class2->id,
        ]));
        $resIndex->assertStatus(403);

        // Attempt to access unassigned class in monthly
        $resMonthly = $this->actingAs($teacher)->get(route('attendance.monthly', [
            'my_class_id' => $class2->id,
        ]));
        $resMonthly->assertStatus(403);
    }

    /**
     * Test teacher cannot submit a student ID from another class or section.
     */
    public function test_teacher_cannot_submit_student_id_from_another_class_or_section()
    {
        $teacher = $this->getTeacher();
        $class1 = MyClass::first();
        $class2 = MyClass::where('id', '!=', $class1->id)->first();
        $section1 = Section::where('my_class_id', $class1->id)->first();

        Section::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);
        Section::where('id', $section1->id)->update(['teacher_id' => $teacher->id]);

        $foreignStudent = StudentRecord::where('my_class_id', $class2->id)->first();

        // Attempt to submit foreign student under assigned class
        $response = $this->actingAs($teacher)->post(route('attendance.store'), [
            'my_class_id' => $class1->id,
            'section_id' => $section1->id,
            'attendance_date_bs' => '2083-07-08',
            'students' => [
                $foreignStudent->id => ['status' => 'present', 'remarks' => 'Intruder'],
            ],
        ]);

        $response->assertStatus(403);

        $exists = StudentAttendance::where('student_record_id', $foreignStudent->id)
            ->where('attendance_date_bs', '2083-07-08')
            ->exists();
        $this->assertFalse($exists);
    }

    /**
     * Test unassigned teacher is denied access.
     */
    public function test_unassigned_teacher_cannot_record_attendance()
    {
        $teacher = $this->getTeacher();
        $class = MyClass::first();
        $student = StudentRecord::where('my_class_id', $class->id)->first();

        // Ensure teacher has no section assignments
        Section::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);

        $response = $this->actingAs($teacher)->post(route('attendance.store'), [
            'my_class_id' => $class->id,
            'attendance_date_bs' => '2083-07-08',
            'students' => [
                $student->id => ['status' => 'present', 'remarks' => ''],
            ],
        ]);

        // Must be rejected with 403 Forbidden
        $response->assertStatus(403);
    }

    /**
     * Test unassigned teacher is denied access and views clear warning notice.
     */
    public function test_unassigned_teacher_is_denied_access()
    {
        $teacher = $this->getTeacher();
        Section::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);

        // Accessing base page shows unassigned message
        $response = $this->actingAs($teacher)->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertViewHas('has_assignment', false);
        $response->assertViewHas('can_manage', false);

        $anyClass = MyClass::first();

        // Attempting to bypass by passing class parameter gives 403 Forbidden
        $resBypass = $this->actingAs($teacher)->get(route('attendance.index', ['my_class_id' => $anyClass->id]));
        $resBypass->assertStatus(403);

        // Attempting to POST attendance gives 403 Forbidden
        $resPost = $this->actingAs($teacher)->post(route('attendance.store'), [
            'my_class_id' => $anyClass->id,
            'attendance_date_bs' => '2083-07-08',
            'students' => [1 => ['status' => 'present']],
        ]);
        $resPost->assertStatus(403);
    }

    /**
     * Test student privacy: student cannot view other students' attendance history.
     */
    public function test_student_cannot_view_other_students_attendance()
    {
        $studentUser = $this->getStudent();
        $myRecord = StudentRecord::where('user_id', $studentUser->id)->first();
        $otherRecord = StudentRecord::where('user_id', '!=', $studentUser->id)->first();

        // Student viewing own attendance -> 200 OK
        if ($myRecord) {
            $resOwn = $this->actingAs($studentUser)->get(route('attendance.student_history', $myRecord->id));
            $resOwn->assertStatus(200);
        }

        // Student attempting to view other student's attendance -> 403 Forbidden
        $resOther = $this->actingAs($studentUser)->get(route('attendance.student_history', $otherRecord->id));
        $resOther->assertStatus(403);
    }

    /**
     * Test parent can only view their own child's attendance history.
     */
    public function test_parent_can_only_view_own_child_attendance()
    {
        $parent = $this->getParent();
        $childRecord = StudentRecord::first();
        $childRecord->update(['my_parent_id' => $parent->id]);

        $otherRecord = StudentRecord::where('id', '!=', $childRecord->id)->first();
        $otherRecord->update(['my_parent_id' => null]);

        // Parent viewing own child -> 200 OK
        $resOwn = $this->actingAs($parent)->get(route('attendance.student_history', $childRecord->id));
        $resOwn->assertStatus(200);

        // Parent viewing other student -> 403 Forbidden
        $resOther = $this->actingAs($parent)->get(route('attendance.student_history', $otherRecord->id));
        $resOther->assertStatus(403);
    }

    /**
     * Test students and parents cannot record attendance.
     */
    public function test_students_and_parents_cannot_record_attendance()
    {
        $student = $this->getStudent();
        $parent = $this->getParent();
        $class = MyClass::first();
        $studentRec = StudentRecord::first();

        $postData = [
            'my_class_id' => $class->id,
            'attendance_date_bs' => '2083-07-08',
            'students' => [
                $studentRec->id => ['status' => 'present'],
            ],
        ];

        // Student attempt
        $resStudent = $this->actingAs($student)->post(route('attendance.store'), $postData);
        $this->assertTrue(in_array($resStudent->status(), [302, 403]));

        // Parent attempt
        $resParent = $this->actingAs($parent)->post(route('attendance.store'), $postData);
        $this->assertTrue(in_array($resParent->status(), [302, 403]));
    }

    /**
     * Test invalid attendance status and invalid BS dates are rejected.
     */
    public function test_invalid_status_and_invalid_bs_dates_are_rejected()
    {
        $admin = $this->getAdmin();
        $class = MyClass::first();
        $student = StudentRecord::where('my_class_id', $class->id)->first();

        // 1. Invalid status
        $resInvalidStatus = $this->actingAs($admin)->post(route('attendance.store'), [
            'my_class_id' => $class->id,
            'attendance_date_bs' => '2083-07-08',
            'students' => [
                $student->id => ['status' => 'sleeping', 'remarks' => ''],
            ],
        ]);
        $resInvalidStatus->assertSessionHasErrors('students.' . $student->id . '.status');

        // Confirm never silently converted to present
        $att = StudentAttendance::where('student_record_id', $student->id)
            ->where('attendance_date_bs', '2083-07-08')
            ->first();
        $this->assertNull($att);

        // 2. Invalid BS date (day out of range for month)
        $resInvalidDate = $this->actingAs($admin)->post(route('attendance.store'), [
            'my_class_id' => $class->id,
            'attendance_date_bs' => '2083-07-35', // Kartik has 30 days
            'students' => [
                $student->id => ['status' => 'present', 'remarks' => ''],
            ],
        ]);
        $resInvalidDate->assertSessionHasErrors('attendance_date_bs');
    }

    /**
     * Test calendar integration: warns when attendance is inspected on configured holiday.
     */
    public function test_calendar_holiday_inspection_and_warning()
    {
        $admin = $this->getAdmin();

        // Create a test holiday
        Holiday::where('title', 'Dashain Festival Break')->delete();
        $holiday = Holiday::create([
            'title' => 'Dashain Festival Break',
            'start_date' => '2026-10-25', // 2083-07-08 BS
            'end_date' => '2026-10-26',
            'start_date_bs' => '2083-07-08',
            'end_date_bs' => '2083-07-09',
            'type' => 'public_holiday',
            'is_closure' => true,
        ]);

        $attendanceService = app(AttendanceService::class);
        $inspection = $attendanceService->inspectAttendanceDate('2083-07-08');

        $this->assertTrue($inspection['is_holiday']);
        $this->assertStringContainsString('Dashain Festival', $inspection['warning']);

        $holiday->delete();
    }

    /**
     * Test attendance stats and percentage calculation rules.
     */
    public function test_attendance_percentage_computation_rules()
    {
        $attendanceService = app(AttendanceService::class);

        // 10 records: 7 present, 1 late, 1 half-day, 1 absent
        $records = collect([
            (object)['status' => 'present'],
            (object)['status' => 'present'],
            (object)['status' => 'present'],
            (object)['status' => 'present'],
            (object)['status' => 'present'],
            (object)['status' => 'present'],
            (object)['status' => 'present'],
            (object)['status' => 'late'],
            (object)['status' => 'half_day'],
            (object)['status' => 'absent'],
        ]);

        $stats = $attendanceService->computeAttendanceStats($records);

        // effective present = 7 + 1 + 0.5 = 8.5 out of 10 = 85.0%
        $this->assertEquals(10, $stats['total_days']);
        $this->assertEquals(7, $stats['present']);
        $this->assertEquals(1, $stats['late']);
        $this->assertEquals(1, $stats['half_day']);
        $this->assertEquals(1, $stats['absent']);
        $this->assertEquals(8.5, $stats['effective_present']);
        $this->assertEquals(85.0, $stats['percentage']);
    }

    /**
     * Test admin can record and view staff daily attendance.
     */
    public function test_admin_can_record_staff_daily_attendance()
    {
        $admin = $this->getAdmin();
        $staffUser = User::whereIn('user_type', ['teacher', 'accountant'])->first();

        $response = $this->actingAs($admin)->post(route('staff_attendance.store'), [
            'attendance_date_bs' => '2083-07-08',
            'staff' => [
                $staffUser->id => ['status' => 'present', 'remarks' => 'On time'],
            ],
        ]);

        $response->assertSessionHas('flash_success');

        $staffAtt = StaffAttendance::where('user_id', $staffUser->id)
            ->where('attendance_date_bs', '2083-07-08')
            ->first();

        $this->assertNotNull($staffAtt);
        $this->assertEquals('present', $staffAtt->status);
        $this->assertEquals('On time', $staffAtt->remarks);
    }

    /**
     * Test monthly attendance matrix generation.
     */
    public function test_monthly_class_attendance_matrix_loads_correctly()
    {
        $admin = $this->getAdmin();
        $class = MyClass::first();

        $response = $this->actingAs($admin)->get(route('attendance.monthly', [
            'my_class_id' => $class->id,
            'year_bs' => 2083,
            'month_bs' => 7,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Kartik');
        $response->assertSee('Attendance Sheet');
    }

    /**
     * Test class-wise daily attendance comparison report.
     */
    public function test_class_comparison_report_loads()
    {
        $admin = $this->getAdmin();
        $response = $this->actingAs($admin)->get(route('attendance.class_report', [
            'attendance_date_bs' => '2083-07-08',
        ]));

        $response->assertStatus(200);
        $response->assertSee(__('Attendance Overview by Class'));
    }

    /**
     * Test AJAX sections endpoint scopes for teachers vs admins.
     */
    public function test_get_sections_ajax_scopes_for_teachers_and_admins()
    {
        $teacher = $this->getTeacher();
        $admin = $this->getAdmin();
        $class = MyClass::first();
        $sections = Section::where('my_class_id', $class->id)->get();

        if ($sections->count() > 1) {
            $assignedSec = $sections->first();
            Section::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);
            Section::where('id', $assignedSec->id)->update(['teacher_id' => $teacher->id]);

            // Teacher only gets assigned section
            $resTeacher = $this->actingAs($teacher)->get(route('attendance.get_sections', $class->id));
            $resTeacher->assertStatus(200);
            $dataTeacher = $resTeacher->json();
            $this->assertCount(1, $dataTeacher);
            $this->assertEquals($assignedSec->id, $dataTeacher[0]['id']);

            // Admin gets all sections
            $resAdmin = $this->actingAs($admin)->get(route('attendance.get_sections', $class->id));
            $resAdmin->assertStatus(200);
            $dataAdmin = $resAdmin->json();
            $this->assertCount($sections->count(), $dataAdmin);
        }
    }

    /**
     * Test authorized admin and assigned teacher can synchronize an offline attendance batch.
     */
    public function test_authorized_admin_and_teacher_can_sync_offline_attendance_batch()
    {
        $admin = $this->getAdmin();
        $class = MyClass::first();
        $students = StudentRecord::where('my_class_id', $class->id)->take(2)->get();
        $this->assertNotEmpty($students);

        $uuid = 'test-sync-uuid-' . uniqid();
        $payload = [
            'idempotency_key' => $uuid,
            'my_class_id' => $class->id,
            'attendance_date_bs' => '2083-07-09',
            'created_at_client' => now()->subMinutes(5)->toIso8601String(),
            'students' => [
                $students[0]->id => ['status' => 'present', 'remarks' => 'On time'],
                $students[1]->id => ['status' => 'absent', 'remarks' => 'Fever'],
            ],
        ];

        $response = $this->actingAs($admin)->postJson(route('attendance.sync'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'synced' => true,
            'idempotency_key' => $uuid,
        ]);

        $this->assertDatabaseHas('student_attendances', [
            'student_record_id' => $students[0]->id,
            'attendance_date_bs' => '2083-07-09',
            'status' => 'present',
        ]);
        $this->assertDatabaseHas('student_attendances', [
            'student_record_id' => $students[1]->id,
            'attendance_date_bs' => '2083-07-09',
            'status' => 'absent',
        ]);
    }

    /**
     * Test unauthenticated requests to sync endpoint are rejected.
     */
    public function test_unauthenticated_user_cannot_sync_attendance()
    {
        $response = $this->postJson(route('attendance.sync'), [
            'idempotency_key' => 'unauth-key-123',
            'my_class_id' => 1,
            'attendance_date_bs' => '2083-07-09',
            'students' => [1 => ['status' => 'present']],
        ]);

        $this->assertTrue(in_array($response->status(), [401, 302], true));
    }

    /**
     * Test unauthorized teacher cannot sync attendance for an unassigned class.
     */
    public function test_unauthorized_teacher_cannot_sync_unassigned_class_or_section()
    {
        $teacher = $this->getTeacher();
        $class = MyClass::first();

        // Ensure teacher has NO assignments in this class
        Section::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);

        $students = StudentRecord::where('my_class_id', $class->id)->take(1)->get();
        $this->assertNotEmpty($students);

        $payload = [
            'idempotency_key' => 'unauthorized-teacher-' . uniqid(),
            'my_class_id' => $class->id,
            'attendance_date_bs' => '2083-07-09',
            'students' => [
                $students[0]->id => ['status' => 'present', 'remarks' => ''],
            ],
        ];

        $response = $this->actingAs($teacher)->postJson(route('attendance.sync'), $payload);
        $response->assertStatus(403);
    }

    /**
     * Test invalid status or invalid BS date in sync payload returns 422.
     */
    public function test_invalid_attendance_status_or_date_is_rejected_in_sync()
    {
        $admin = $this->getAdmin();
        $class = MyClass::first();
        $student = StudentRecord::where('my_class_id', $class->id)->first();

        // 1. Invalid status
        $resInvalidStatus = $this->actingAs($admin)->postJson(route('attendance.sync'), [
            'idempotency_key' => 'invalid-status-key',
            'my_class_id' => $class->id,
            'attendance_date_bs' => '2083-07-09',
            'students' => [
                $student->id => ['status' => 'invalid_status_value'],
            ],
        ]);
        $resInvalidStatus->assertStatus(422);

        // 2. Invalid date
        $resInvalidDate = $this->actingAs($admin)->postJson(route('attendance.sync'), [
            'idempotency_key' => 'invalid-date-key',
            'my_class_id' => $class->id,
            'attendance_date_bs' => '2083-99-99',
            'students' => [
                $student->id => ['status' => 'present'],
            ],
        ]);
        $resInvalidDate->assertStatus(422);
    }

    /**
     * Test idempotency key prevents duplicate writes and returns cached confirmation on retry.
     */
    public function test_idempotency_prevents_duplicate_sync_commits_on_retries()
    {
        $admin = $this->getAdmin();
        $class = MyClass::first();
        $student = StudentRecord::where('my_class_id', $class->id)->first();

        $uuid = 'idempotent-test-' . uniqid();
        $payload = [
            'idempotency_key' => $uuid,
            'my_class_id' => $class->id,
            'attendance_date_bs' => '2083-07-09',
            'students' => [
                $student->id => ['status' => 'present', 'remarks' => 'Initial sync'],
            ],
        ];

        // First attempt: Commits
        $res1 = $this->actingAs($admin)->postJson(route('attendance.sync'), $payload);
        $res1->assertStatus(200);
        $res1->assertJson(['status' => 'success', 'synced' => true]);

        $initialAuditCount = AttendanceAuditTrail::where('attendance_date_bs', '2083-07-09')->count();

        // Second attempt with SAME idempotency key (simulating timeout recovery retry)
        $res2 = $this->actingAs($admin)->postJson(route('attendance.sync'), $payload);
        $res2->assertStatus(200);
        $res2->assertJson([
            'status' => 'success',
            'synced' => true,
            'idempotent_replay' => true,
            'idempotency_key' => $uuid,
        ]);

        // Audit trail count must not have duplicated
        $afterRetryAuditCount = AttendanceAuditTrail::where('attendance_date_bs', '2083-07-09')->count();
        $this->assertEquals($initialAuditCount, $afterRetryAuditCount, 'Retrying with identical idempotency key must not duplicate database changes');
    }

    /**
     * Test conflict detection flags an error if server record was modified after client draft was created.
     */
    public function test_conflict_detection_detects_server_modifications_and_requires_review()
    {
        $admin = $this->getAdmin();
        $class = MyClass::first();
        $student = StudentRecord::where('my_class_id', $class->id)->first();

        // 1. Initial record exists on server and was updated at 10:00
        StudentAttendance::updateOrCreate(
            ['student_record_id' => $student->id, 'attendance_date' => '2026-10-26'],
            [
                'user_id' => $student->user_id,
                'my_class_id' => $class->id,
                'attendance_date_bs' => '2083-07-09',
                'status' => 'absent',
                'updated_at' => now(),
            ]
        );

        // 2. Client submits draft that was created in the past (e.g. 1 hour ago before server update)
        $clientCreatedTime = now()->subMinutes(60)->toIso8601String();
        $conflictPayload = [
            'idempotency_key' => 'conflict-test-' . uniqid(),
            'my_class_id' => $class->id,
            'attendance_date_bs' => '2083-07-09',
            'created_at_client' => $clientCreatedTime,
            'force_overwrite' => false,
            'students' => [
                $student->id => ['status' => 'present', 'remarks' => 'I marked him present earlier'],
            ],
        ];

        $response = $this->actingAs($admin)->postJson(route('attendance.sync'), $conflictPayload);
        $response->assertStatus(409);
        $response->assertJson([
            'status' => 'conflict',
            'synced' => false,
        ]);

        // Verify server record was NOT silently overwritten
        $serverRecord = StudentAttendance::where('student_record_id', $student->id)
            ->where('attendance_date_bs', '2083-07-09')
            ->first();
        $this->assertEquals('absent', $serverRecord->status);
    }

    /**
     * Test user can resolve conflict with explicit force_overwrite flag.
     */
    public function test_force_overwrite_resolves_conflict_when_user_approves()
    {
        $admin = $this->getAdmin();
        $class = MyClass::first();
        $student = StudentRecord::where('my_class_id', $class->id)->first();

        $clientCreatedTime = now()->subMinutes(60)->toIso8601String();
        $overwritePayload = [
            'idempotency_key' => 'overwrite-test-' . uniqid(),
            'my_class_id' => $class->id,
            'attendance_date_bs' => '2083-07-09',
            'created_at_client' => $clientCreatedTime,
            'force_overwrite' => true, // User confirmed overwrite
            'students' => [
                $student->id => ['status' => 'late', 'remarks' => 'Confirmed late'],
            ],
        ];

        $response = $this->actingAs($admin)->postJson(route('attendance.sync'), $overwritePayload);
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'synced' => true,
        ]);

        $serverRecord = StudentAttendance::where('student_record_id', $student->id)
            ->where('attendance_date_bs', '2083-07-09')
            ->first();
        $this->assertEquals('late', $serverRecord->status);
    }
}
