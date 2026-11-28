<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\StudentRecord;
use App\Models\StudentTransport;
use App\Models\TransportAttendance;
use App\Models\TransportRoute;
use App\Services\ErpSupportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransportController extends Controller
{
    protected ErpSupportService $erp;

    public function __construct(ErpSupportService $erp)
    {
        $this->middleware('auth');
        $this->erp = $erp;
    }

    public function index()
    {
        $user = Auth::user();
        $allowedSrIds = $this->erp->accessibleStudentRecordIds($user);

        $routes = TransportRoute::withCount('assignments')->orderBy('name')->get();

        $assignQuery = StudentTransport::with(['route', 'studentRecord.user', 'studentRecord.my_class'])
            ->orderBy('id', 'desc');
        if ($allowedSrIds !== null) {
            $assignQuery->whereIn('student_record_id', $allowedSrIds ?: [0]);
        }
        $assignments = $assignQuery->paginate(15, ['*'], 'assign_page');

        $attQuery = TransportAttendance::with(['route', 'studentRecord.user'])
            ->orderBy('attendance_date_ad', 'desc')
            ->orderBy('id', 'desc');
        if ($allowedSrIds !== null) {
            $attQuery->whereIn('student_record_id', $allowedSrIds ?: [0]);
        }
        $busAttendances = $attQuery->take(40)->get();

        $students = $allowedSrIds === null
            ? StudentRecord::with(['user', 'my_class'])->where('grad', 0)->orderBy('adm_no')->get()
            : collect();

        return view('pages.support_team.transport.index', compact(
            'routes', 'assignments', 'busAttendances', 'students'
        ));
    }

    public function storeRoute(Request $request)
    {
        if (!Qs::userIsTeamSA()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'name_ne' => 'nullable|string|max:180',
            'vehicle_number' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:120',
            'driver_phone' => 'nullable|string|max:30',
            'monthly_fee' => 'required|numeric|min:0|max:100000',
        ]);

        TransportRoute::create($validated + ['is_active' => true]);

        return back()->with('flash_success', __('Transport route created.'));
    }

    public function assignStudent(Request $request)
    {
        if (!Qs::userIsTeamSA()) {
            abort(403);
        }

        $validated = $request->validate([
            'student_record_id' => 'required|exists:student_records,id',
            'transport_route_id' => 'required|exists:transport_routes,id',
            'pickup_point' => 'nullable|string|max:150',
        ]);

        StudentTransport::updateOrCreate(
            ['student_record_id' => $validated['student_record_id'], 'academic_year' => Qs::getCurrentSession()],
            [
                'transport_route_id' => $validated['transport_route_id'],
                'pickup_point' => $validated['pickup_point'] ?? null,
                'is_active' => true,
            ]
        );

        return back()->with('flash_success', __('Student assigned to transport route.'));
    }

    public function storeAttendance(Request $request)
    {
        if (!Qs::userIsTeamSAT()) {
            abort(403);
        }

        $validated = $request->validate([
            'transport_route_id' => 'required|exists:transport_routes,id',
            'student_record_id' => 'required|exists:student_records,id',
            'trip_type' => 'required|in:pickup,drop',
            'status' => 'required|in:boarded,missed,absent',
            'attendance_date_ad' => 'nullable|date',
            'attendance_date_bs' => 'nullable|string|max:20',
            'remarks' => 'nullable|string|max:191',
        ]);

        $dates = $this->erp->resolveDualDates($validated['attendance_date_ad'] ?? null, $validated['attendance_date_bs'] ?? null, true);

        TransportAttendance::updateOrCreate(
            [
                'transport_route_id' => $validated['transport_route_id'],
                'student_record_id' => $validated['student_record_id'],
                'attendance_date_ad' => $dates['ad'],
                'trip_type' => $validated['trip_type'],
            ],
            [
                'attendance_date_bs' => $dates['bs'],
                'status' => $validated['status'],
                'remarks' => $validated['remarks'] ?? null,
                'marked_by' => Auth::id(),
            ]
        );

        return back()->with('flash_success', __('Bus attendance recorded.'));
    }
}
