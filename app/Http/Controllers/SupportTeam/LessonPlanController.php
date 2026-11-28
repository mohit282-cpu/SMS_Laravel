<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\LessonPlan;
use App\Models\MyClass;
use App\Models\Section;
use App\Models\Subject;
use App\Services\ErpSupportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LessonPlanController extends Controller
{
    protected ErpSupportService $erp;

    public function __construct(ErpSupportService $erp)
    {
        $this->middleware('auth');
        $this->middleware('teamSAT');
        $this->erp = $erp;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = LessonPlan::with(['teacher', 'my_class', 'section', 'subject'])
            ->orderBy('planned_date_ad', 'desc')
            ->orderBy('id', 'desc');

        if ($user->user_type === 'teacher') {
            $query->where('teacher_id', $user->id);
        }

        if ($request->filled('class_id')) {
            $query->where('my_class_id', $request->integer('class_id'));
        }
        if ($request->filled('completion_status')) {
            $query->where('completion_status', $request->query('completion_status'));
        }

        $lessonPlans = $query->paginate(15)->appends($request->query());
        $classes = MyClass::orderBy('name')->get();
        $sections = Section::with('my_class')->orderBy('name')->get();
        $subjects = Subject::with('my_class')->orderBy('name')->get();

        $totalPlans = (clone $query)->toBase()->getCountForPagination();
        $completedCount = (clone $query)->where('completion_status', 'completed')->count();
        $inProgressCount = (clone $query)->where('completion_status', 'in_progress')->count();
        $plannedCount = (clone $query)->where('completion_status', 'planned')->count();

        return view('pages.support_team.lesson_plans.index', compact(
            'lessonPlans', 'classes', 'sections', 'subjects',
            'totalPlans', 'completedCount', 'inProgressCount', 'plannedCount'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'my_class_id' => 'required|exists:my_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'unit_title' => 'required|string|max:191',
            'lesson_topic' => 'required|string|max:191',
            'learning_objectives' => 'nullable|string|max:4000',
            'teaching_aids' => 'nullable|string|max:1000',
            'planned_date_ad' => 'nullable|date',
            'planned_date_bs' => 'nullable|string|max:20',
            'daily_report_notes' => 'nullable|string|max:4000',
        ]);

        $dates = $this->erp->resolveDualDates($validated['planned_date_ad'] ?? null, $validated['planned_date_bs'] ?? null, true);

        LessonPlan::create([
            'teacher_id' => Auth::id(),
            'my_class_id' => $validated['my_class_id'],
            'section_id' => $validated['section_id'] ?? null,
            'subject_id' => $validated['subject_id'] ?? null,
            'unit_title' => $validated['unit_title'],
            'lesson_topic' => $validated['lesson_topic'],
            'learning_objectives' => $validated['learning_objectives'] ?? null,
            'teaching_aids' => $validated['teaching_aids'] ?? null,
            'planned_date_ad' => $dates['ad'],
            'planned_date_bs' => $dates['bs'],
            'completion_status' => 'planned',
            'daily_report_notes' => $validated['daily_report_notes'] ?? null,
        ]);

        return back()->with('flash_success', __('Lesson plan & daily teaching entry saved.'));
    }

    public function updateStatus(Request $request, $id)
    {
        $plan = LessonPlan::findOrFail($id);
        if (!Qs::userIsTeamSA() && (int) $plan->teacher_id !== (int) Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'completion_status' => 'required|in:planned,in_progress,completed',
            'daily_report_notes' => 'nullable|string|max:4000',
        ]);

        $completedAd = $validated['completion_status'] === 'completed' ? now()->toDateString() : null;
        $completedBs = $completedAd ? \App\Helpers\NepaliHelper::adToBs($completedAd) : null;

        $plan->update([
            'completion_status' => $validated['completion_status'],
            'daily_report_notes' => $validated['daily_report_notes'] ?? $plan->daily_report_notes,
            'completed_date_ad' => $completedAd,
            'completed_date_bs' => $completedBs,
        ]);

        return back()->with('flash_success', __('Lesson progress updated.'));
    }

    public function destroy($id)
    {
        $plan = LessonPlan::findOrFail($id);
        if (!Qs::userIsTeamSA() && (int) $plan->teacher_id !== (int) Auth::id()) {
            abort(403);
        }
        $plan->delete();

        return back()->with('flash_success', __('Lesson plan deleted.'));
    }
}
