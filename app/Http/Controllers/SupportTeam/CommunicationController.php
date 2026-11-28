<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\PortalMessage;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Services\ErpSupportService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommunicationController extends Controller
{
    protected ErpSupportService $erp;

    public function __construct(ErpSupportService $erp)
    {
        $this->middleware('auth');
        $this->erp = $erp;
    }

    public function messagesIndex()
    {
        $user = Auth::user();
        $inbox = PortalMessage::with(['sender', 'recipient'])
            ->where(function ($q) use ($user) {
                $q->where('recipient_id', $user->id)->orWhere('sender_id', $user->id);
            })
            ->orderBy('id', 'desc')
            ->paginate(20);

        PortalMessage::where('recipient_id', $user->id)->whereNull('read_at')->update(['read_at' => now()]);

        $recipients = $user->user_type === 'student' || $user->user_type === 'parent'
            ? User::whereIn('user_type', ['super_admin', 'admin', 'teacher', 'accountant'])->orderBy('name')->get()
            : User::where('id', '!=', $user->id)->orderBy('name')->get();

        return view('pages.support_team.communication.messages', compact('inbox', 'recipients'));
    }

    public function sendMessage(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'recipient_id' => 'required|exists:users,id|not_in:' . $user->id,
            'subject' => 'required|string|max:191',
            'body' => 'required|string|max:5000',
        ]);

        if (in_array($user->user_type, ['student', 'parent'], true)) {
            $recipient = User::findOrFail($validated['recipient_id']);
            if (!in_array($recipient->user_type, ['super_admin', 'admin', 'teacher', 'accountant'], true)) {
                abort(403, __('Students and parents can message school staff directly.'));
            }
        }

        PortalMessage::create([
            'sender_id' => $user->id,
            'recipient_id' => $validated['recipient_id'],
            'subject' => $validated['subject'],
            'body' => $validated['body'],
        ]);

        return back()->with('flash_success', __('Direct message sent.'));
    }

    public function surveysIndex()
    {
        $user = Auth::user();
        $query = Survey::with('creator')->withCount('responses')->orderBy('id', 'desc');
        if (!Qs::userIsTeamSA()) {
            $query->where('is_active', true)
                ->where(function ($q) use ($user) {
                    $q->where('target_role', 'all')->orWhere('target_role', $user->user_type);
                });
        }

        $surveys = $query->paginate(10);
        $myResponses = SurveyResponse::where('user_id', $user->id)->get()->keyBy('survey_id');

        return view('pages.support_team.communication.surveys', compact('surveys', 'myResponses'));
    }

    public function storeSurvey(Request $request)
    {
        if (!Qs::userIsTeamSA()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:191',
            'description' => 'nullable|string|max:2000',
            'target_role' => 'required|in:all,parent,student,teacher,staff',
            'question_1' => 'required|string|max:255',
            'question_2' => 'nullable|string|max:255',
            'question_3' => 'nullable|string|max:255',
            'closes_at' => 'nullable|date',
        ]);

        Survey::create($validated + [
            'created_by' => Auth::id(),
            'is_active' => true,
        ]);

        return back()->with('flash_success', __('Institutional survey published.'));
    }

    public function submitSurveyResponse(Request $request, $surveyId)
    {
        $survey = Survey::where('is_active', true)->findOrFail($surveyId);
        $user = Auth::user();
        if ($survey->target_role !== 'all' && $survey->target_role !== $user->user_type && !Qs::userIsTeamSA()) {
            abort(403);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'answer_1' => 'required|string|max:2000',
            'answer_2' => 'nullable|string|max:2000',
            'answer_3' => 'nullable|string|max:2000',
            'comments' => 'nullable|string|max:2000',
        ]);

        SurveyResponse::updateOrCreate(
            ['survey_id' => $survey->id, 'user_id' => $user->id],
            $validated
        );

        return back()->with('flash_success', __('Survey response recorded. Thank you!'));
    }

    public function notificationsIndex()
    {
        if (!Qs::userIsTeamSA()) {
            abort(403);
        }

        $logs = NotificationLog::with(['sender', 'recipient'])->orderBy('id', 'desc')->paginate(20);

        $todayMd = now()->format('-m-d');
        $birthdayUsers = User::whereNotNull('dob')
            ->where('dob', 'like', '%' . $todayMd)
            ->orderBy('name')
            ->get();

        return view('pages.support_team.communication.notifications', compact('logs', 'birthdayUsers'));
    }

    public function dispatchBroadcast(Request $request)
    {
        if (!Qs::userIsTeamSA()) {
            abort(403);
        }

        $validated = $request->validate([
            'channel' => 'required|in:in_app,web_push,sms_queued,email_queued',
            'category' => 'required|string|max:60',
            'target_role' => 'required|in:all,parent,student,teacher,accountant,librarian',
            'title' => 'required|string|max:191',
            'message' => 'required|string|max:2000',
        ]);

        $status = in_array($validated['channel'], ['sms_queued', 'email_queued'], true) ? 'queued' : 'sent';

        $log = NotificationLog::create([
            'sender_id' => Auth::id(),
            'channel' => $validated['channel'],
            'category' => $validated['category'],
            'target_role' => $validated['target_role'],
            'title' => $validated['title'],
            'message' => $validated['message'],
            'status' => $status,
        ]);

        $this->erp->audit('communication.broadcast', 'administration', "Logged {$log->channel} broadcast '{$log->title}' ({$log->status})", $log);

        return back()->with('flash_success', __('Notification broadcast logged (:status).', ['status' => $status]));
    }
}
