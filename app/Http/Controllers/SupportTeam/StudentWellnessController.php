<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\StudentGroup;
use App\Models\StudentGroupMember;
use App\Models\StudentRecord;
use App\Models\StudentWellnessLog;
use App\Services\ErpSupportService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentWellnessController extends Controller
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

        $logQuery = StudentWellnessLog::with(['studentRecord.user', 'studentRecord.my_class', 'recorder'])
            ->orderBy('log_date_ad', 'desc')
            ->orderBy('id', 'desc');

        if ($allowedSrIds !== null) {
            $logQuery->whereIn('student_record_id', $allowedSrIds ?: [0]);
        }

        if ($request->filled('category')) {
            $logQuery->where('category', $request->query('category'));
        }

        $logs = $logQuery->paginate(15)->appends($request->query());

        $groupsQuery = StudentGroup::with(['incharge', 'members.studentRecord.user'])->orderBy('name');
        $groups = $groupsQuery->get();

        $students = $allowedSrIds === null
            ? StudentRecord::with(['user', 'my_class'])->where('grad', 0)->orderBy('adm_no')->get()
            : StudentRecord::with(['user', 'my_class'])->whereIn('id', $allowedSrIds)->get();

        $staffUsers = Qs::userIsTeamSA()
            ? User::whereIn('user_type', Qs::getStaff())->orderBy('name')->get()
            : collect();

        $meritPoints = (clone $logQuery)->where('category', 'merit')->sum('points');
        $infirmaryCount = (clone $logQuery)->whereIn('category', ['infirmary', 'health_check'])->count();
        $ecaCount = (clone $logQuery)->where('category', 'eca')->count();

        return view('pages.support_team.wellness.index', compact(
            'logs', 'groups', 'students', 'staffUsers',
            'meritPoints', 'infirmaryCount', 'ecaCount'
        ));
    }

    public function storeLog(Request $request)
    {
        if (!Qs::userIsTeamSAT()) {
            abort(403);
        }

        $validated = $request->validate([
            'student_record_id' => 'required|exists:student_records,id',
            'category' => 'required|in:merit,demerit,infirmary,eca,health_check',
            'title' => 'required|string|max:191',
            'points' => 'nullable|integer|min:-500|max:500',
            'description' => 'nullable|string|max:2000',
            'log_date_ad' => 'nullable|date',
            'log_date_bs' => 'nullable|string|max:20',
            'vitals_summary' => 'nullable|string|max:191',
            'action_taken' => 'nullable|string|max:191',
        ]);

        $dates = $this->erp->resolveDualDates($validated['log_date_ad'] ?? null, $validated['log_date_bs'] ?? null, true);

        $log = StudentWellnessLog::create([
            'student_record_id' => $validated['student_record_id'],
            'recorded_by' => Auth::id(),
            'category' => $validated['category'],
            'title' => $validated['title'],
            'points' => $validated['points'] ?? 0,
            'description' => $validated['description'] ?? null,
            'log_date_ad' => $dates['ad'],
            'log_date_bs' => $dates['bs'],
            'vitals_summary' => $validated['vitals_summary'] ?? null,
            'action_taken' => $validated['action_taken'] ?? null,
        ]);

        $this->erp->audit('wellness.log', 'students', "Added {$log->category} log '{$log->title}'", $log);

        return back()->with('flash_success', __('Student wellness / ECA / merit entry recorded.'));
    }

    public function storeGroup(Request $request)
    {
        if (!Qs::userIsTeamSA()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'category' => 'required|in:house,club,bus,sports,academic,other',
            'description' => 'nullable|string|max:1000',
            'incharge_user_id' => 'nullable|exists:users,id',
        ]);

        StudentGroup::create($validated + ['is_active' => true]);

        return back()->with('flash_success', __('Student group created.'));
    }

    public function addGroupMember(Request $request, $groupId)
    {
        if (!Qs::userIsTeamSA()) {
            abort(403);
        }

        $group = StudentGroup::findOrFail($groupId);
        $validated = $request->validate([
            'student_record_id' => 'required|exists:student_records,id',
            'role_in_group' => 'nullable|string|max:80',
        ]);

        StudentGroupMember::updateOrCreate(
            ['student_group_id' => $group->id, 'student_record_id' => $validated['student_record_id']],
            ['role_in_group' => $validated['role_in_group'] ?? null]
        );

        return back()->with('flash_success', __('Student added to group.'));
    }
}
