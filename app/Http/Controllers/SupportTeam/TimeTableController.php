<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Requests\TimeTable\TSRequest;
use App\Http\Requests\TimeTable\TTRecordRequest;
use App\Http\Requests\TimeTable\TTRequest;
use App\Models\Setting;
use App\Repositories\ExamRepo;
use App\Repositories\MyClassRepo;
use App\Repositories\TimeTableRepo;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TimeTableController extends Controller
{
    protected $tt, $my_class, $exam, $year;

    public function __construct(TimeTableRepo $tt, MyClassRepo $mc, ExamRepo $exam)
    {
        $this->tt = $tt;
        $this->my_class = $mc;
        $this->exam = $exam;
        $this->year = Qs::getCurrentSession();

        $this->middleware('super_admin', ['only' => ['delete', 'delete_record']]);
    }

    public function my_timetable()
    {
        $user = auth()->user();

        if (Qs::userIsStudent()) {
            $sr = \App\Models\StudentRecord::where('user_id', $user->id)->first();
            if (!$sr) {
                return back()->with('flash_danger', __('Student record not found.'));
            }
            $ttr = \App\Models\TimeTableRecord::where('my_class_id', $sr->my_class_id)->whereNull('exam_id')->first()
                ?? \App\Models\TimeTableRecord::where('my_class_id', $sr->my_class_id)->first();

            if (!$ttr) {
                return view('pages.support_team.timetables.my_timetable', ['empty' => true]);
            }
            return redirect()->route('ttr.show', $ttr->id);
        }

        if (Qs::userIsTeacher()) {
            $subjectIds = \App\Models\Subject::where('teacher_id', $user->id)->pluck('id');
            $tts = \App\Models\TimeTable::whereIn('subject_id', $subjectIds)
                ->with(['subject', 'time_slot', 'tt_record.my_class'])
                ->get();

            return view('pages.support_team.timetables.teacher_timetable', compact('tts', 'user'));
        }

        if (Qs::userIsParent()) {
            $children = \App\Models\StudentRecord::where('my_parent_id', $user->id)->with(['user', 'my_class'])->get();
            return view('pages.support_team.timetables.parent_children_timetable', compact('children'));
        }

        return redirect()->route('tt.index');
    }

    public function student_timetable($student_id)
    {
        $student_id = Qs::decodeHash($student_id) ?: $student_id;
        $user = auth()->user();

        if (Qs::userIsStudent() && $user->id != $student_id) {
            abort(403);
        }

        if (Qs::userIsParent() && !Qs::userIsMyChild($student_id, $user->id)) {
            abort(403);
        }

        $sr = \App\Models\StudentRecord::where('user_id', $student_id)->first();
        if (!$sr) {
            return back()->with('flash_danger', __('Student record not found.'));
        }

        $ttr = \App\Models\TimeTableRecord::where('my_class_id', $sr->my_class_id)->whereNull('exam_id')->first()
            ?? \App\Models\TimeTableRecord::where('my_class_id', $sr->my_class_id)->first();

        if (!$ttr) {
            return view('pages.support_team.timetables.my_timetable', ['empty' => true]);
        }

        return redirect()->route('ttr.show', $ttr->id);
    }

    public function index()
    {
        if (Qs::userIsStudent() || Qs::userIsParent()) {
            return redirect()->route('tt.my_timetable');
        }

        if (Qs::userIsTeacher()) {
            $user = auth()->user();
            $assignedClassIds = \App\Models\Section::where('teacher_id', $user->id)->pluck('my_class_id')
                ->merge(\App\Models\Subject::where('teacher_id', $user->id)->pluck('my_class_id'))
                ->unique();

            $d['my_classes'] = $this->my_class->all()->whereIn('id', $assignedClassIds);
            $d['exams'] = $this->exam->getExam(['year' => $this->year])->filter(function($ex) {
                return $ex->isPublished();
            });

            $d['tt_records'] = $this->tt->getAllRecords()->filter(function($rec) use ($assignedClassIds) {
                if (!$assignedClassIds->contains($rec->my_class_id)) {
                    return false;
                }
                if ($rec->exam_id) {
                    $ex = $this->exam->find($rec->exam_id);
                    return $ex && $ex->isPublished();
                }
                return true;
            });

            return view('pages.support_team.timetables.index', $d);
        }

        if (!Qs::userIsTeamSA()) {
            abort(403);
        }

        $d['exams'] = $this->exam->getExam(['year' => $this->year]);
        $d['my_classes'] = $this->my_class->all();
        $d['tt_records'] = $this->tt->getAllRecords();

        return view('pages.support_team.timetables.index', $d);
    }

    public function manage($ttr_id)
    {
        $d['ttr_id'] = $ttr_id;
        $d['ttr'] = $ttr = $this->tt->findRecord($ttr_id);
        if (!$ttr) {
            return Qs::goWithDanger('tt.index');
        }

        if (!$this->canManageTimeTableRecord($ttr)) {
            abort(403, __('Only assigned class teachers can manage class timetable.'));
        }

        $d['time_slots'] = $this->tt->getTimeSlotByTTR($ttr_id);
        $d['ts_existing'] = $this->tt->getExistingTS($ttr_id);
        $d['subjects'] = $this->my_class->getSubject(['my_class_id' => $ttr->my_class_id])->get();
        $d['my_class'] = $this->my_class->find($ttr->my_class_id);

        if($ttr->exam_id){
            $d['exam_id'] = $ttr->exam_id;
            $d['exam'] = $this->exam->find($ttr->exam_id);
        }

        $d['tts'] = $this->tt->getTimeTable(['ttr_id' => $ttr_id]);

        return view('pages.support_team.timetables.manage', $d);
    }

    public function store(TTRequest $req)
    {
        $data = $req->all();
        $ttr = $this->tt->findRecord($req->ttr_id);
        if (!$ttr) {
            return response()->json(['msg' => __('Record not found.'), 'ok' => false], 404);
        }

        if (!$this->canManageTimeTableRecord($ttr)) {
            return response()->json(['msg' => __('Only assigned class teachers can manage class timetable.'), 'ok' => false], 403);
        }

        $tms = $this->tt->findTimeSlot($req->ts_id);
        $d_date = $req->exam_date ?? $req->day;
        $day_col = $req->exam_date ? 'exam_date' : 'day';

        // Conflict check 1: Class already has a subject scheduled in this slot
        $classConflict = \App\Models\TimeTable::where('ttr_id', $req->ttr_id)
            ->where('ts_id', $req->ts_id)
            ->where($day_col, $d_date)
            ->exists();
        if ($classConflict) {
            return response()->json(['msg' => __('This class already has a subject scheduled for this time slot.'), 'ok' => false], 422);
        }

        // Conflict check 2: Teacher scheduling conflict across all classes
        $subject = \App\Models\Subject::find($req->subject_id);
        if ($subject && $subject->teacher_id) {
            $teacherId = $subject->teacher_id;
            $teacherConflict = \App\Models\TimeTable::where($day_col, $d_date)
                ->whereHas('subject', function($q) use ($teacherId) {
                    $q->where('teacher_id', $teacherId);
                })
                ->where(function($q) use ($req, $tms) {
                    $q->where('ts_id', $req->ts_id)
                      ->orWhere(function($sub) use ($tms) {
                          $sub->where('timestamp_from', '<', $tms->timestamp_to)
                              ->where('timestamp_to', '>', $tms->timestamp_from);
                      });
                })
                ->exists();

            if ($teacherConflict) {
                return response()->json(['msg' => __('Teacher scheduling conflict: The assigned teacher is already scheduled in another class during this time period.'), 'ok' => false], 422);
            }
        }

        $data['timestamp_from'] = strtotime($d_date.' '.$tms->time_from);
        $data['timestamp_to'] = strtotime($d_date.' '.$tms->time_to);

        $this->tt->create($data);

        return Qs::jsonStoreOk();
    }

    public function update(TTRequest $req, $tt_id)
    {
        $data = $req->all();
        $ttr = $this->tt->findRecord($req->ttr_id);
        if (!$ttr) {
            return back()->with('flash_danger', __('Record not found.'));
        }

        if (!$this->canManageTimeTableRecord($ttr)) {
            return back()->with('flash_danger', __('Only assigned class teachers can manage class timetable.'));
        }

        $tms = $this->tt->findTimeSlot($req->ts_id);
        $d_date = $req->exam_date ?? $req->day;
        $day_col = $req->exam_date ? 'exam_date' : 'day';

        // Conflict check 1: Class conflict
        $classConflict = \App\Models\TimeTable::where('ttr_id', $req->ttr_id)
            ->where('ts_id', $req->ts_id)
            ->where($day_col, $d_date)
            ->where('id', '!=', $tt_id)
            ->exists();
        if ($classConflict) {
            return back()->with('flash_danger', __('This class already has a subject scheduled for this time slot.'));
        }

        // Conflict check 2: Teacher conflict
        $subject = \App\Models\Subject::find($req->subject_id);
        if ($subject && $subject->teacher_id) {
            $teacherId = $subject->teacher_id;
            $teacherConflict = \App\Models\TimeTable::where($day_col, $d_date)
                ->where('id', '!=', $tt_id)
                ->whereHas('subject', function($q) use ($teacherId) {
                    $q->where('teacher_id', $teacherId);
                })
                ->where(function($q) use ($req, $tms) {
                    $q->where('ts_id', $req->ts_id)
                      ->orWhere(function($sub) use ($tms) {
                          $sub->where('timestamp_from', '<', $tms->timestamp_to)
                              ->where('timestamp_to', '>', $tms->timestamp_from);
                      });
                })
                ->exists();

            if ($teacherConflict) {
                return back()->with('flash_danger', __('Teacher scheduling conflict: The assigned teacher is already scheduled in another class during this time period.'));
            }
        }

        $data['timestamp_from'] = strtotime($d_date.' '.$tms->time_from);
        $data['timestamp_to'] = strtotime($d_date.' '.$tms->time_to);

        $this->tt->update($tt_id, $data);

        return back()->with('flash_success', __('msg.update_ok'));
    }

    public function delete($tt_id)
    {
        $this->tt->delete($tt_id);
        return back()->with('flash_success', __('msg.delete_ok'));
    }

    /*********** TIME SLOTS *************/

    public function store_time_slot(TSRequest $req)
    {
        $data = $req->all();
        $data['time_from'] = $tf =$req->hour_from.':'.$req->min_from.' '.$req->meridian_from;
        $data['time_to'] = $tt = $req->hour_to.':'.$req->min_to.' '.$req->meridian_to;
        $data['timestamp_from'] = strtotime($tf);
        $data['timestamp_to'] = strtotime($tt);
        $data['full'] = $tf.' - '.$tt;

        if($tf == $tt){
            return response()->json(['msg' => __('msg.invalid_time_slot'), 'ok' => FALSE]);
        }

        $this->tt->createTimeSlot($data);
        return Qs::jsonStoreOk();
    }

    public function use_time_slot(Request $req, $ttr_id)
    {
        $this->validate($req, ['ttr_id' => 'required'], [], ['ttr_id' => 'TimeTable Record']);

        $d = [];  //  Empty Current Time Slot Before Adding New
        $this->tt->deleteTimeSlots(['ttr_id' => $ttr_id]);
        $time_slots = $this->tt->getTimeSlotByTTR($req->ttr_id)->toArray();

        foreach($time_slots as $ts){
            $ts['ttr_id'] = $ttr_id;
            $this->tt->createTimeSlot($ts);
        }

        return redirect()->route('ttr.manage', $ttr_id)->with('flash_success', __('msg.update_ok'));

    }

    public function edit_time_slot($ts_id)
    {
        $d['tms'] = $this->tt->findTimeSlot($ts_id);
        return view('pages.support_team.timetables.time_slots.edit', $d);
    }

    public function update_time_slot(TSRequest $req, $ts_id)
    {
        $data = $req->all();
        $data['time_from'] = $tf =$req->hour_from.':'.$req->min_from.' '.$req->meridian_from;
        $data['time_to'] = $tt = $req->hour_to.':'.$req->min_to.' '.$req->meridian_to;
        $data['timestamp_from'] = strtotime($tf);
        $data['timestamp_to'] = strtotime($tt);
        $data['full'] = $tf.' - '.$tt;

        if($tf == $tt){
            return back()->with('flash_danger', __('msg.invalid_time_slot'));
        }

        $this->tt->updateTimeSlot($ts_id, $data);
        return redirect()->route('ttr.manage', $req->ttr_id)->with('flash_success', __('msg.update_ok'));
    }

    public function delete_time_slot($ts_id)
    {
        $this->tt->deleteTimeSlot($ts_id);
        return back()->with('flash_success', __('msg.delete_ok'));
    }


    /*********** RECORDS *************/

    protected function authorizeViewRecord($ttr_id)
    {
        $ttr = $this->tt->findRecord($ttr_id);
        if (!$ttr) {
            abort(404, __('Timetable record not found.'));
        }

        if (Qs::userIsTeamSA()) {
            return $ttr;
        }

        // Non-administrators must not view unpublished examination timetables
        if ($ttr->exam_id) {
            $exam = $this->exam->find($ttr->exam_id);
            if (!$exam || !$exam->isPublished()) {
                abort(403, __('Unpublished examination timetables are not accessible.'));
            }
        }

        $user = auth()->user();

        if (Qs::userIsStudent()) {
            $sr = \App\Models\StudentRecord::where('user_id', $user->id)->first();
            if (!$sr || $sr->my_class_id != $ttr->my_class_id) {
                abort(403, __('You are not authorized to view timetables of other classes.'));
            }
            return $ttr;
        }

        if (Qs::userIsParent()) {
            $hasChildInClass = \App\Models\StudentRecord::where('my_parent_id', $user->id)
                ->where('my_class_id', $ttr->my_class_id)
                ->exists();
            if (!$hasChildInClass) {
                abort(403, __('You are not authorized to view this timetable.'));
            }
            return $ttr;
        }

        if (Qs::userIsTeacher()) {
            $teachesClass = \App\Models\Section::where('my_class_id', $ttr->my_class_id)->where('teacher_id', $user->id)->exists()
                || \App\Models\Subject::where('my_class_id', $ttr->my_class_id)->where('teacher_id', $user->id)->exists()
                || \App\Models\TimeTable::where('ttr_id', $ttr->id)->whereHas('subject', function($q) use ($user) {
                    $q->where('teacher_id', $user->id);
                })->exists();

            if (!$teachesClass) {
                abort(403, __('You are not assigned to this class or timetable.'));
            }
            return $ttr;
        }

        abort(403);
    }

    public function edit_record($ttr_id)
    {
        $d['ttr'] = $ttr = $this->tt->findRecord($ttr_id);
        if (!$ttr) {
            abort(404, __('Timetable record not found.'));
        }
        $d['exams'] = $this->exam->getExam(['year' => $ttr->year]);
        $d['my_classes'] = $this->my_class->all();

        return view('pages.support_team.timetables.edit', $d);
    }

    public function show_record($ttr_id)
    {
        $ttr = $this->authorizeViewRecord($ttr_id);

        $d_time = [];
        $d['ttr'] = $ttr;
        $d['ttr_id'] = $ttr->id;
        $d['my_class'] = $this->my_class->find($ttr->my_class_id);

        $d['time_slots'] = $tms = $this->tt->getTimeSlotByTTR($ttr->id);
        $d['tts'] = $tts = $this->tt->getTimeTable(['ttr_id' => $ttr->id]);

        if($ttr->exam_id){
            $d['exam_id'] = $ttr->exam_id;
            $d['exam'] = $this->exam->find($ttr->exam_id);
            $d['days'] = $days = $tts->unique('exam_date')->pluck('exam_date');
            $d_date = 'exam_date';
        }

        else{
            $d['days'] = $days = $tts->unique('day')->pluck('day');
            $d_date = 'day';
        }

        foreach ($days as $day) {
            foreach ($tms as $tm) {
                $d_time[] = ['day' => $day, 'time' => $tm->full, 'subject' => $tts->where('ts_id', $tm->id)->where($d_date, $day)->first()->subject->name ?? NULL ];
            }
        }

        $d['d_time'] = collect($d_time);

        return view('pages.support_team.timetables.show', $d);
    }

    public function print_record($ttr_id)
    {
        $ttr = $this->authorizeViewRecord($ttr_id);

        $d_time = [];
        $d['ttr'] = $ttr;
        $d['ttr_id'] = $ttr->id;
        $d['my_class'] = $this->my_class->find($ttr->my_class_id);

        $d['time_slots'] = $tms = $this->tt->getTimeSlotByTTR($ttr->id);
        $d['tts'] = $tts = $this->tt->getTimeTable(['ttr_id' => $ttr->id]);

        if($ttr->exam_id){
            $d['exam_id'] = $ttr->exam_id;
            $d['exam'] = $this->exam->find($ttr->exam_id);
            $d['days'] = $days = $tts->unique('exam_date')->pluck('exam_date');
            $d_date = 'exam_date';
        }

        else{
            $d['days'] = $days = $tts->unique('day')->pluck('day');
            $d_date = 'day';
        }

        foreach ($days as $day) {
            foreach ($tms as $tm) {
                $d_time[] = ['day' => $day, 'time' => $tm->full, 'subject' => $tts->where('ts_id', $tm->id)->where($d_date, $day)->first()->subject->name ?? NULL ];
            }
        }

        $d['d_time'] = collect($d_time);
        $d['s'] = Setting::all()->flatMap(function($s){
            return [$s->type => $s->description];
        });

        return view('pages.support_team.timetables.print', $d);
    }

    public function store_record(TTRecordRequest $req)
    {
        $data = $req->all();
        $data['year'] = $this->year;
        $this->tt->createRecord($data);

        return Qs::jsonStoreOk();
    }

    public function update_record(TTRecordRequest $req, $id)
    {
        $data = $req->all();
        $this->tt->updateRecord($id, $data);

        return Qs::jsonUpdateOk();
    }

    public function delete_record($ttr_id)
    {
        $this->tt->deleteRecord($ttr_id);
        return back()->with('flash_success', __('msg.delete_ok'));
    }

    /**
     * Check if authenticated user is authorized to manage the given timetable record.
     */
    protected function canManageTimeTableRecord($ttr): bool
    {
        if (Qs::userIsTeamSA()) {
            return true;
        }

        if (Qs::userIsTeacher() && $ttr) {
            return \App\Models\Section::where('my_class_id', $ttr->my_class_id)
                ->where('teacher_id', auth()->id())
                ->exists();
        }

        return false;
    }
}
