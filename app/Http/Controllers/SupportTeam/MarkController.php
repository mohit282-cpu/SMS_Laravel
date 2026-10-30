<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Helpers\Mk;
use App\Http\Requests\Mark\MarkSelector;
use App\Models\Setting;
use App\Repositories\ExamRepo;
use App\Repositories\MarkRepo;
use App\Repositories\MyClassRepo;
use App\Http\Controllers\Controller;
use App\Repositories\StudentRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class MarkController extends Controller
{
    protected $my_class, $exam, $student, $year, $user, $mark;

    public function __construct(MyClassRepo $my_class, ExamRepo $exam, StudentRepo $student, MarkRepo $mark)
    {
        $this->exam =  $exam;
        $this->mark =  $mark;
        $this->student =  $student;
        $this->my_class =  $my_class;
        $this->year =  Qs::getCurrentSession();

        $this->middleware('teamSAT', ['except' => ['show', 'year_selected', 'year_selector', 'print_view', 'pdf_download'] ]);
    }

    public function index()
    {
        $exams = $this->exam->getExam(['year' => $this->year]);
        if($exams->isEmpty()){
            $exams = $this->exam->all();
        }
        $d['exams'] = $exams;
        $d['my_classes'] = $this->my_class->all();
        $d['sections'] = $this->my_class->getAllSections();
        $d['subjects'] = $this->my_class->getAllSubjects();
        $d['selected'] = false;

        return view('pages.support_team.marks.index', $d);
    }

    public function year_selector($student_id)
    {
        $st_id = $this->decodeStudentId($student_id);

        /* Prevent unauthorized access */
        if(!$this->canAccessStudentRecord($st_id)){
            if (Qs::userIsParent() || request()->expectsJson()) {
                abort(403, __('msg.denied'));
            }
            return redirect(route('dashboard'))->with('pop_error', __('msg.denied'));
        }

       return $this->verifyStudentExamYear($st_id);
    }

    public function year_selected(Request $req, $student_id)
    {
        $st_id = $this->decodeStudentId($student_id);

        /* Prevent unauthorized access */
        if(!$this->canAccessStudentRecord($st_id)){
            if (Qs::userIsParent() || request()->expectsJson()) {
                abort(403, __('msg.denied'));
            }
            return redirect(route('dashboard'))->with('pop_error', __('msg.denied'));
        }

        if(!$this->verifyStudentExamYear($st_id, $req->year)){
            return $this->noStudentRecord();
        }

        return redirect()->route('marks.show', [Qs::hash($st_id), $req->year]);
    }

    public function show($student_id, $year)
    {
        $st_id = $this->decodeStudentId($student_id);

        /* Prevent unauthorized access */
        if(!$this->canAccessStudentRecord($st_id)){
            if (Qs::userIsParent() || request()->expectsJson()) {
                abort(403, __('msg.denied'));
            }
            return redirect(route('dashboard'))->with('pop_error', __('msg.denied'));
        }

        if(Mk::examIsLocked() && !Qs::userIsTeamSA()){
            Session::put('marks_url', route('marks.show', [Qs::hash($st_id), $year]));

            if(!$this->checkPinVerified($st_id)){
                return redirect()->route('pins.enter', Qs::hash($st_id));
            }
        }

        if(!$this->verifyStudentExamYear($st_id, $year)){
            return $this->noStudentRecord();
        }

        $wh = ['student_id' => $st_id, 'year' => $year ];

        /* Prevent students/parents from viewing unpublished results */
        if (!Qs::userIsTeamSAT()) {
            $publishedExamIds = \App\Models\Exam::where(['year' => $year, 'status' => 'published'])->pluck('id')->toArray();
            $d['marks'] = $this->exam->getMark($wh)->whereIn('exam_id', $publishedExamIds);
            $d['exam_records'] = $exr = $this->exam->getRecord($wh)->whereIn('exam_id', $publishedExamIds);
            $d['exams'] = $this->exam->getExam(['year' => $year])->where('status', 'published');
            if ($d['exams']->isEmpty() || $d['exam_records']->isEmpty()) {
                return redirect(route('dashboard'))->with('flash_danger', __('Results for this session have not been published yet.'));
            }
        } else {
            $d['marks'] = $this->exam->getMark($wh);
            $d['exam_records'] = $exr = $this->exam->getRecord($wh);
            $d['exams'] = $this->exam->getExam(['year' => $year]);
        }
        $d['sr'] = $this->student->getRecord(['user_id' => $st_id])->first();
        if ($exr->isEmpty() || !$exr->first()) {
            return $this->noStudentRecord();
        }
        $d['my_class'] = $mc = $this->my_class->getMC(['id' => $exr->first()->my_class_id])->first();
        $d['class_type'] = $this->my_class->findTypeByClass($mc->id);
        $d['subjects'] = $this->my_class->findSubjectByClass($mc->id);
        $d['year'] = $year;
        $d['student_id'] = $st_id;
        $d['skills'] = $this->exam->getSkillByClassType() ?: NULL;
        //$d['ct'] = $d['class_type']->code;
        //$d['mark_type'] = Qs::getMarkType($d['ct']);

        return view('pages.support_team.marks.show.index', $d);
    }

    public function print_view($student_id, $exam_id, $year)
    {
        $dataOrResponse = $this->resolveMarksheetViewData($student_id, $exam_id, $year);
        if ($dataOrResponse instanceof \Illuminate\Http\Response || $dataOrResponse instanceof \Illuminate\Http\RedirectResponse) {
            return $dataOrResponse;
        }

        return view('pages.support_team.marks.print.index', $dataOrResponse);
    }

    public function pdf_download($student_id, $exam_id, $year)
    {
        $dataOrResponse = $this->resolveMarksheetViewData($student_id, $exam_id, $year);
        if ($dataOrResponse instanceof \Illuminate\Http\Response || $dataOrResponse instanceof \Illuminate\Http\RedirectResponse) {
            return $dataOrResponse;
        }

        $sr = $dataOrResponse['sr'];
        $exam = $dataOrResponse['ex'];
        $pdf_name = 'Marksheet_' . ($sr->user->name ?? 'Student') . '_' . $exam->name . '_' . str_replace('/', '-', $year);

        return \PDF::loadView('pages.support_team.marks.print.index', $dataOrResponse)->download($pdf_name);
    }

    /**
     * Resolve and validate data required for marksheet print and PDF views.
     */
    protected function resolveMarksheetViewData($student_id, $exam_id, $year)
    {
        $st_id = $this->decodeStudentId($student_id);

        /* Prevent unauthorized access */
        if(!$this->canAccessStudentRecord($st_id)){
            if (Qs::userIsParent() || request()->expectsJson()) {
                abort(403, __('msg.denied'));
            }
            return redirect(route('dashboard'))->with('pop_error', __('msg.denied'));
        }

        if(Mk::examIsLocked() && !Qs::userIsTeamSA()){
            Session::put('marks_url', route('marks.show', [Qs::hash($st_id), $year]));

            if(!$this->checkPinVerified($st_id)){
                return redirect()->route('pins.enter', Qs::hash($st_id));
            }
        }

        if(!$this->verifyStudentExamYear($st_id, $year)){
            return $this->noStudentRecord();
        }

        $wh = ['student_id' => $st_id, 'exam_id' => $exam_id, 'year' => $year ];
        $d['marks'] = $this->exam->getMark($wh);
        $d['exr'] = $exr = $this->exam->getRecord($wh)->first();
        if (!$exr) {
            return $this->noStudentRecord();
        }
        $d['my_class'] = $mc = $this->my_class->find($exr->my_class_id);
        $d['section_id'] = $exr->section_id;
        $d['ex'] = $exam = $this->exam->find($exam_id);
        if (!$exam) {
            return $this->noStudentRecord();
        }

        /* Prevent students/parents from viewing unpublished results */
        if (!Qs::userIsTeamSAT() && $exam->status !== 'published') {
            return redirect(route('dashboard'))->with('flash_danger', __('Results for this examination have not been published yet.'));
        }

        $d['tex'] = 'tex'.$exam->term;
        $d['sr'] = $this->student->getRecord(['user_id' => $st_id])->first();
        $d['class_type'] = $this->my_class->findTypeByClass($mc->id);
        $d['subjects'] = $this->my_class->findSubjectByClass($mc->id);

        $d['ct'] = $d['class_type']->code;
        $d['year'] = $year;
        $d['student_id'] = $st_id;
        $d['exam_id'] = $exam_id;

        $d['skills'] = $this->exam->getSkillByClassType() ?: NULL;
        $d['s'] = Setting::all()->flatMap(function($s){
            return [$s->type => $s->description];
        });

        return $d;
    }

    public function selector(MarkSelector $req)
    {
        // Validate academic relationships
        $sec = \App\Models\Section::where('id', $req->section_id)->where('my_class_id', $req->my_class_id)->first();
        $sub = \App\Models\Subject::where('id', $req->subject_id)->where('my_class_id', $req->my_class_id)->first();

        if(!$sec || !$sub){
            return back()->with('pop_error', __('msg.rnf'));
        }

        // Teachers can only select marks for subjects assigned to them
        if(Qs::userIsTeacher()){
            if($sub->teacher_id != Auth::id()){
                return back()->with('pop_error', __('msg.denied'));
            }
        }

        $data = $req->only(['exam_id', 'my_class_id', 'section_id', 'subject_id']);
        $d2 = $req->only(['exam_id', 'my_class_id', 'section_id']);
        $d = $req->only(['my_class_id', 'section_id']);

        $exam = $this->exam->find($req->exam_id);
        $examYear = $exam ? $exam->year : $this->year;
        $data['year'] = $d2['year'] = $examYear;

        // Try getting students for the exam session/year first
        $students = $this->student->getRecord(array_merge($d, ['session' => $examYear]))->get();
        // If no students explicitly registered under this session string, fall back to active students in class and section
        if($students->count() < 1){
            $students = $this->student->getRecord($d)->get();
        }

        if($students->count() < 1){
            return back()->with('pop_error', __('msg.rnf'));
        }

        foreach ($students as $s){
            $data['student_id'] = $d2['student_id'] = $s->user_id;
            $this->exam->createMark($data);
            $this->exam->createRecord($d2);
        }

        return redirect()->route('marks.manage', [$req->exam_id, $req->my_class_id, $req->section_id, $req->subject_id]);
    }

    public function manage($exam_id, $class_id, $section_id, $subject_id)
    {
        // Validate academic relationships
        $sec = \App\Models\Section::where('id', $section_id)->where('my_class_id', $class_id)->first();
        $sub = \App\Models\Subject::where('id', $subject_id)->where('my_class_id', $class_id)->first();

        if(!$sec || !$sub){
            return $this->noStudentRecord();
        }

        // Teachers can only manage subjects assigned to them
        if(Qs::userIsTeacher()){
            if($sub->teacher_id != Auth::id()){
                abort(403, __('msg.denied'));
            }
        }

        $exam = $this->exam->find($exam_id);
        $examYear = $exam ? $exam->year : $this->year;
        $d = ['exam_id' => $exam_id, 'my_class_id' => $class_id, 'section_id' => $section_id, 'subject_id' => $subject_id, 'year' => $examYear];

        $d['marks'] = $this->exam->getMark($d);
        if($d['marks']->count() < 1){
            return $this->noStudentRecord();
        }

        $d['m'] =  $d['marks']->first();
        $d['exams'] = $this->exam->all();
        $d['my_classes'] = $this->my_class->all();
        $d['sections'] = $this->my_class->getAllSections();
        $d['subjects'] = $this->my_class->getAllSubjects();
        if(Qs::userIsTeacher()){
            $d['subjects'] = $this->my_class->findSubjectByTeacher(Auth::user()->id)->where('my_class_id', $class_id);
        }
        $d['selected'] = true;
        $d['class_type'] = $this->my_class->findTypeByClass($class_id);
        return view('pages.support_team.marks.manage', $d);
    }

    public function update(Request $req, $exam_id, $class_id, $section_id, $subject_id)
    {
        // Validate academic relationships
        $sec = \App\Models\Section::where('id', $section_id)->where('my_class_id', $class_id)->first();
        $subject = \App\Models\Subject::where('id', $subject_id)->where('my_class_id', $class_id)->first();

        if(!$sec || !$subject){
            return response()->json(['ok' => false, 'msg' => __('msg.rnf')], 404);
        }

        // Teachers can only update marks for subjects assigned to them
        if(Qs::userIsTeacher()){
            if($subject->teacher_id != Auth::id()){
                return response()->json(['ok' => false, 'msg' => __('msg.denied')], 403);
            }
        }

        $exam = $this->exam->find($exam_id);
        $examYear = $exam ? $exam->year : $this->year;
        $p = ['exam_id' => $exam_id, 'my_class_id' => $class_id, 'section_id' => $section_id, 'subject_id' => $subject_id, 'year' => $examYear];
        $marks = $this->exam->getMark($p);
        $class_type = $this->my_class->findTypeByClass($class_id);
        $gradingService = app(\App\Services\NepalGradingService::class);

        $mks = $req->all();
        $all_st_ids = [];

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            /** Test, Exam, Grade **/
            foreach($marks->sortBy('user.name') as $mk)
            {
                $all_st_ids[] = $mk->student_id;
                $oldValues = $mk->only(['theory_mark', 'practical_mark', 'total_mark', 'gpa_point', 'grade_name', 'is_absent', 'is_exempt']);

                $isAbsent = isset($mks['absent_'.$mk->id]) && $mks['absent_'.$mk->id] == '1';
                $isExempt = isset($mks['exempt_'.$mk->id]) && $mks['exempt_'.$mk->id] == '1';

                $theoryMark = isset($mks['theory_'.$mk->id]) && $mks['theory_'.$mk->id] !== '' ? (float)$mks['theory_'.$mk->id] : (isset($mks['exm_'.$mk->id]) && $mks['exm_'.$mk->id] !== '' ? (float)$mks['exm_'.$mk->id] : null);
                $practicalMark = isset($mks['practical_'.$mk->id]) && $mks['practical_'.$mk->id] !== '' ? (float)$mks['practical_'.$mk->id] : (isset($mks['t1_'.$mk->id]) && $mks['t1_'.$mk->id] !== '' ? (float)$mks['t1_'.$mk->id] : null);

                // Enforce marks boundaries
                $theoryMax = $subject ? ($subject->has_practical ? $subject->theory_max : $subject->total_max) : 100;
                $practicalMax = ($subject && $subject->has_practical) ? $subject->practical_max : 0;
                $totalMax = $subject ? $subject->total_max : 100;

                if ($theoryMark !== null && ($theoryMark < 0 || $theoryMark > $theoryMax)) {
                    \Illuminate\Support\Facades\DB::rollBack();
                    return response()->json(['ok' => false, 'msg' => __('Marks must be between 0 and maximum :max', ['max' => $theoryMax])], 422);
                }

                if ($practicalMark !== null && ($practicalMark < 0 || $practicalMark > $practicalMax)) {
                    \Illuminate\Support\Facades\DB::rollBack();
                    return response()->json(['ok' => false, 'msg' => __('Practical marks must be between 0 and maximum :max', ['max' => $practicalMax])], 422);
                }

                $total = 0;
                if (!$isAbsent && !$isExempt) {
                    $total = ($theoryMark ?? 0) + ($practicalMark ?? 0);
                    if ($total > $totalMax) {
                        \Illuminate\Support\Facades\DB::rollBack();
                        return response()->json(['ok' => false, 'msg' => __('Total marks cannot exceed :max', ['max' => $totalMax])], 422);
                    }
                }

                // Percentage calculation for grading
                $percentage = ($totalMax > 0 && !$isAbsent && !$isExempt) ? ($total / $totalMax) * 100 : 0;
                $gradeResult = $gradingService->getGradeAndGpa($percentage, $isAbsent, $isExempt);

                $d = [
                    't1' => $practicalMark,
                    't2' => null,
                    'tca' => $practicalMark,
                    'exm' => $theoryMark,
                    'tex'.$exam->term => $total,
                    'theory_mark' => $theoryMark,
                    'practical_mark' => $practicalMark,
                    'total_mark' => $total,
                    'gpa_point' => $gradeResult['gpa'],
                    'grade_name' => $gradeResult['grade'],
                    'is_absent' => $isAbsent,
                    'is_exempt' => $isExempt,
                    'is_passed' => $gradeResult['is_passed'],
                    'remarks' => $gradeResult['remark'],
                    'updated_by' => Auth::id(),
                ];

                // Check traditional grade fallback for backward compatibility
                $legacyGrade = $this->mark->getGrade($total, $class_type->id);
                $d['grade_id'] = $legacyGrade ? $legacyGrade->id : null;

                $this->exam->updateMark($mk->id, $d);

                // Audit Trail
                \App\Models\MarkAuditTrail::create([
                    'mark_id' => $mk->id,
                    'student_id' => $mk->student_id,
                    'subject_id' => $subject_id,
                    'exam_id' => $exam_id,
                    'action' => 'updated',
                    'old_values' => $oldValues,
                    'new_values' => $d,
                    'user_id' => Auth::id(),
                ]);
            }

            /** Sub Position **/
            foreach($marks->sortBy('user.name') as $mk)
            {
                $d2['sub_pos'] = $this->mark->getSubPos($mk->student_id, $exam, $class_id, $subject_id, $examYear);
                $this->exam->updateMark($mk->id, $d2);
            }

            /** Exam Record Update **/
            unset($p['subject_id']);

            foreach ($all_st_ids as $st_id) {
                $p['student_id'] = $st_id;
                $d3['total'] = $this->mark->getExamTotalTerm($exam, $st_id, $class_id, $examYear);
                $d3['ave'] = $this->mark->getExamAvgTerm($exam, $st_id, $class_id, $section_id, $examYear);
                $d3['class_ave'] = $this->mark->getClassAvg($exam, $class_id, $examYear);
                $d3['pos'] = $this->mark->getPos($st_id, $exam, $class_id, $section_id, $examYear);

                // Calculate cumulative GPA across all subjects for this student in this exam
                $allStudentMarks = \App\Models\Mark::with('subject')
                    ->where(['exam_id' => $exam_id, 'student_id' => $st_id, 'year' => $examYear])
                    ->get();

                $subjectRecords = [];
                foreach ($allStudentMarks as $sm) {
                    $subjectRecords[] = [
                        'gpa' => $sm->gpa_point ?? 0.0,
                        'credit_hour' => $sm->subject ? $sm->subject->credit_hour : 4.0,
                        'is_absent' => $sm->is_absent,
                        'is_exempt' => $sm->is_exempt,
                        'is_passed' => $sm->is_passed,
                    ];
                }

                $overall = $gradingService->calculateOverallResult($subjectRecords);
                $d3['gpa'] = $overall['gpa'];
                $d3['grade'] = $overall['overall_grade'];
                $d3['result_status'] = $overall['result_status'];

                $this->exam->updateRecord($p, $d3);
            }

            \Illuminate\Support\Facades\DB::commit();

            app(\App\Services\AuditLogService::class)->log(
                'mark.update',
                'academics',
                "Updated examination marks for {$marks->count()} student(s) in '{$subject->name}' for exam '{$exam->name}'",
                $exam,
                ['exam_id' => $exam_id, 'subject_id' => $subject_id, 'class_id' => $class_id],
                ['count' => $marks->count()],
                'success'
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            $correlationId = (string) \Illuminate\Support\Str::uuid();
            \Illuminate\Support\Facades\Log::error("Mark update transaction failed [Ref: {$correlationId}]: " . $e->getMessage(), [
                'correlation_id' => $correlationId,
                'user_id' => \Illuminate\Support\Facades\Auth::id(),
                'exception' => $e,
            ]);
            return response()->json(['ok' => false, 'msg' => __('Failed to update marks. Please try again. Reference ID: :id', ['id' => $correlationId])], 500);
        }

        return Qs::jsonUpdateOk();
    }

    public function batch_fix()
    {
        $exams = $this->exam->getExam(['year' => $this->year]);
        if($exams->isEmpty()){
            $exams = $this->exam->all();
        }
        $d['exams'] = $exams;
        $d['my_classes'] = $this->my_class->all();
        $d['sections'] = $this->my_class->getAllSections();
        $d['selected'] = false;

        return view('pages.support_team.marks.batch_fix', $d);
    }

    public function batch_update(Request $req): \Illuminate\Http\JsonResponse
    {
        $exam_id = $req->exam_id;
        $class_id = $req->my_class_id;
        $section_id = $req->section_id;

        $exam = $this->exam->find($exam_id);
        $examYear = $exam ? $exam->year : $this->year;
        $w = ['exam_id' => $exam_id, 'my_class_id' => $class_id, 'section_id' => $section_id, 'year' => $examYear];

        $exrs = $this->exam->getRecord($w);
        $marks = $this->exam->getMark($w);

        /** Marks Fix Begin **/

        $class_type = $this->my_class->findTypeByClass($class_id);
        $tex = 'tex'.$exam->term;

        foreach($marks as $mk){

            $total = $mk->$tex;
            $d['grade_id'] = $this->mark->getGrade($total, $class_type->id);

            /*      if($exam->term == 3){
                      $d['cum'] = $this->mark->getSubCumTotal($total, $mk->student_id, $mk->subject_id, $class_id, $this->year);
                      $d['cum_ave'] = $cav = $this->mark->getSubCumAvg($total, $mk->student_id, $mk->subject_id, $class_id, $this->year);
                      $grade = $this->mark->getGrade(round($mk->cum_ave), $class_type->id);
                  }*/

            $this->exam->updateMark($mk->id, $d);
        }

        /* Marks Fix End*/

        /** Exam Record Update  **/
        foreach($exrs as $exr){

            $st_id = $exr->student_id;

            $d3['total'] = $this->mark->getExamTotalTerm($exam, $st_id, $class_id, $examYear);
            $d3['ave'] = $this->mark->getExamAvgTerm($exam, $st_id, $class_id, $section_id, $examYear);
            $d3['class_ave'] = $this->mark->getClassAvg($exam, $class_id, $examYear);
            $d3['pos'] = $this->mark->getPos($st_id, $exam, $class_id, $section_id, $examYear);

            $this->exam->updateRecord(['id' => $exr->id], $d3);
        }

        /** END Exam Record Update END **/

        return Qs::jsonUpdateOk();
    }

    public function comment_update(Request $req, $exr_id)
    {
        $exr = $this->exam->getRecord(['id' => $exr_id])->first();
        if(!$exr){
            return response()->json(['ok' => false, 'msg' => __('msg.rnf')], 404);
        }

        if(Qs::userIsTeacher()){
            $isClassTeacher = \App\Models\Section::where('id', $exr->section_id)->where('teacher_id', Auth::id())->exists();
            if(!$isClassTeacher){
                return response()->json(['ok' => false, 'msg' => __('msg.denied')], 403);
            }
        }

        $d = Qs::userIsTeamSA() ? $req->only(['t_comment', 'p_comment']) : $req->only(['t_comment']);

        $this->exam->updateRecord(['id' => $exr_id], $d);
        return Qs::jsonUpdateOk();
    }

    public function skills_update(Request $req, $skill, $exr_id)
    {
        $exr = $this->exam->getRecord(['id' => $exr_id])->first();
        if(!$exr){
            return response()->json(['ok' => false, 'msg' => __('msg.rnf')], 404);
        }

        if(Qs::userIsTeacher()){
            $isClassTeacher = \App\Models\Section::where('id', $exr->section_id)->where('teacher_id', Auth::id())->exists();
            if(!$isClassTeacher){
                return response()->json(['ok' => false, 'msg' => __('msg.denied')], 403);
            }
        }

        $d = [];
        if($skill == 'AF' || $skill == 'PS'){
            $sk = strtolower($skill);
            $d[$skill] = implode(',', $req->$sk);
        }

        $this->exam->updateRecord(['id' => $exr_id], $d);
        return Qs::jsonUpdateOk();
    }

    public function bulk($class_id = NULL, $section_id = NULL)
    {
        $d['my_classes'] = $this->my_class->all();
        $d['selected'] = false;

        if($class_id && $section_id){
            if (Qs::userIsTeacher()) {
                $isAssigned = \App\Models\Section::where('id', $section_id)->where('teacher_id', Auth::id())->where('my_class_id', $class_id)->exists();
                if(!$isAssigned){
                    return redirect()->route('marks.bulk')->with('flash_danger', __('msg.denied'));
                }
            }
            $d['sections'] = $this->my_class->getAllSections()->where('my_class_id', $class_id);
            $d['students'] = $st = $this->student->getRecord(['my_class_id' => $class_id, 'section_id' => $section_id])->get()->sortBy('user.name');
            if($st->count() < 1){
                return redirect()->route('marks.bulk')->with('flash_danger', __('msg.srnf'));
            }
            $d['selected'] = true;
            $d['my_class_id'] = $class_id;
            $d['section_id'] = $section_id;
        }

        return view('pages.support_team.marks.bulk', $d);
    }

    public function bulk_select(Request $req)
    {
        if (Qs::userIsTeacher()) {
            $isAssigned = \App\Models\Section::where('id', $req->section_id)->where('teacher_id', Auth::id())->where('my_class_id', $req->my_class_id)->exists();
            if(!$isAssigned){
                return redirect()->route('marks.bulk')->with('flash_danger', __('msg.denied'));
            }
        }
        return redirect()->route('marks.bulk', [$req->my_class_id, $req->section_id]);
    }

    public function tabulation($exam_id = NULL, $class_id = NULL, $section_id = NULL)
    {
        $d['my_classes'] = $this->my_class->all();
        $exams = $this->exam->getExam(['year' => $this->year]);
        if($exams->isEmpty()){
            $exams = $this->exam->all();
        }
        $d['exams'] = $exams;
        $d['selected'] = FALSE;

        if($class_id && $exam_id && $section_id){
            $exam = $this->exam->find($exam_id);
            $examYear = $exam ? $exam->year : $this->year;

            $wh = ['my_class_id' => $class_id, 'section_id' => $section_id, 'exam_id' => $exam_id, 'year' => $examYear];

            $sub_ids = $this->mark->getSubjectIDs($wh);
            $st_ids = $this->mark->getStudentIDs($wh);

            if(count($sub_ids) < 1 OR count($st_ids) < 1) {
                return Qs::goWithDanger('marks.tabulation', __('msg.srnf'));
            }

            $d['subjects'] = $this->my_class->getSubjectsByIDs($sub_ids);
            $d['students'] = $this->student->getRecordByUserIDs($st_ids)->get()->sortBy('user.name');
            $d['sections'] = $this->my_class->getAllSections();

            $d['selected'] = TRUE;
            $d['my_class_id'] = $class_id;
            $d['section_id'] = $section_id;
            $d['exam_id'] = $exam_id;
            $d['year'] = $examYear;
            $d['marks'] = $mks = $this->exam->getMark($wh);
            $d['exr'] = $exr = $this->exam->getRecord($wh);

            $d['my_class'] = $mc = $this->my_class->find($class_id);
            $d['section']  = $this->my_class->findSection($section_id);
            $d['ex'] = $exam;
            $d['tex'] = 'tex'.$exam->term;
            //$d['class_type'] = $this->my_class->findTypeByClass($mc->id);
            //$d['ct'] = $ct = $d['class_type']->code;
        }

        return view('pages.support_team.marks.tabulation.index', $d);
    }

    public function print_tabulation($exam_id, $class_id, $section_id)
    {
        $exam = $this->exam->find($exam_id);
        $examYear = $exam ? $exam->year : $this->year;

        $wh = ['my_class_id' => $class_id, 'section_id' => $section_id, 'exam_id' => $exam_id, 'year' => $examYear];

        $sub_ids = $this->mark->getSubjectIDs($wh);
        $st_ids = $this->mark->getStudentIDs($wh);

        if(count($sub_ids) < 1 OR count($st_ids) < 1) {
            return Qs::goWithDanger('marks.tabulation', __('msg.srnf'));
        }

        $d['subjects'] = $this->my_class->getSubjectsByIDs($sub_ids);
        $d['students'] = $this->student->getRecordByUserIDs($st_ids)->get()->sortBy('user.name');

        $d['my_class_id'] = $class_id;
        $d['exam_id'] = $exam_id;
        $d['year'] = $examYear;
        $d['marks'] = $mks = $this->exam->getMark($wh);
        $d['exr'] = $exr = $this->exam->getRecord($wh);

        $d['my_class'] = $mc = $this->my_class->find($class_id);
        $d['section']  = $this->my_class->findSection($section_id);
        $d['ex'] = $exam;
        $d['tex'] = 'tex'.$exam->term;
        $d['s'] = Setting::all()->flatMap(function($s){
            return [$s->type => $s->description];
        });
        //$d['class_type'] = $this->my_class->findTypeByClass($mc->id);
        //$d['ct'] = $ct = $d['class_type']->code;

        return view('pages.support_team.marks.tabulation.print', $d);
    }

    public function tabulation_select(Request $req)
    {
        return redirect()->route('marks.tabulation', [$req->exam_id, $req->my_class_id, $req->section_id]);
    }

    protected function decodeStudentId($student_id)
    {
        $raw = Qs::decodeHash($student_id);
        return $raw ? (int)$raw : (int)$student_id;
    }

    protected function verifyStudentExamYear($student_id, $year = null)
    {
        $st_id = $this->decodeStudentId($student_id);
        $student_exists = $this->student->exists($st_id);

        if (!$student_exists) {
            return $year ? false : $this->noStudentRecord();
        }

        $years = $this->exam->getExamYears($st_id);

        if (!$year) {
            if ($years->count() > 0) {
                $d = ['years' => $years, 'student_id' => Qs::hash($st_id)];

                return view('pages.support_team.marks.select_year', $d);
            }

            return redirect()->route('dashboard')->with('flash_danger', __('No exam records or marksheet found for this student.'));
        }

        return $years->contains('year', $year);
    }

    protected function noStudentRecord()
    {
        return redirect()->route('dashboard')->with('flash_danger', __('msg.srnf'));
    }

    protected function checkPinVerified($st_id)
    {
        return Session::has('pin_verified') && Session::get('pin_verified') == $st_id;
    }

    /**
     * Determine whether the authenticated user is authorized to access a given student's marks.
     */
    protected function canAccessStudentRecord($st_id): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        // Super Admin & Admin can access all student records
        if (Qs::userIsTeamSA()) {
            return true;
        }

        // Student can only access their own record
        if (Qs::userIsStudent()) {
            return (int)$user->id === (int)$st_id;
        }

        // Parent can only access their linked children's records
        if (Qs::userIsParent()) {
            return Qs::userIsMyChild($st_id, $user->id);
        }

        // Teacher can only access students in classes/sections/subjects they are assigned to
        if (Qs::userIsTeacher()) {
            $srCheck = $this->student->getRecord(['user_id' => $st_id])->first();
            if (!$srCheck) {
                return false;
            }
            $teachesClass = \App\Models\Subject::where('my_class_id', $srCheck->my_class_id)
                ->where('teacher_id', $user->id)
                ->exists();
            $teachesSection = \App\Models\Section::where('id', $srCheck->section_id)
                ->where('teacher_id', $user->id)
                ->exists();
            return $teachesClass || $teachesSection;
        }

        return false;
    }
}
