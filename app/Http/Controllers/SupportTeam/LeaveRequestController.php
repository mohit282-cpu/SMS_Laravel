<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\StudentRecord;
use App\Services\ErpSupportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveRequestController extends Controller
{
    protected ErpSupportService $erp;

    public function __construct(ErpSupportService $erp)
    {
        $this->middleware('auth');
        $this->erp = $erp;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $allowedSrIds = $this->erp->accessibleStudentRecordIds($user);

        $query = LeaveRequest::with(['applicant', 'studentRecord.user', 'studentRecord.my_class', 'reviewer'])
            ->orderBy('id', 'desc');

        if ($allowedSrIds !== null) {
            $query->where(function ($q) use ($user, $allowedSrIds) {
                $q->where('user_id', $user->id);
                if (!empty($allowedSrIds)) {
                    $q->orWhereIn('student_record_id', $allowedSrIds);
                }
            });
        }

        if ($request->filled('leave_scope')) {
            $query->where('leave_scope', $request->query('leave_scope'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $leaves = $query->paginate(15)->appends($request->query());

        $students = $allowedSrIds === null
            ? StudentRecord::with(['user', 'my_class'])->where('grad', 0)->orderBy('adm_no')->get()
            : StudentRecord::with(['user', 'my_class'])->whereIn('id', $allowedSrIds)->get();

        $pendingCount = (clone $query)->where('status', 'pending')->count();
        $approvedCount = (clone $query)->where('status', 'approved')->count();
        $rejectedCount = (clone $query)->where('status', 'rejected')->count();

        return view('pages.support_team.leave_requests.index', compact(
            'leaves', 'students', 'pendingCount', 'approvedCount', 'rejectedCount'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'leave_scope' => 'required|in:student,staff',
            'student_record_id' => 'nullable|exists:student_records,id',
            'leave_type' => 'required|in:sick,casual,urgent,annual,other',
            'from_date_ad' => 'nullable|date',
            'from_date_bs' => 'nullable|string|max:20',
            'to_date_ad' => 'nullable|date',
            'to_date_bs' => 'nullable|string|max:20',
            'reason' => 'required|string|max:3000',
        ]);

        $scope = $validated['leave_scope'];
        $studentRecordId = $validated['student_record_id'] ?? null;

        if ($scope === 'staff') {
            if (!Qs::userIsStaff()) {
                abort(403, __('Only staff members can apply for staff leave.'));
            }
            $studentRecordId = null;
        } else {
            if ($user->user_type === 'student' && !$studentRecordId) {
                $sr = StudentRecord::where('user_id', $user->id)->first();
                $studentRecordId = $sr ? $sr->id : null;
            }
            if (!$studentRecordId || !$this->erp->canAccessStudentRecord($user, (int) $studentRecordId)) {
                abort(403, __('Unauthorized student selection for leave request.'));
            }
        }

        $fromDates = $this->erp->resolveDualDates($validated['from_date_ad'] ?? null, $validated['from_date_bs'] ?? null, true);
        $toDates = $this->erp->resolveDualDates($validated['to_date_ad'] ?? null, $validated['to_date_bs'] ?? null, true);

        $fromAd = Carbon::parse($fromDates['ad']);
        $toAd = Carbon::parse($toDates['ad']);
        if ($toAd->lt($fromAd)) {
            $toAd = $fromAd->copy();
            $toDates = $fromDates;
        }
        $days = max(1, $fromAd->diffInDays($toAd) + 1);

        $leave = LeaveRequest::create([
            'user_id' => $user->id,
            'student_record_id' => $studentRecordId,
            'leave_scope' => $scope,
            'leave_type' => $validated['leave_type'],
            'from_date_ad' => $fromDates['ad'],
            'from_date_bs' => $fromDates['bs'],
            'to_date_ad' => $toDates['ad'],
            'to_date_bs' => $toDates['bs'],
            'total_days' => $days,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        $this->erp->audit('leave.apply', 'administration', "Submitted {$scope} leave request ({$days} day(s))", $leave);

        return back()->with('flash_success', __('Leave application submitted successfully.'));
    }

    public function review(Request $request, $id)
    {
        $leave = LeaveRequest::findOrFail($id);

        if ($leave->leave_scope === 'staff') {
            if (!Qs::userIsTeamSA()) {
                abort(403, __('Only administrators can review staff leave requests.'));
            }
        } elseif (!Qs::userIsTeamSAT()) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'review_remarks' => 'nullable|string|max:1000',
        ]);

        $leave->update([
            'status' => $validated['status'],
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_remarks' => $validated['review_remarks'] ?? null,
        ]);

        $this->erp->audit('leave.review', 'administration', "Marked leave #{$leave->id} as {$validated['status']}", $leave);

        return back()->with('flash_success', __('Leave request updated to :status.', ['status' => $validated['status']]));
    }
}
