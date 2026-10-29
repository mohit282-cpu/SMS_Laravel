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

        $bsService = app(\App\Services\BikramSambatService::class);
        $startDate = null;
        $endDate = null;

        if (!empty($data['start_date_bs'])) {
            try {
                $conv = $bsService->bsToAd($data['start_date_bs']);
                $startDate = $conv['ad_date'];
            } catch (\Throwable $e) {
                return back()->withInput()->with('flash_danger', __('Failed to convert start BS date: ') . $e->getMessage());
            }
        }
        if (!empty($data['end_date_bs'])) {
            try {
                $conv = $bsService->bsToAd($data['end_date_bs']);
                $endDate = $conv['ad_date'];
            } catch (\Throwable $e) {
                return back()->withInput()->with('flash_danger', __('Failed to convert end BS date: ') . $e->getMessage());
            }
        }

        if ($startDate && $endDate && $endDate < $startDate) {
            return back()->withInput()->with('flash_danger', __('Examination end date cannot be earlier than start date.'));
        }

        $data['start_date'] = $startDate;
        $data['end_date'] = $endDate;

        if (($data['status'] ?? 'draft') === 'published') {
            $data['published_by'] = \Illuminate\Support\Facades\Auth::id();
            $data['published_at'] = now();
        }

        // Duplicate schedule prevention within active session
        $exists = $this->exam->getExam(['name' => $data['name'], 'year' => $data['year']])->first();
        if ($exists) {
            return back()->withInput()->with('flash_danger', __('An examination with this title already exists in the current academic session.'));
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($data, &$created) {
            $created = $this->exam->create($data);
            $this->syncExamCalendarEvent($created);
        });

        app(\App\Services\AuditLogService::class)->log(
            'exam.create',
            'academics',
            "Created examination '{$data['name']}' (Year: {$data['year']}, Term: {$data['term']})",
            $created ?? null,
            [],
            ['name' => $data['name'], 'term' => $data['term'], 'year' => $data['year']],
            'success'
        );

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

        $bsService = app(\App\Services\BikramSambatService::class);
        $startDate = null;
        $endDate = null;

        if (!empty($data['start_date_bs'])) {
            try {
                $conv = $bsService->bsToAd($data['start_date_bs']);
                $startDate = $conv['ad_date'];
            } catch (\Throwable $e) {
                return back()->withInput()->with('flash_danger', __('Failed to convert start BS date: ') . $e->getMessage());
            }
        }
        if (!empty($data['end_date_bs'])) {
            try {
                $conv = $bsService->bsToAd($data['end_date_bs']);
                $endDate = $conv['ad_date'];
            } catch (\Throwable $e) {
                return back()->withInput()->with('flash_danger', __('Failed to convert end BS date: ') . $e->getMessage());
            }
        }

        if ($startDate && $endDate && $endDate < $startDate) {
            return back()->withInput()->with('flash_danger', __('Examination end date cannot be earlier than start date.'));
        }

        $data['start_date'] = $startDate;
        $data['end_date'] = $endDate;

        $currentExam = $this->exam->find($id);
        if (($data['status'] ?? 'draft') === 'published' && $currentExam->status !== 'published') {
            $data['published_by'] = \Illuminate\Support\Facades\Auth::id();
            $data['published_at'] = now();
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($id, $data) {
            $this->exam->update($id, $data);
            $updatedExam = $this->exam->find($id);
            $this->syncExamCalendarEvent($updatedExam);
        });

        return back()->with('flash_success', __('msg.update_ok'));
    }

    public function publish($id)
    {
        $examId = is_object($id) ? $id->id : $id;
        $exam = $this->exam->find($examId);
        if (!$exam) {
            return back()->with('flash_danger', __('Exam not found.'));
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($examId) {
            $this->exam->update($examId, [
                'status' => 'published',
                'published_by' => \Illuminate\Support\Facades\Auth::id(),
                'published_at' => now(),
            ]);

            \App\Models\Event::where('exam_id', $examId)->update(['is_published' => true]);
        });

        app(\App\Services\AuditLogService::class)->log(
            'exam.publish',
            'academics',
            "Published examination results for '{$exam->name}' (ID: {$examId})",
            $exam,
            ['status' => $exam->status],
            ['status' => 'published'],
            'success'
        );

        return back()->with('flash_success', __('Exam results published successfully.'));
    }

    public function unpublish($id)
    {
        $exam = $this->exam->find($id);
        if (!$exam) {
            return back()->with('flash_danger', __('Exam not found.'));
        }

        $now = now()->format('Y-m-d');
        $revertStatus = 'draft';
        if (!empty($exam->end_date) && $exam->end_date->format('Y-m-d') < $now) {
            $revertStatus = 'completed';
        } elseif (!empty($exam->start_date) && $exam->start_date->format('Y-m-d') <= $now) {
            $revertStatus = 'ongoing';
        } elseif (!empty($exam->start_date)) {
            $revertStatus = 'scheduled';
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($id, $revertStatus) {
            $this->exam->update($id, [
                'status' => $revertStatus,
                'published_by' => null,
                'published_at' => null,
            ]);

            \App\Models\Event::where('exam_id', $id)->update(['is_published' => false]);
        });

        return back()->with('flash_success', __('Exam results unpublished successfully.'));
    }

    public function destroy($id)
    {
        $hasMarks = \App\Models\Mark::where('exam_id', $id)->exists();
        if ($hasMarks) {
            return back()->with('flash_danger', __('Cannot delete examination: Student marks and results have already been recorded for this examination.'));
        }

        $exam = $this->exam->find($id);
        $examName = $exam ? $exam->name : "Exam #{$id}";

        \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            \App\Models\Event::where('exam_id', $id)->delete();
            $this->exam->delete($id);
        });

        app(\App\Services\AuditLogService::class)->log(
            'exam.delete',
            'academics',
            "Deleted examination '{$examName}' (ID: {$id})",
            null,
            ['id' => $id, 'name' => $examName],
            [],
            'success'
        );

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
