<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Requests\Exam\ExamCreate;
use App\Http\Requests\Exam\ExamUpdate;
use App\Repositories\ExamRepo;
use App\Http\Controllers\Controller;

class ExamController extends Controller
{
    protected $exam;
    public function __construct(ExamRepo $exam)
    {
        $this->middleware('teamSA', ['except' => ['destroy',] ]);
        $this->middleware('super_admin', ['only' => ['destroy',] ]);

        $this->exam = $exam;
    }

    public function index()
    {
        $d['exams'] = $this->exam->all();
        return view('pages.support_team.exams.index', $d);
    }

    public function store(ExamCreate $req)
    {
        $data = $req->only(['name', 'term', 'exam_type', 'start_date_bs', 'end_date_bs', 'status', 'description']);
        $data['year'] = Qs::getCurrentSession();

        if (!empty($data['start_date_bs'])) {
            $data['start_date'] = \App\Helpers\NepaliHelper::bsToAd($data['start_date_bs']);
        }
        if (!empty($data['end_date_bs'])) {
            $data['end_date'] = \App\Helpers\NepaliHelper::bsToAd($data['end_date_bs']);
        }

        if (($data['status'] ?? 'draft') === 'published') {
            $data['published_by'] = \Illuminate\Support\Facades\Auth::id();
            $data['published_at'] = now();
        }

        // Duplicate schedule prevention within active session
        $exists = $this->exam->getExam(['name' => $data['name'], 'year' => $data['year']])->first();
        if ($exists) {
            return back()->withInput()->with('flash_danger', __('An examination with this title already exists in the current academic session.'));
        }

        $created = $this->exam->create($data);
        $this->syncExamCalendarEvent($created);

        return back()->with('flash_success', __('msg.store_ok'));
    }

    public function edit($id)
    {
        $d['ex'] = $this->exam->find($id);
        return view('pages.support_team.exams.edit', $d);
    }

    public function update(ExamUpdate $req, $id)
    {
        $data = $req->only(['name', 'term', 'exam_type', 'start_date_bs', 'end_date_bs', 'status', 'description']);

        if (!empty($data['start_date_bs'])) {
            $data['start_date'] = \App\Helpers\NepaliHelper::bsToAd($data['start_date_bs']);
        }
        if (!empty($data['end_date_bs'])) {
            $data['end_date'] = \App\Helpers\NepaliHelper::bsToAd($data['end_date_bs']);
        }

        if (!empty($data['start_date']) && !empty($data['end_date']) && $data['end_date'] < $data['start_date']) {
            return back()->withInput()->with('flash_danger', __('Examination end date cannot be earlier than start date.'));
        }

        $currentExam = $this->exam->find($id);
        if (($data['status'] ?? 'draft') === 'published' && $currentExam->status !== 'published') {
            $data['published_by'] = \Illuminate\Support\Facades\Auth::id();
            $data['published_at'] = now();
        }

        $this->exam->update($id, $data);
        $updatedExam = $this->exam->find($id);
        $this->syncExamCalendarEvent($updatedExam);

        return back()->with('flash_success', __('msg.update_ok'));
    }

    public function publish($id)
    {
        $examId = is_object($id) ? $id->id : $id;
        $exam = $this->exam->find($examId);
        if (!$exam) {
            return back()->with('flash_danger', __('Exam not found.'));
        }

        $this->exam->update($examId, [
            'status' => 'published',
            'published_by' => \Illuminate\Support\Facades\Auth::id(),
            'published_at' => now(),
        ]);

        \App\Models\Event::where('exam_id', $examId)->update(['is_published' => true]);

        return back()->with('flash_success', __('Exam results published successfully.'));
    }

    public function unpublish($id)
    {
        $exam = $this->exam->find($id);
        if (!$exam) {
            return back()->with('flash_danger', __('Exam not found.'));
        }

        $this->exam->update($id, [
            'status' => 'completed',
        ]);

        \App\Models\Event::where('exam_id', $id)->update(['is_published' => false]);

        return back()->with('flash_success', __('Exam results unpublished successfully.'));
    }

    public function destroy($id)
    {
        // Safe deletion behavior: Remove linked calendar event, preserving marks and audit records
        \App\Models\Event::where('exam_id', $id)->delete();
        $this->exam->delete($id);
        return back()->with('flash_success', __('msg.del_ok'));
    }

    /**
     * Synchronize examination schedule to calendar event.
     */
    protected function syncExamCalendarEvent($exam): void
    {
        if (!$exam || empty($exam->start_date_bs)) {
            return;
        }

        try {
            $bsService = app(\App\Services\BikramSambatService::class);
            $startDateBs = $exam->start_date_bs;
            $endDateBs = $exam->end_date_bs ?: $startDateBs;

            $convStart = $bsService->bsToAd($startDateBs);
            $convEnd = $bsService->bsToAd($endDateBs);

            \App\Models\Event::updateOrCreate(
                ['exam_id' => $exam->id],
                [
                    'title' => $exam->name,
                    'title_ne' => $exam->name,
                    'category' => 'examination',
                    'start_date' => $convStart['ad_date'],
                    'end_date' => $convEnd['ad_date'],
                    'start_date_bs' => $startDateBs,
                    'end_date_bs' => $endDateBs,
                    'is_all_day' => true,
                    'is_published' => in_array($exam->status, ['published', 'ongoing', 'scheduled'], true),
                    'is_closure' => false,
                    'session' => $exam->year ?: Qs::getCurrentSession(),
                    'target_audience' => 'all',
                    'description' => $exam->description ?: "Examination: {$exam->name} (Term: {$exam->term})",
                    'instructions' => 'Students must bring their admit card, stationery, and arrive 15 minutes before exam commencement.',
                    'created_by' => \Illuminate\Support\Facades\Auth::id(),
                    'updated_by' => \Illuminate\Support\Facades\Auth::id(),
                ]
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to sync calendar event for exam: ' . $e->getMessage());
        }
    }
}
