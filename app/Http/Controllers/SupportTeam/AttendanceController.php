<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\MyClass;
use App\Models\Section;
use App\Models\StudentAttendance;
use App\Models\StudentRecord;
use App\Repositories\MyClassRepo;
use App\Services\AttendanceService;
use App\Services\BikramSambatService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    protected $attendanceService;
    protected $bsService;
    protected $myClassRepo;

    public function __construct(
        AttendanceService $attendanceService,
        BikramSambatService $bsService,
        MyClassRepo $myClassRepo
    ) {
        $this->attendanceService = $attendanceService;
        $this->bsService = $bsService;
        $this->myClassRepo = $myClassRepo;
    }

    /**
     * Daily student attendance entry & view.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isTeacher = ($user->user_type === 'teacher');

        $assignedSections = collect();
        if ($isTeacher) {
            $assignedSections = Section::where('teacher_id', $user->id)->get();
            if ($assignedSections->isEmpty()) {
                if ($request->filled('my_class_id') || $request->filled('section_id')) {
                    abort(403, __('Unauthorized: You do not have an assigned class or section.'));
                }
                $data['my_classes'] = collect();
                $data['sections'] = collect();
                $data['students'] = collect();
                $data['existing_records'] = collect();
                $data['is_already_recorded'] = false;
                $data['date_info'] = null;
                $data['selected_class'] = null;
                $data['selected_section'] = null;
                $data['today_bs'] = $this->bsService->adToBs(date('Y-m-d'))['bs_date'];
                $data['selected_date'] = $request->get('attendance_date_bs', $data['today_bs']);
                $data['academic_years'] = AcademicYear::orderBy('year_bs', 'desc')->get();
                $data['can_manage'] = false;
                $data['has_assignment'] = false;

                return view('pages.support_team.attendance.index', $data);
            }
            $assignedClassIds = $assignedSections->pluck('my_class_id')->unique()->values();
            $data['my_classes'] = MyClass::whereIn('id', $assignedClassIds)->get();
        } else {
            $data['my_classes'] = $this->myClassRepo->all();
        }

        $data['has_assignment'] = true;
        $data['academic_years'] = AcademicYear::orderBy('year_bs', 'desc')->get();
        $todayBs = $this->bsService->adToBs(date('Y-m-d'))['bs_date'];

        $classId = $request->get('my_class_id');
        $sectionId = $request->get('section_id');
        $bsDate = $request->get('attendance_date_bs', $todayBs);

        // Auto-select if teacher has exactly 1 assignment and class parameter was not specified
        if ($isTeacher && !$request->filled('my_class_id') && $assignedSections->count() === 1) {
            $classId = $assignedSections->first()->my_class_id;
            $sectionId = $assignedSections->first()->id;
        }

        // Validate teacher access to requested class/section server-side
        if ($isTeacher && $classId) {
            if (!$assignedClassIds->contains((int)$classId)) {
                abort(403, __('Unauthorized: You are not assigned as the class teacher for this class.'));
            }

            if ($sectionId) {
                $isSecValid = $assignedSections->where('id', (int)$sectionId)->where('my_class_id', (int)$classId)->isNotEmpty();
                if (!$isSecValid) {
                    abort(403, __('Unauthorized: You are not assigned to this section.'));
                }
            } else {
                $teacherClassSections = $assignedSections->where('my_class_id', (int)$classId);
                if ($teacherClassSections->count() === 1) {
                    $sectionId = $teacherClassSections->first()->id;
                }
            }
        }

        $data['selected_class'] = $classId;
        $data['selected_section'] = $sectionId;
        $data['selected_date'] = $bsDate;
        $data['today_bs'] = $todayBs;

        if ($classId) {
            if ($isTeacher) {
                $data['sections'] = Section::where('my_class_id', $classId)->where('teacher_id', $user->id)->get();
            } else {
                $data['sections'] = Section::where('my_class_id', $classId)->get();
            }
        } else {
            $data['sections'] = collect();
        }

        $data['students'] = collect();
        $data['existing_records'] = collect();
        $data['is_already_recorded'] = false;
        $data['date_info'] = null;
        $data['can_manage'] = $classId ? $this->attendanceService->canUserManageAttendance($user, $classId, $sectionId) : true;

        if ($classId) {
            try {
                $data['date_info'] = $this->attendanceService->inspectAttendanceDate($bsDate);
                $adDate = $data['date_info']['ad_date'];

                $studentsQuery = StudentRecord::where('my_class_id', $classId);
                if ($sectionId) {
                    $studentsQuery->where('section_id', $sectionId);
                } elseif ($isTeacher) {
                    $studentsQuery->whereIn('section_id', $assignedSections->where('my_class_id', $classId)->pluck('id'));
                }
                $data['students'] = $studentsQuery->with('user')->get();

                $attendancesQuery = StudentAttendance::where('my_class_id', $classId)
                    ->where('attendance_date', $adDate);

                if ($sectionId) {
                    $attendancesQuery->where('section_id', $sectionId);
                } elseif ($isTeacher) {
                    $attendancesQuery->whereIn('section_id', $assignedSections->where('my_class_id', $classId)->pluck('id'));
                }

                $attendances = $attendancesQuery->get()->keyBy('student_record_id');

                $data['existing_records'] = $attendances;
                $data['is_already_recorded'] = ($attendances->count() > 0);
            } catch (\Exception $e) {
                return back()->with('flash_danger', __('Invalid BS date: ') . $e->getMessage());
            }
        }

        return view('pages.support_team.attendance.index', $data);
    }

    /**
     * Store or bulk update student attendance.
     */
    public function store(Request $request)
    {
        // If client requests JSON or submits an offline sync payload with idempotency key, handle via sync()
        if ($request->expectsJson() || $request->ajax() || $request->filled('idempotency_key')) {
            return $this->sync($request);
        }

        $request->validate([
            'my_class_id' => 'required|exists:my_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'attendance_date_bs' => 'required|string',
            'students' => 'required|array|min:1',
            'students.*.status' => 'required|in:present,absent,late,leave,half_day',
            'students.*.remarks' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $classId = (int)$request->my_class_id;
        $sectionId = $request->filled('section_id') ? (int)$request->section_id : null;

        // Validate BS date using BikramSambatService
        try {
            $this->bsService->bsToAd($request->attendance_date_bs);
        } catch (\Exception $e) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'attendance_date_bs' => [__('The attendance date is invalid: ') . $e->getMessage()]
            ]);
        }

        // Authorize teacher assignment server-side
        if (!$this->attendanceService->canUserManageAttendance($user, $classId, $sectionId)) {
            abort(403, __('Unauthorized: You are not assigned to record attendance for this class or section.'));
        }

        // If teacher and no section_id passed, auto-resolve if teacher has 1 section in class
        if ($user->user_type === 'teacher' && !$sectionId) {
            $teacherSections = Section::where('teacher_id', $user->id)->where('my_class_id', $classId)->get();
            if ($teacherSections->count() === 1) {
                $sectionId = $teacherSections->first()->id;
            }
        }

        try {
            $currentSession = Qs::getCurrentSession();
            $activeYear = AcademicYear::active()->first();

            $result = $this->attendanceService->recordStudentAttendance([
                'my_class_id' => $classId,
                'section_id' => $sectionId,
                'attendance_date_bs' => $request->attendance_date_bs,
                'session' => $currentSession,
                'academic_year_id' => $activeYear ? $activeYear->id : null,
                'students' => $request->students,
            ], $user, $request->ip());

            $msg = __('Attendance saved successfully. (:created new, :updated updated).', [
                'created' => $result['created'],
                'updated' => $result['updated'],
            ]);

            return redirect()->route('attendance.index', [
                'my_class_id' => $classId,
                'section_id' => $sectionId,
                'attendance_date_bs' => $request->attendance_date_bs,
            ])->with('flash_success', $msg);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()->with('flash_danger', __('Failed to record attendance: ') . $e->getMessage());
        }
    }

    /**
     * Synchronize offline attendance draft batch with idempotency and conflict detection.
     */
    public function sync(Request $request)
    {
        $request->validate([
            'idempotency_key' => 'required|string|max:64',
            'my_class_id' => 'required|exists:my_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'attendance_date_bs' => 'required|string',
            'students' => 'required|array|min:1',
            'students.*.status' => 'required|in:present,absent,late,leave,half_day',
            'students.*.remarks' => 'nullable|string|max:255',
            'created_at_client' => 'nullable|string',
            'force_overwrite' => 'nullable|boolean',
        ]);

        $idempotencyKey = $request->idempotency_key;
        $cacheKey = 'att_sync_' . md5($idempotencyKey);

        // 1. Idempotency replay check (handles network timeout after DB commit)
        if ($cached = Cache::get($cacheKey)) {
            return response()->json([
                'status' => 'success',
                'synced' => true,
                'idempotent_replay' => true,
                'idempotency_key' => $idempotencyKey,
                'message' => __('Attendance batch already confirmed by server.'),
                'data' => $cached,
            ], 200);
        }

        $user = Auth::user();
        $classId = (int)$request->my_class_id;
        $sectionId = $request->filled('section_id') ? (int)$request->section_id : null;

        // 2. Validate BS date
        try {
            $dateInfo = $this->attendanceService->inspectAttendanceDate($request->attendance_date_bs);
            $adDate = $dateInfo['ad_date'];
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'synced' => false,
                'message' => __('The attendance date is invalid: ') . $e->getMessage(),
            ], 422);
        }

        // 3. Authorize teacher / staff assignment server-side
        if (!$this->attendanceService->canUserManageAttendance($user, $classId, $sectionId)) {
            return response()->json([
                'status' => 'error',
                'synced' => false,
                'message' => __('Unauthorized: You are not assigned to record attendance for this class or section.'),
            ], 403);
        }

        // Auto-resolve section for teacher if single section in class
        if ($user->user_type === 'teacher' && !$sectionId) {
            $teacherSections = Section::where('teacher_id', $user->id)->where('my_class_id', $classId)->get();
            if ($teacherSections->count() === 1) {
                $sectionId = $teacherSections->first()->id;
            }
        }

        // 4. Conflict detection: Check if records were modified on server after client created the draft
        if (!$request->boolean('force_overwrite') && $request->filled('created_at_client')) {
            try {
                $clientTime = Carbon::parse($request->created_at_client);
                $studentIds = array_keys($request->students);
                $conflict = $this->attendanceService->checkAttendanceConflict($classId, $adDate, $studentIds, $clientTime);

                if ($conflict) {
                    return response()->json([
                        'status' => 'conflict',
                        'synced' => false,
                        'idempotency_key' => $idempotencyKey,
                        'server_updated_at' => $conflict->updated_at ? $conflict->updated_at->toIso8601String() : null,
                        'server_updater' => ($conflict->updater) ? $conflict->updater->name : __('Another staff member'),
                        'message' => __('Conflict: Attendance for this class was modified on the server after your offline draft was created. Please review and decide whether to overwrite or discard.'),
                    ], 409);
                }
            } catch (\Exception $e) {
                // If client date parsing fails, log and proceed with standard save
                Log::warning('Client date parse exception in attendance sync: ' . $e->getMessage());
            }
        }

        // 5. Atomic record execution
        try {
            $currentSession = Qs::getCurrentSession();
            $activeYear = AcademicYear::active()->first();

            $result = $this->attendanceService->recordStudentAttendance([
                'my_class_id' => $classId,
                'section_id' => $sectionId,
                'attendance_date_bs' => $request->attendance_date_bs,
                'session' => $currentSession,
                'academic_year_id' => $activeYear ? $activeYear->id : null,
                'students' => $request->students,
            ], $user, $request->ip());

            $responseData = [
                'created' => $result['created'],
                'updated' => $result['updated'],
                'total' => count($request->students),
                'class_id' => $classId,
                'section_id' => $sectionId,
                'attendance_date_bs' => $request->attendance_date_bs,
                'synced_at' => now()->toIso8601String(),
            ];

            // Cache confirmation for 7 days to ensure idempotent retries
            Cache::put($cacheKey, $responseData, now()->addDays(7));

            return response()->json([
                'status' => 'success',
                'synced' => true,
                'idempotency_key' => $idempotencyKey,
                'message' => __('Attendance synchronized successfully. (:created new, :updated updated).', [
                    'created' => $result['created'],
                    'updated' => $result['updated'],
                ]),
                'data' => $responseData,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'synced' => false,
                'message' => __('Validation failed.'),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            return response()->json([
                'status' => 'error',
                'synced' => false,
                'message' => $e->getMessage() ?: __('Access denied.')
            ], $e->getStatusCode());
        } catch (\Exception $e) {
            Log::error('Attendance sync error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'status' => 'error',
                'synced' => false,
                'message' => __('Failed to synchronize attendance due to a server error. Please try again.')
            ], 500);
        }
    }

    /**
     * View individual student attendance history.
     */
    public function studentHistory(Request $request, $student_id)
    {
        $sr = StudentRecord::with(['user', 'my_class', 'section'])->findOrFail($student_id);

        if (!$this->attendanceService->canUserViewStudentAttendance(Auth::user(), $sr->id)) {
            abort(403, __('Access Denied: You do not have permission to view this student\'s attendance.'));
        }

        $query = StudentAttendance::where('student_record_id', $sr->id);

        // Optional status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Optional date range
        if ($request->filled('start_date_bs')) {
            try {
                $startAd = $this->bsService->bsToAd($request->start_date_bs)['ad_date'];
                $query->where('attendance_date', '>=', $startAd);
            } catch (\Exception $e) {}
        }
        if ($request->filled('end_date_bs')) {
            try {
                $endAd = $this->bsService->bsToAd($request->end_date_bs)['ad_date'];
                $query->where('attendance_date', '<=', $endAd);
            } catch (\Exception $e) {}
        }

        $records = $query->orderBy('attendance_date', 'desc')->get();
        $stats = $this->attendanceService->computeAttendanceStats($records);

        $data['sr'] = $sr;
        $data['records'] = $records;
        $data['stats'] = $stats;
        $data['status_filter'] = $request->status;
        $data['start_date_bs'] = $request->start_date_bs;
        $data['end_date_bs'] = $request->end_date_bs;

        return view('pages.support_team.attendance.student_history', $data);
    }

    /**
     * Monthly attendance matrix for class/section.
     */
    public function monthly(Request $request)
    {
        $user = Auth::user();
        $isTeacher = ($user->user_type === 'teacher');

        $assignedSections = collect();
        if ($isTeacher) {
            $assignedSections = Section::where('teacher_id', $user->id)->get();
            if ($assignedSections->isEmpty()) {
                if ($request->filled('my_class_id') || $request->filled('section_id')) {
                    abort(403, __('Unauthorized: You do not have an assigned class or section.'));
                }
                $data['my_classes'] = collect();
                $data['sections'] = collect();
                $data['selected_class'] = null;
                $data['selected_section'] = null;
                $data['selected_year'] = 2083;
                $data['selected_month'] = 1;
                $data['matrix_data'] = null;
                $data['has_assignment'] = false;
                return view('pages.support_team.attendance.monthly', $data);
            }
            $assignedClassIds = $assignedSections->pluck('my_class_id')->unique()->values();
            $data['my_classes'] = MyClass::whereIn('id', $assignedClassIds)->get();
        } else {
            $data['my_classes'] = $this->myClassRepo->all();
        }

        $data['has_assignment'] = true;
        $todayBs = $this->bsService->adToBs(date('Y-m-d'));
        $curYear = $todayBs['year'];
        $curMonth = $todayBs['month'];

        $classId = $request->get('my_class_id');
        $sectionId = $request->get('section_id');
        $yearBs = (int)$request->get('year_bs', $curYear);
        $monthBs = (int)$request->get('month_bs', $curMonth);

        // Auto-select if teacher has 1 assignment and not requested
        if ($isTeacher && !$request->filled('my_class_id') && $assignedSections->count() === 1) {
            $classId = $assignedSections->first()->my_class_id;
            $sectionId = $assignedSections->first()->id;
        }

        // Validate teacher access
        if ($isTeacher && $classId) {
            if (!$assignedClassIds->contains((int)$classId)) {
                abort(403, __('Unauthorized: You are not assigned to this class.'));
            }
            if ($sectionId) {
                $isSecValid = $assignedSections->where('id', (int)$sectionId)->where('my_class_id', (int)$classId)->isNotEmpty();
                if (!$isSecValid) {
                    abort(403, __('Unauthorized: You are not assigned to this section.'));
                }
            } else {
                $teacherClassSections = $assignedSections->where('my_class_id', (int)$classId);
                if ($teacherClassSections->count() === 1) {
                    $sectionId = $teacherClassSections->first()->id;
                }
            }
        }

        $data['selected_class'] = $classId;
        $data['selected_section'] = $sectionId;
        $data['selected_year'] = $yearBs;
        $data['selected_month'] = $monthBs;

        if ($classId) {
            if ($isTeacher) {
                $data['sections'] = Section::where('my_class_id', $classId)->where('teacher_id', $user->id)->get();
            } else {
                $data['sections'] = Section::where('my_class_id', $classId)->get();
            }
        } else {
            $data['sections'] = collect();
        }

        $data['matrix_data'] = null;

        if ($classId) {
            $allowedSectionIds = ($isTeacher && !$sectionId)
                ? $assignedSections->where('my_class_id', (int)$classId)->pluck('id')->all()
                : null;

            $data['matrix_data'] = $this->attendanceService->getMonthlyClassAttendanceMatrix(
                $classId,
                $sectionId,
                $yearBs,
                $monthBs,
                $allowedSectionIds
            );
        }

        return view('pages.support_team.attendance.monthly', $data);
    }

    /**
     * Class-wise daily attendance comparison report.
     */
    public function classReport(Request $request)
    {
        $user = Auth::user();
        $isTeacher = ($user->user_type === 'teacher');

        $assignedSections = collect();
        if ($isTeacher) {
            $assignedSections = Section::where('teacher_id', $user->id)->get();
            if ($assignedSections->isEmpty()) {
                $data['has_assignment'] = false;
                $data['classes_report'] = [];
                $data['selected_date'] = $this->bsService->adToBs(date('Y-m-d'))['bs_date'];
                return view('pages.support_team.attendance.class_report', $data);
            }
        }

        $data['has_assignment'] = true;
        $todayBs = $this->bsService->adToBs(date('Y-m-d'))['bs_date'];
        $bsDate = $request->get('attendance_date_bs', $todayBs);
        $data['selected_date'] = $bsDate;
        $data['classes_report'] = [];

        try {
            $dateInfo = $this->attendanceService->inspectAttendanceDate($bsDate);
            $adDate = $dateInfo['ad_date'];
            $data['date_info'] = $dateInfo;

            if ($isTeacher) {
                $assignedClassIds = $assignedSections->pluck('my_class_id')->unique();
                $classes = MyClass::whereIn('id', $assignedClassIds)->with('sections')->get();
            } else {
                $classes = MyClass::with('sections')->get();
            }

            foreach ($classes as $c) {
                $teacherSecIds = $isTeacher ? $assignedSections->where('my_class_id', $c->id)->pluck('id') : null;

                $studentsQuery = StudentRecord::where('my_class_id', $c->id);
                if ($isTeacher) {
                    $studentsQuery->whereIn('section_id', $teacherSecIds);
                }
                $studentsCount = $studentsQuery->count();

                $attQuery = StudentAttendance::where('my_class_id', $c->id)
                    ->where('attendance_date', $adDate);
                if ($isTeacher) {
                    $attQuery->whereIn('section_id', $teacherSecIds);
                }
                $attendances = $attQuery->get();

                $present = $attendances->where('status', 'present')->count();
                $late = $attendances->where('status', 'late')->count();
                $leave = $attendances->where('status', 'leave')->count();
                $absent = $attendances->where('status', 'absent')->count();
                $halfDay = $attendances->where('status', 'half_day')->count();
                $recorded = $attendances->count();

                $effectivePresent = $present + $late + ($halfDay * 0.5);
                $pct = $recorded > 0 ? round(($effectivePresent / $recorded) * 100, 1) : 0.0;

                $data['classes_report'][] = [
                    'class' => $c,
                    'total_students' => $studentsCount,
                    'recorded_attendance' => $recorded,
                    'present' => $present,
                    'late' => $late,
                    'leave' => $leave,
                    'absent' => $absent,
                    'percentage' => $pct,
                ];
            }
        } catch (\Exception $e) {
            return back()->with('flash_danger', __('Invalid BS date: ') . $e->getMessage());
        }

        return view('pages.support_team.attendance.class_report', $data);
    }

    /**
     * AJAX endpoint to return class sections scoped for attendance.
     */
    public function getSections($class_id)
    {
        $user = Auth::user();
        if ($user->user_type === 'teacher') {
            $sections = Section::where('my_class_id', $class_id)->where('teacher_id', $user->id)->get();
        } else {
            $sections = Section::where('my_class_id', $class_id)->get();
        }

        return response()->json($sections->map(function ($s) {
            return ['id' => $s->id, 'name' => $s->name];
        }));
    }
}
