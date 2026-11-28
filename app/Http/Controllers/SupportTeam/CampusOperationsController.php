<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\CanteenLunchLog;
use App\Models\ComplaintFeedback;
use App\Models\Dorm;
use App\Models\HostelAllocation;
use App\Models\StudentRecord;
use App\Models\VisitorLog;
use App\Services\ErpSupportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampusOperationsController extends Controller
{
    protected ErpSupportService $erp;

    public function __construct(ErpSupportService $erp)
    {
        $this->middleware('auth');
        $this->erp = $erp;
    }

    public function visitorsIndex()
    {
        if (!Qs::userIsStaff()) {
            abort(403);
        }

        $visitors = VisitorLog::with(['studentRecord.user', 'recorder'])
            ->orderBy('visit_date_ad', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);
        $students = StudentRecord::with(['user', 'my_class'])->where('grad', 0)->orderBy('adm_no')->get();

        $activeInside = VisitorLog::whereNull('check_out_time')->count();
        $parentVisits = VisitorLog::where('visitor_type', 'parent')->count();

        return view('pages.support_team.operations.visitors', compact(
            'visitors', 'students', 'activeInside', 'parentVisits'
        ));
    }

    public function storeVisitor(Request $request)
    {
        if (!Qs::userIsStaff()) {
            abort(403);
        }

        $validated = $request->validate([
            'visitor_name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:40',
            'visitor_type' => 'required|in:parent,vendor,guest,official,other',
            'student_record_id' => 'nullable|exists:student_records,id',
            'person_to_meet' => 'nullable|string|max:150',
            'purpose' => 'required|string|max:255',
            'id_card_no' => 'nullable|string|max:80',
            'visit_date_ad' => 'nullable|date',
            'visit_date_bs' => 'nullable|string|max:20',
            'check_in_time' => 'nullable|string|max:20',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $dates = $this->erp->resolveDualDates($validated['visit_date_ad'] ?? null, $validated['visit_date_bs'] ?? null, true);

        VisitorLog::create([
            'visitor_name' => $validated['visitor_name'],
            'phone' => $validated['phone'] ?? null,
            'visitor_type' => $validated['visitor_type'],
            'student_record_id' => $validated['student_record_id'] ?? null,
            'person_to_meet' => $validated['person_to_meet'] ?? null,
            'purpose' => $validated['purpose'],
            'id_card_no' => $validated['id_card_no'] ?? null,
            'visit_date_ad' => $dates['ad'],
            'visit_date_bs' => $dates['bs'],
            'check_in_time' => !empty($validated['check_in_time']) ? $validated['check_in_time'] : now()->format('H:i'),
            'remarks' => $validated['remarks'] ?? null,
            'recorded_by' => Auth::id(),
        ]);

        return back()->with('flash_success', __('Visitor entry recorded at reception.'));
    }

    public function checkoutVisitor($id)
    {
        if (!Qs::userIsStaff()) {
            abort(403);
        }

        $visit = VisitorLog::findOrFail($id);
        $visit->update(['check_out_time' => now()->format('H:i')]);

        return back()->with('flash_success', __('Visitor checked out cleanly.'));
    }

    public function feedbackIndex()
    {
        $user = Auth::user();
        $query = ComplaintFeedback::with(['submitter', 'studentRecord.user', 'handler'])->orderBy('id', 'desc');

        if (!Qs::userIsTeamSA()) {
            $query->where('submitted_by', $user->id);
        }

        $entries = $query->paginate(15);
        $allowedSrIds = $this->erp->accessibleStudentRecordIds($user);
        $students = $allowedSrIds === null
            ? StudentRecord::with(['user', 'my_class'])->where('grad', 0)->orderBy('adm_no')->get()
            : StudentRecord::with(['user', 'my_class'])->whereIn('id', $allowedSrIds)->get();

        $openCount = (clone $query)->whereIn('status', ['open', 'in_progress'])->count();
        $resolvedCount = (clone $query)->whereIn('status', ['resolved', 'closed'])->count();

        return view('pages.support_team.operations.feedback', compact(
            'entries', 'students', 'openCount', 'resolvedCount'
        ));
    }

    public function storeFeedback(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'entry_type' => 'required|in:complaint,feedback,suggestion,appreciation,discipline_case',
            'student_record_id' => 'nullable|exists:student_records,id',
            'subject' => 'required|string|max:191',
            'description' => 'required|string|max:4000',
            'priority' => 'required|in:low,medium,high',
        ]);

        if (!empty($validated['student_record_id']) && !$this->erp->canAccessStudentRecord($user, (int) $validated['student_record_id'])) {
            abort(403);
        }

        ComplaintFeedback::create([
            'submitted_by' => $user->id,
            'student_record_id' => $validated['student_record_id'] ?? null,
            'entry_type' => $validated['entry_type'],
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'priority' => $validated['priority'],
            'status' => 'open',
        ]);

        return back()->with('flash_success', __('Feedback / case ticket submitted.'));
    }

    public function updateFeedbackStatus(Request $request, $id)
    {
        if (!Qs::userIsTeamSA()) {
            abort(403);
        }

        $entry = ComplaintFeedback::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
            'resolution_notes' => 'nullable|string|max:2000',
        ]);

        $entry->update([
            'status' => $validated['status'],
            'resolution_notes' => $validated['resolution_notes'] ?? $entry->resolution_notes,
            'handled_by' => Auth::id(),
            'resolved_at' => in_array($validated['status'], ['resolved', 'closed'], true) ? now() : null,
        ]);

        return back()->with('flash_success', __('Ticket status updated.'));
    }

    public function hostelCanteenIndex()
    {
        $user = Auth::user();
        $allowedSrIds = $this->erp->accessibleStudentRecordIds($user);

        $allocQuery = HostelAllocation::with(['studentRecord.user', 'dorm'])->orderBy('id', 'desc');
        $lunchQuery = CanteenLunchLog::with(['studentRecord.user', 'marker'])->orderBy('log_date_ad', 'desc')->orderBy('id', 'desc');

        if ($allowedSrIds !== null) {
            $allocQuery->whereIn('student_record_id', $allowedSrIds ?: [0]);
            $lunchQuery->whereIn('student_record_id', $allowedSrIds ?: [0]);
        }

        $allocations = $allocQuery->paginate(15, ['*'], 'hostel_page');
        $lunchLogs = $lunchQuery->paginate(15, ['*'], 'canteen_page');
        $dorms = Dorm::orderBy('name')->get();
        $students = $allowedSrIds === null
            ? StudentRecord::with(['user', 'my_class'])->where('grad', 0)->orderBy('adm_no')->get()
            : collect();

        return view('pages.support_team.operations.hostel_canteen', compact(
            'allocations', 'lunchLogs', 'dorms', 'students'
        ));
    }

    public function storeHostelAllocation(Request $request)
    {
        if (!Qs::userIsTeamSA()) {
            abort(403);
        }

        $validated = $request->validate([
            'student_record_id' => 'required|exists:student_records,id',
            'dorm_id' => 'required|exists:dorms,id',
            'room_no' => 'required|string|max:50',
            'bed_no' => 'nullable|string|max:50',
            'monthly_fee' => 'required|numeric|min:0|max:100000',
            'meal_plan' => 'nullable|string|max:80',
            'assigned_date_bs' => 'nullable|string|max:20',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $dates = $this->erp->resolveDualDates(null, $validated['assigned_date_bs'] ?? null, true);

        HostelAllocation::create([
            'student_record_id' => $validated['student_record_id'],
            'dorm_id' => $validated['dorm_id'],
            'room_no' => $validated['room_no'],
            'bed_no' => $validated['bed_no'] ?? null,
            'monthly_fee' => $validated['monthly_fee'],
            'meal_plan' => $validated['meal_plan'] ?? 'Full Board',
            'assigned_date_bs' => $dates['bs'],
            'status' => 'active',
            'remarks' => $validated['remarks'] ?? null,
        ]);

        StudentRecord::where('id', $validated['student_record_id'])->update([
            'dorm_id' => $validated['dorm_id'],
            'dorm_room_no' => $validated['room_no'],
        ]);

        return back()->with('flash_success', __('Hostel room and bed allocated cleanly.'));
    }

    public function storeLunchLog(Request $request)
    {
        if (!Qs::userIsTeamSAT()) {
            abort(403);
        }

        $validated = $request->validate([
            'student_record_id' => 'required|exists:student_records,id',
            'meal_type' => 'required|in:lunch,snacks,breakfast',
            'status' => 'required|in:taken,absent',
            'unit_charge' => 'required|numeric|min:0|max:10000',
            'log_date_ad' => 'nullable|date',
            'log_date_bs' => 'nullable|string|max:20',
            'remarks' => 'nullable|string|max:191',
        ]);

        $dates = $this->erp->resolveDualDates($validated['log_date_ad'] ?? null, $validated['log_date_bs'] ?? null, true);

        CanteenLunchLog::create([
            'student_record_id' => $validated['student_record_id'],
            'log_date_ad' => $dates['ad'],
            'log_date_bs' => $dates['bs'],
            'meal_type' => $validated['meal_type'],
            'status' => $validated['status'],
            'unit_charge' => $validated['unit_charge'],
            'remarks' => $validated['remarks'] ?? null,
            'marked_by' => Auth::id(),
        ]);

        return back()->with('flash_success', __('Canteen / lunch attendance logged.'));
    }
}
