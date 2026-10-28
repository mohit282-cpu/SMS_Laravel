<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\StaffAttendance;
use App\Services\AttendanceService;
use App\Services\BikramSambatService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffAttendanceController extends Controller
{
    protected $attendanceService;
    protected $bsService;

    public function __construct(AttendanceService $attendanceService, BikramSambatService $bsService)
    {
        $this->middleware('teamSA');
        $this->attendanceService = $attendanceService;
        $this->bsService = $bsService;
    }

    /**
     * Daily staff attendance entry & view.
     */
    public function index(Request $request)
    {
        $todayBs = $this->bsService->adToBs(date('Y-m-d'))['bs_date'];
        $bsDate = $request->get('attendance_date_bs', $todayBs);

        $data['selected_date'] = $bsDate;
        $data['today_bs'] = $todayBs;

        try {
            $data['date_info'] = $this->attendanceService->inspectAttendanceDate($bsDate);
            $adDate = $data['date_info']['ad_date'];

            $staffUsers = User::whereIn('user_type', Qs::getStaff())
                ->orderBy('user_type')
                ->orderBy('name')
                ->get();

            $attendances = StaffAttendance::where('attendance_date', $adDate)
                ->get()
                ->keyBy('user_id');

            $data['staff_users'] = $staffUsers;
            $data['existing_records'] = $attendances;
            $data['is_already_recorded'] = ($attendances->count() > 0);
        } catch (\Exception $e) {
            return back()->with('flash_danger', __('Invalid BS date: ') . $e->getMessage());
        }

        return view('pages.support_team.staff_attendance.index', $data);
    }

    /**
     * Store or bulk update staff attendance.
     */
    public function store(Request $request)
    {
        $request->validate([
            'attendance_date_bs' => 'required|string',
            'staff' => 'required|array',
        ]);

        try {
            $currentSession = Qs::getCurrentSession();
            $activeYear = AcademicYear::active()->first();

            $result = $this->attendanceService->recordStaffAttendance([
                'attendance_date_bs' => $request->attendance_date_bs,
                'session' => $currentSession,
                'academic_year_id' => $activeYear ? $activeYear->id : null,
                'staff' => $request->staff,
            ], Auth::user(), $request->ip());

            $msg = __('Staff attendance saved successfully. (:created new, :updated updated).', [
                'created' => $result['created'],
                'updated' => $result['updated'],
            ]);

            return redirect()->route('staff_attendance.index', [
                'attendance_date_bs' => $request->attendance_date_bs,
            ])->with('flash_success', $msg);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput()->with('flash_danger', collect($e->errors())->flatten()->first());
        } catch (\Throwable $e) {
            $correlationId = (string) \Illuminate\Support\Str::uuid();
            \Illuminate\Support\Facades\Log::error("Failed to record staff attendance [Ref: {$correlationId}]: " . $e->getMessage(), [
                'correlation_id' => $correlationId,
                'user_id' => Auth::id(),
                'exception' => $e,
            ]);
            return back()->with('flash_danger', __('Failed to record staff attendance. Please try again. Reference ID: :id', ['id' => $correlationId]));
        }
    }

    /**
     * Staff monthly attendance summary report.
     */
    public function monthly(Request $request)
    {
        $todayBs = $this->bsService->adToBs(date('Y-m-d'));
        $curYear = $todayBs['year'];
        $curMonth = $todayBs['month'];

        $yearBs = (int)$request->get('year_bs', $curYear);
        $monthBs = (int)$request->get('month_bs', $curMonth);

        $data['selected_year'] = $yearBs;
        $data['selected_month'] = $monthBs;

        // Get calendar days for month
        $calendar = BikramSambatService::BS_CALENDAR_DATA;
        $daysInMonth = (isset($calendar[$yearBs]) && isset($calendar[$yearBs][$monthBs - 1]))
            ? $calendar[$yearBs][$monthBs - 1]
            : 30;

        $dates = [];
        $adDates = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $bsDateStr = sprintf('%04d-%02d-%02d', $yearBs, $monthBs, $d);
            try {
                $info = $this->attendanceService->inspectAttendanceDate($bsDateStr);
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

        $staffUsers = User::whereIn('user_type', Qs::getStaff())
            ->orderBy('user_type')
            ->orderBy('name')
            ->get();

        $attendances = StaffAttendance::whereIn('attendance_date', $adDates)
            ->get()
            ->groupBy('user_id');

        $matrix = [];
        foreach ($staffUsers as $u) {
            $userAttendances = $attendances->get($u->id, collect());
            $attendanceByBs = $userAttendances->keyBy('attendance_date_bs');

            $staffRow = [
                'user' => $u,
                'name' => $u->name,
                'role' => ucfirst(str_replace('_', ' ', $u->user_type)),
                'days' => [],
            ];

            foreach ($dates as $d => $dInfo) {
                $rec = $attendanceByBs->get($dInfo['bs_date']);
                $staffRow['days'][$d] = $rec ? $rec->status : null;
            }

            $staffRow['stats'] = $this->attendanceService->computeAttendanceStats($userAttendances);
            $matrix[] = $staffRow;
        }

        $data['month_name'] = BikramSambatService::MONTHS_NE[$monthBs] ?? '';
        $data['month_name_en'] = BikramSambatService::MONTHS_EN[$monthBs] ?? '';
        $data['days_in_month'] = $daysInMonth;
        $data['dates'] = $dates;
        $data['matrix'] = $matrix;

        return view('pages.support_team.staff_attendance.monthly', $data);
    }
}
