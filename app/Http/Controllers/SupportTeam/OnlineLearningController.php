<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\ForumReply;
use App\Models\ForumThread;
use App\Models\MyClass;
use App\Models\OnlineClass;
use App\Models\OnlineQuiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Section;
use App\Models\StudentRecord;
use App\Models\Subject;
use App\Services\ErpSupportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OnlineLearningController extends Controller
{
    protected ErpSupportService $erp;

    public function __construct(ErpSupportService $erp)
    {
        $this->middleware('auth');
        $this->erp = $erp;
    }

    public function classesIndex()
    {
        $user = Auth::user();
        $allowedSrIds = $this->erp->accessibleStudentRecordIds($user);

        $query = OnlineClass::with(['teacher', 'my_class', 'section', 'subject'])
            ->orderBy('scheduled_date_ad', 'desc')
            ->orderBy('id', 'desc');

        if ($allowedSrIds !== null) {
            $classIds = StudentRecord::whereIn('id', $allowedSrIds)->pluck('my_class_id')->unique()->all();
            $query->whereIn('my_class_id', $classIds ?: [0]);
        }

        $onlineClasses = $query->paginate(15);
        $classes = MyClass::orderBy('name')->get();
        $sections = Section::with('my_class')->orderBy('name')->get();
        $subjects = Subject::with('my_class')->orderBy('name')->get();

        $totalSessions = (clone $query)->toBase()->getCountForPagination();
        $liveCount = OnlineClass::where('status', 'live')->count();
        $scheduledCount = OnlineClass::where('status', 'scheduled')->count();

        return view('pages.support_team.elearning.classes', compact(
            'onlineClasses', 'classes', 'sections', 'subjects',
            'totalSessions', 'liveCount', 'scheduledCount'
        ));
    }

    public function storeClass(Request $request)
    {
        if (!Qs::userIsTeamSAT()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:191',
            'platform' => 'required|in:zoom,google_meet,ms_teams,jitsi,other',
            'meeting_url' => 'required|url|max:500',
            'meeting_code' => 'nullable|string|max:100',
            'meeting_passcode' => 'nullable|string|max:60',
            'my_class_id' => 'required|exists:my_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'scheduled_date_ad' => 'nullable|date',
            'scheduled_date_bs' => 'nullable|string|max:20',
            'start_time' => 'required|string|max:20',
            'duration_minutes' => 'required|integer|min:10|max:360',
            'description' => 'nullable|string|max:2000',
        ]);

        $dates = $this->erp->resolveDualDates($validated['scheduled_date_ad'] ?? null, $validated['scheduled_date_bs'] ?? null, true);

        OnlineClass::create([
            'teacher_id' => Auth::id(),
            'my_class_id' => $validated['my_class_id'],
            'section_id' => $validated['section_id'] ?? null,
            'subject_id' => $validated['subject_id'] ?? null,
            'title' => $validated['title'],
            'platform' => $validated['platform'],
            'meeting_url' => $validated['meeting_url'],
            'meeting_code' => $validated['meeting_code'] ?? null,
            'meeting_passcode' => $validated['meeting_passcode'] ?? null,
            'scheduled_date_ad' => $dates['ad'],
            'scheduled_date_bs' => $dates['bs'],
            'start_time' => $validated['start_time'],
            'duration_minutes' => $validated['duration_minutes'],
            'status' => 'scheduled',
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('flash_success', __('Online class scheduled cleanly.'));
    }

    public function updateClassStatus(Request $request, $id)
    {
        if (!Qs::userIsTeamSAT()) {
            abort(403);
        }
        $oc = OnlineClass::findOrFail($id);
        $validated = $request->validate(['status' => 'required|in:scheduled,live,completed,cancelled']);
        $oc->update(['status' => $validated['status']]);

        return back()->with('flash_success', __('Online class status updated.'));
    }

    public function quizzesIndex()
    {
        $user = Auth::user();
        $allowedSrIds = $this->erp->accessibleStudentRecordIds($user);

        $query = OnlineQuiz::with(['creator', 'my_class', 'section', 'subject'])
            ->withCount(['questions', 'attempts'])
            ->orderBy('id', 'desc');

        if ($allowedSrIds !== null) {
            $classIds = StudentRecord::whereIn('id', $allowedSrIds)->pluck('my_class_id')->unique()->all();
            $query->where('is_published', true)->whereIn('my_class_id', $classIds ?: [0]);
        }

        $quizzes = $query->paginate(15);
        $classes = MyClass::orderBy('name')->get();
        $sections = Section::with('my_class')->orderBy('name')->get();
        $subjects = Subject::with('my_class')->orderBy('name')->get();

        $myAttempts = $allowedSrIds !== null
            ? QuizAttempt::whereIn('student_record_id', $allowedSrIds)->get()->keyBy('online_quiz_id')
            : collect();

        return view('pages.support_team.elearning.quizzes', compact('quizzes', 'classes', 'sections', 'subjects', 'myAttempts'));
    }

    public function storeQuiz(Request $request)
    {
        if (!Qs::userIsTeamSAT()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:191',
            'description' => 'nullable|string|max:2000',
            'my_class_id' => 'required|exists:my_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'duration_minutes' => 'required|integer|min:5|max:240',
            'pass_marks' => 'required|integer|min:1|max:100',
            'scheduled_date_ad' => 'nullable|date',
            'scheduled_date_bs' => 'nullable|string|max:20',
            'is_published' => 'nullable|boolean',
        ]);

        $dates = $this->erp->resolveDualDates($validated['scheduled_date_ad'] ?? null, $validated['scheduled_date_bs'] ?? null, true);

        $quiz = OnlineQuiz::create([
            'created_by' => Auth::id(),
            'my_class_id' => $validated['my_class_id'],
            'section_id' => $validated['section_id'] ?? null,
            'subject_id' => $validated['subject_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'duration_minutes' => $validated['duration_minutes'],
            'pass_marks' => $validated['pass_marks'],
            'scheduled_date_ad' => $dates['ad'],
            'scheduled_date_bs' => $dates['bs'],
            'is_published' => (bool) ($validated['is_published'] ?? false),
        ]);

        return redirect()->route('elearning.quizzes.show', $quiz->id)->with('flash_success', __('Objective quiz created. Add questions below.'));
    }

    public function showQuiz($id)
    {
        $user = Auth::user();
        $quiz = OnlineQuiz::with(['creator', 'my_class', 'section', 'subject', 'questions', 'attempts.studentRecord.user'])
            ->findOrFail($id);

        $allowedSrIds = $this->erp->accessibleStudentRecordIds($user);
        if ($allowedSrIds !== null) {
            $classIds = StudentRecord::whereIn('id', $allowedSrIds)->pluck('my_class_id')->all();
            if (!$quiz->is_published || !in_array((int) $quiz->my_class_id, array_map('intval', $classIds), true)) {
                abort(403);
            }
        }

        $mySr = $user->user_type === 'student' ? StudentRecord::where('user_id', $user->id)->first() : null;
        $myAttempt = $mySr ? $quiz->attempts->firstWhere('student_record_id', $mySr->id) : null;
        $visibleAttempts = $allowedSrIds === null
            ? $quiz->attempts
            : $quiz->attempts->whereIn('student_record_id', $allowedSrIds);

        return view('pages.support_team.elearning.quiz_show', compact('quiz', 'mySr', 'myAttempt', 'visibleAttempts'));
    }

    public function addQuestion(Request $request, $id)
    {
        if (!Qs::userIsTeamSAT()) {
            abort(403);
        }
        $quiz = OnlineQuiz::findOrFail($id);

        $validated = $request->validate([
            'question_text' => 'required|string|max:2000',
            'option_a' => 'required|string|max:500',
            'option_b' => 'required|string|max:500',
            'option_c' => 'nullable|string|max:500',
            'option_d' => 'nullable|string|max:500',
            'correct_option' => 'required|in:a,b,c,d',
            'marks' => 'required|integer|min:1|max:20',
            'explanation' => 'nullable|string|max:1000',
        ]);

        $quiz->questions()->create($validated);

        return back()->with('flash_success', __('Question added to quiz.'));
    }

    public function toggleQuizPublish($id)
    {
        if (!Qs::userIsTeamSAT()) {
            abort(403);
        }
        $quiz = OnlineQuiz::findOrFail($id);
        $quiz->update(['is_published' => !$quiz->is_published]);

        return back()->with('flash_success', $quiz->is_published ? __('Quiz published to students.') : __('Quiz moved back to draft.'));
    }

    public function attemptQuiz(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->user_type !== 'student') {
            abort(403, __('Only students can submit objective quiz attempts.'));
        }

        $quiz = OnlineQuiz::with('questions')->where('is_published', true)->findOrFail($id);
        $sr = StudentRecord::where('user_id', $user->id)->firstOrFail();
        if ((int) $sr->my_class_id !== (int) $quiz->my_class_id) {
            abort(403);
        }

        $submitted = (array) $request->input('answers', []);
        $score = 0;
        $totalMarks = 0;
        foreach ($quiz->questions as $q) {
            $totalMarks += (int) $q->marks;
            $picked = isset($submitted[$q->id]) ? strtolower((string) $submitted[$q->id]) : null;
            if ($picked && $picked === strtolower((string) $q->correct_option)) {
                $score += (int) $q->marks;
            }
        }

        $percentage = $totalMarks > 0 ? round(($score / $totalMarks) * 100, 2) : 0;
        $passed = $percentage >= (float) $quiz->pass_marks;

        QuizAttempt::updateOrCreate(
            ['online_quiz_id' => $quiz->id, 'student_record_id' => $sr->id],
            [
                'answers_json' => $submitted,
                'score' => $score,
                'total_marks' => $totalMarks,
                'percentage' => $percentage,
                'passed' => $passed,
                'submitted_at' => now(),
            ]
        );

        return back()->with('flash_success', __('Quiz submitted and auto-graded! Score: :score / :total (:pct%)', [
            'score' => $score,
            'total' => $totalMarks,
            'pct' => $percentage,
        ]));
    }

    public function forumIndex()
    {
        $threads = ForumThread::with(['author', 'my_class', 'subject', 'replies.author'])
            ->withCount('replies')
            ->orderBy('is_pinned', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);
        $classes = MyClass::orderBy('name')->get();
        $subjects = Subject::with('my_class')->orderBy('name')->get();

        return view('pages.support_team.elearning.forum', compact('threads', 'classes', 'subjects'));
    }

    public function storeThread(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:191',
            'body' => 'required|string|max:5000',
            'my_class_id' => 'nullable|exists:my_classes,id',
            'subject_id' => 'nullable|exists:subjects,id',
        ]);

        ForumThread::create([
            'user_id' => Auth::id(),
            'my_class_id' => $validated['my_class_id'] ?? null,
            'subject_id' => $validated['subject_id'] ?? null,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'is_pinned' => Qs::userIsTeamSA() && $request->boolean('is_pinned'),
        ]);

        return back()->with('flash_success', __('Discussion topic created.'));
    }

    public function replyThread(Request $request, $id)
    {
        $thread = ForumThread::findOrFail($id);
        if ($thread->is_locked && !Qs::userIsTeamSAT()) {
            return back()->with('flash_danger', __('This thread is locked by staff.'));
        }

        $validated = $request->validate(['body' => 'required|string|max:3000']);
        ForumReply::create([
            'forum_thread_id' => $thread->id,
            'user_id' => Auth::id(),
            'body' => $validated['body'],
        ]);

        return back()->with('flash_success', __('Reply posted.'));
    }
}
