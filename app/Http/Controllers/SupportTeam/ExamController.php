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

        $this->exam->create($data);
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

        $currentExam = $this->exam->find($id);
        if (($data['status'] ?? 'draft') === 'published' && $currentExam->status !== 'published') {
            $data['published_by'] = \Illuminate\Support\Facades\Auth::id();
            $data['published_at'] = now();
        }

        $this->exam->update($id, $data);
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

        return back()->with('flash_success', __('Exam results unpublished successfully.'));
    }

    public function destroy($id)
    {
        $this->exam->delete($id);
        return back()->with('flash_success', __('msg.del_ok'));
    }
}
