<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Helpers\Mk;
use App\Http\Requests\Student\StudentRecordCreate;
use App\Http\Requests\Student\StudentRecordUpdate;
use App\Repositories\LocationRepo;
use App\Repositories\MyClassRepo;
use App\Repositories\StudentRepo;
use App\Repositories\UserRepo;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\StudentRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentRecordController extends Controller
{
    protected $loc, $my_class, $user, $student;

   public function __construct(LocationRepo $loc, MyClassRepo $my_class, UserRepo $user, StudentRepo $student)
   {
       $this->middleware('teamSA', ['only' => ['edit','update', 'reset_pass', 'create', 'store', 'graduated', 'not_graduated'] ]);
       $this->middleware('super_admin', ['only' => ['destroy',] ]);

        $this->loc = $loc;
        $this->my_class = $my_class;
        $this->user = $user;
        $this->student = $student;
   }

    public function index()
    {
        $my_classes = $this->my_class->all();
        if ($my_classes->isNotEmpty()) {
            return redirect()->route('students.list', $my_classes->first()->id);
        }
        $d['my_classes'] = $my_classes;
        return view('pages.support_team.students.list', $d);
    }

    public function reset_pass($st_id)
    {
        if (!Qs::userIsTeamSA()) {
            abort(403);
        }

        $st_id = Qs::decodeHash($st_id) ?: (is_numeric($st_id) ? (int)$st_id : null);
        if (!$st_id) {
            return back()->with('flash_danger', __('msg.rnf'));
        }

        $targetUser = $this->user->find($st_id);
        if (!$targetUser) {
            return back()->with('flash_danger', __('msg.rnf'));
        }

        if ($targetUser->user_type !== 'student') {
            abort(403, __('Only student accounts can be reset here.'));
        }

        $tempPass = Str::random(10);
        $this->user->update($st_id, ['password' => Hash::make($tempPass)]);

        \Illuminate\Support\Facades\Log::info("Student password reset for user ID {$st_id} by staff " . Auth::id());

        return back()->with('flash_success', __('msg.p_reset') . ' New temporary password: ' . $tempPass)
            ->with('temp_password', $tempPass);
    }

    public function create()
    {
        $data['my_classes'] = $this->my_class->all();
        $data['parents'] = $this->user->getUserByType('parent');
        $data['dorms'] = $this->student->getAllDorms();
        $data['states'] = $this->loc->getStates();
        $data['nationals'] = $this->loc->getAllNationals();
        return view('pages.support_team.students.add', $data);
    }

    public function store(StudentRecordCreate $req)
    {
       $data =  $req->only(Qs::getUserRecord());
       $sr =  $req->only(Qs::getStudentData());

        if ($req->section_id && $req->my_class_id) {
            $validSection = \App\Models\Section::where('id', $req->section_id)->where('my_class_id', $req->my_class_id)->exists();
            if (!$validSection) {
                return response()->json(['msg' => __('Selected section does not belong to the selected class.')], 422);
            }
        }

        $ct = $this->my_class->findTypeByClass($req->my_class_id)->code;
       /* $ct = ($ct == 'J') ? 'JSS' : $ct;
        $ct = ($ct == 'S') ? 'SS' : $ct;*/

        $data['user_type'] = 'student';
        $data['name'] = ucwords($req->name);
        $data['code'] = strtoupper(Str::random(10));
        $tempPassword = Str::random(10);
        $data['password'] = Hash::make($tempPassword);
        session()->flash('temp_password', $tempPassword);
        $data['photo'] = Qs::getDefaultUserImage();
        $adm_no = $req->adm_no;
        $data['username'] = strtoupper(Qs::getAppCode().'/'.$ct.'/'.$sr['year_admitted'].'/'.($adm_no ?: mt_rand(1000, 99999)));

        if($req->hasFile('photo')) {
            $photo = $req->file('photo');
            $f = Qs::getFileMetaData($photo);
            $f['name'] = 'photo.' . $f['ext'];
            $f['path'] = $photo->storeAs(Qs::getUploadPath('student').$data['code'], $f['name']);
            $data['photo'] = asset('storage/' . $f['path']);
        }

        if(!empty($data['dob'])) {
            // If entered as BS date YYYY-MM-DD (year >= 2000), convert to AD date
            $dobBsConv = \App\Helpers\NepaliHelper::bsToAd($data['dob']);
            if($dobBsConv) {
                $data['dob'] = $dobBsConv;
            }
        }

        $user = $this->user->create($data); // Create User

        $sr['adm_no'] = $data['username'];
        $sr['user_id'] = $user->id;
        $sr['session'] = Qs::getCurrentSession();

        $this->student->createRecord($sr); // Create Student
        return Qs::jsonStoreOk();
    }

    public function listByClass($class_id)
    {
        $data['my_class'] = $mc = $this->my_class->getMC(['id' => $class_id])->first();
        $data['students'] = $this->student->findStudentsByClass($class_id);
        $data['sections'] = $this->my_class->getClassSections($class_id);

        return is_null($mc) ? Qs::goWithDanger() : view('pages.support_team.students.list', $data);
    }

    public function graduated()
    {
        if (!Qs::userIsTeamSA()) {
            abort(403);
        }

        $data['my_classes'] = $this->my_class->all();
        $data['students'] = $this->student->allGradStudents();

        return view('pages.support_team.students.graduated', $data);
    }

    public function not_graduated($sr_id)
    {
        if (!Qs::userIsTeamSA()) {
            abort(403);
        }

        $decoded_id = Qs::decodeHash($sr_id) ?: (is_numeric($sr_id) ? (int)$sr_id : null);
        if (!$decoded_id) {
            return back()->with('flash_danger', __('msg.rnf'));
        }

        $sr = StudentRecord::find($decoded_id);
        if (!$sr) {
            return back()->with('flash_danger', __('msg.rnf'));
        }

        $d['grad'] = 0;
        $d['grad_date'] = NULL;
        $d['session'] = Qs::getSetting('current_session');
        $this->student->updateRecord($sr->id, $d);

        \Illuminate\Support\Facades\Log::info("Student record {$sr->id} marked as not graduated by user " . \Illuminate\Support\Facades\Auth::id());

        return back()->with('flash_success', __('msg.update_ok'));
    }

    public function show($sr_id)
    {
        $sr_id = Qs::decodeHash($sr_id);
        if(!$sr_id){return Qs::goWithDanger();}

        $data['sr'] = $this->student->getRecord(['id' => $sr_id])->first();
        if(!$data['sr']){return Qs::goWithDanger();}

        /* Prevent Other Students/Parents from viewing Profile of others */
        if(Auth::user()->id != $data['sr']->user_id && !Qs::userIsTeamSAT() && !Qs::userIsMyChild($data['sr']->user_id, Auth::user()->id)){
            return redirect(route('dashboard'))->with('pop_error', __('msg.denied'));
        }

        return view('pages.support_team.students.show', $data);
    }

    public function edit($sr_id)
    {
        $sr_id = Qs::decodeHash($sr_id);
        if(!$sr_id){return Qs::goWithDanger();}

        $data['sr'] = $this->student->getRecord(['id' => $sr_id])->first();
        if(!$data['sr']){return Qs::goWithDanger();}
        $data['my_classes'] = $this->my_class->all();
        $data['parents'] = $this->user->getUserByType('parent');
        $data['dorms'] = $this->student->getAllDorms();
        $data['states'] = $this->loc->getStates();
        $data['nationals'] = $this->loc->getAllNationals();
        return view('pages.support_team.students.edit', $data);
    }

    public function update(StudentRecordUpdate $req, $sr_id)
    {
        $sr_id = Qs::decodeHash($sr_id);
        if(!$sr_id){return Qs::goWithDanger();}

        $sr = $this->student->getRecord(['id' => $sr_id])->first();
        $d =  $req->only(Qs::getUserRecord());
        $d['name'] = ucwords($req->name);

        if($req->hasFile('photo')) {
            $photo = $req->file('photo');
            $f = Qs::getFileMetaData($photo);
            $f['name'] = 'photo.' . $f['ext'];
            $f['path'] = $photo->storeAs(Qs::getUploadPath('student').$sr->user->code, $f['name']);
            $d['photo'] = asset('storage/' . $f['path']);
        }

        if(!empty($d['dob'])) {
            $dobBsConv = \App\Helpers\NepaliHelper::bsToAd($d['dob']);
            if($dobBsConv) {
                $d['dob'] = $dobBsConv;
            }
        }

        $this->user->update($sr->user->id, $d); // Update User Details

        $srec = $req->only(Qs::getStudentData());

        if (!empty($srec['section_id']) && !empty($srec['my_class_id'])) {
            $validSection = \App\Models\Section::where('id', $srec['section_id'])->where('my_class_id', $srec['my_class_id'])->exists();
            if (!$validSection) {
                return response()->json(['msg' => __('Selected section does not belong to the selected class.')], 422);
            }
        }

        $this->student->updateRecord($sr_id, $srec); // Update St Rec

        // Preserve historical results across class/section transfers without destructive purge

        return Qs::jsonUpdateOk();
    }

    public function destroy($st_id)
    {
        $st_id = Qs::decodeHash($st_id);
        if(!$st_id){return Qs::goWithDanger();}

        $sr = $this->student->getRecord(['user_id' => $st_id])->first();
        $path = Qs::getUploadPath('student').$sr->user->code;
        Storage::exists($path) ? Storage::deleteDirectory($path) : false;
        $this->user->delete($sr->user->id);

        return back()->with('flash_success', __('msg.del_ok'));
    }

}
