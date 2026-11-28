<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\NepaliHelper;
use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\MyClass;
use App\Models\Section;
use App\Models\StudentRecord;
use App\Models\Subject;
use App\Services\ErpSupportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class HomeworkController extends Controller
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

        $query = Homework::with(['teacher', 'my_class', 'section', 'subject'])
            ->withCount('submissions')
            ->orderBy('assigned_date_ad', 'desc')
            ->orderBy('id', 'desc');

        if ($allowedSrIds !== null) {
            $classIds = StudentRecord::whereIn('id', $allowedSrIds)->pluck('my_class_id')->unique()->all();
            $query->where('is_published', true)->whereIn('my_class_id', $classIds ?: [0]);
        }

        if ($request->filled('class_id')) {
            $query->where('my_class_id', $request->integer('class_id'));
        }
        if ($request->filled('homework_type')) {
            $query->where('homework_type', $request->query('homework_type'));
        }

        $homeworks = $query->paginate(15)->appends($request->query());
        $classes = MyClass::orderBy('name')->get();
        $subjects = Subject::with('my_class')->orderBy('name')->get();
        $sections = Section::with('my_class')->orderBy('name')->get();

        $totalHomeworks = (clone $query)->toBase()->getCountForPagination();
        $diaryCount = Homework::where('homework_type', 'daily_diary')->count();
        $pendingEvaluations = HomeworkSubmission::where('status', 'submitted')->count();
        $todayBs = NepaliHelper::adToBs(now()->toDateString());

        return view('pages.support_team.homework.index', compact(
            'homeworks', 'classes', 'subjects', 'sections',
            'totalHomeworks', 'diaryCount', 'pendingEvaluations', 'todayBs'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!Qs::userIsTeamSAT()) {
            abort(403, __('Only teachers and administrators can assign homework.'));
        }

        $validated = $request->validate([
            'title' => 'required|string|max:191',
            'description' => 'nullable|string|max:5000',
            'homework_type' => 'required|in:homework,assignment,daily_diary',
            'my_class_id' => 'required|exists:my_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'assigned_date_ad' => 'nullable|date',
            'assigned_date_bs' => 'nullable|string|max:20',
            'due_date_ad' => 'nullable|date',
            'due_date_bs' => 'nullable|string|max:20',
            'reference_link' => 'nullable|url|max:500',
            'max_marks' => 'nullable|integer|min:1|max:500',
            'attachment' => 'nullable|file|max:10240|mimes:pdf,doc,docx,ppt,pptx,txt,zip,jpg,jpeg,png',
        ]);

        if (!empty($validated['section_id']) && !Section::where('id', $validated['section_id'])->where('my_class_id', $validated['my_class_id'])->exists()) {
            return back()->withInput()->with('flash_danger', __('Selected section does not belong to the chosen class.'));
        }
        if (!empty($validated['subject_id']) && !Subject::where('id', $validated['subject_id'])->where('my_class_id', $validated['my_class_id'])->exists()) {
            return back()->withInput()->with('flash_danger', __('Selected subject does not belong to the chosen class.'));
        }

        $assignedDates = $this->erp->resolveDualDates($validated['assigned_date_ad'] ?? null, $validated['assigned_date_bs'] ?? null, true);
        $dueDates = $this->erp->resolveDualDates($validated['due_date_ad'] ?? null, $validated['due_date_bs'] ?? null, false);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $stored = $this->erp->storePrivateUpload($request->file('attachment'), 'homeworks');
            $attachmentPath = $stored['path'];
        }

        $hw = Homework::create([
            'teacher_id' => $user->id,
            'my_class_id' => $validated['my_class_id'],
            'section_id' => $validated['section_id'] ?? null,
            'subject_id' => $validated['subject_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'homework_type' => $validated['homework_type'],
            'attachment_path' => $attachmentPath,
            'reference_link' => $validated['reference_link'] ?? null,
            'assigned_date_ad' => $assignedDates['ad'],
            'assigned_date_bs' => $assignedDates['bs'],
            'due_date_ad' => $dueDates['ad'],
            'due_date_bs' => $dueDates['bs'],
            'max_marks' => $validated['max_marks'] ?? 10,
            'is_published' => true,
        ]);

        $this->erp->audit('homework.create', 'academics', "Posted homework '{$hw->title}'", $hw, ['class_id' => $hw->my_class_id]);

        return redirect()->route('homework.index')->with('flash_success', __('Homework / Daily Diary published successfully.'));
    }

    public function show($id)
    {
        $user = Auth::user();
        $homework = Homework::with(['teacher', 'my_class', 'section', 'subject', 'submissions.studentRecord.user', 'submissions.evaluator'])
            ->findOrFail($id);

        $allowedSrIds = $this->erp->accessibleStudentRecordIds($user);
        if ($allowedSrIds !== null) {
            $allowedClasses = StudentRecord::whereIn('id', $allowedSrIds)->pluck('my_class_id')->all();
            if (!in_array((int) $homework->my_class_id, array_map('intval', $allowedClasses), true) || !$homework->is_published) {
                abort(403);
            }
        }

        $myStudentRecord = $user->user_type === 'student'
            ? StudentRecord::where('user_id', $user->id)->first()
            : null;
        $mySubmission = $myStudentRecord
            ? $homework->submissions->firstWhere('student_record_id', $myStudentRecord->id)
            : null;

        $visibleSubmissions = $allowedSrIds === null
            ? $homework->submissions
            : $homework->submissions->whereIn('student_record_id', $allowedSrIds);

        return view('pages.support_team.homework.show', compact('homework', 'myStudentRecord', 'mySubmission', 'visibleSubmissions'));
    }

    public function submit(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->user_type !== 'student') {
            abort(403, __('Only students can submit homework answers.'));
        }

        $homework = Homework::where('is_published', true)->findOrFail($id);
        $sr = StudentRecord::where('user_id', $user->id)->firstOrFail();
        if ((int) $sr->my_class_id !== (int) $homework->my_class_id) {
            abort(403);
        }

        $validated = $request->validate([
            'submission_text' => 'required_without:attachment|nullable|string|max:5000',
            'attachment' => 'nullable|file|max:10240|mimes:pdf,doc,docx,txt,zip,jpg,jpeg,png',
        ]);

        $existing = HomeworkSubmission::where('homework_id', $homework->id)
            ->where('student_record_id', $sr->id)
            ->first();

        $attachmentPath = $existing ? $existing->attachment_path : null;
        if ($request->hasFile('attachment')) {
            $stored = $this->erp->storePrivateUpload($request->file('attachment'), 'homework_submissions');
            $attachmentPath = $stored['path'];
        }

        $isLate = $homework->due_date_ad && now()->startOfDay()->gt($homework->due_date_ad);

        HomeworkSubmission::updateOrCreate(
            ['homework_id' => $homework->id, 'student_record_id' => $sr->id],
            [
                'submission_text' => $validated['submission_text'] ?? null,
                'attachment_path' => $attachmentPath,
                'status' => $isLate ? 'late' : 'submitted',
                'submitted_at' => now(),
            ]
        );

        return back()->with('flash_success', __('Homework submitted successfully.'));
    }

    public function evaluate(Request $request, $submissionId)
    {
        if (!Qs::userIsTeamSAT()) {
            abort(403);
        }

        $submission = HomeworkSubmission::with('homework')->findOrFail($submissionId);
        $max = max(1, (int) ($submission->homework->max_marks ?? 100));

        $validated = $request->validate([
            'status' => 'required|in:evaluated,incomplete,submitted',
            'marks_obtained' => "nullable|numeric|min:0|max:{$max}",
            'teacher_remarks' => 'nullable|string|max:2000',
        ]);

        $submission->update([
            'status' => $validated['status'],
            'marks_obtained' => $validated['marks_obtained'] ?? null,
            'teacher_remarks' => $validated['teacher_remarks'] ?? null,
            'evaluated_by' => Auth::id(),
            'evaluated_at' => now(),
        ]);

        return back()->with('flash_success', __('Homework evaluation saved.'));
    }

    public function download($id)
    {
        $user = Auth::user();
        $hw = Homework::findOrFail($id);
        $allowedSrIds = $this->erp->accessibleStudentRecordIds($user);
        if ($allowedSrIds !== null) {
            $allowedClasses = StudentRecord::whereIn('id', $allowedSrIds)->pluck('my_class_id')->all();
            if (!in_array((int) $hw->my_class_id, array_map('intval', $allowedClasses), true)) {
                abort(403);
            }
        }
        if (!$hw->attachment_path || !Storage::disk('local')->exists($hw->attachment_path)) {
            abort(404);
        }

        return Storage::disk('local')->download($hw->attachment_path);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $hw = Homework::findOrFail($id);
        if (!Qs::userIsTeamSA() && (int) $hw->teacher_id !== (int) $user->id) {
            abort(403);
        }
        if ($hw->attachment_path) {
            Storage::disk('local')->delete($hw->attachment_path);
        }
        $hw->delete();

        return back()->with('flash_success', __('Homework entry removed.'));
    }
}
