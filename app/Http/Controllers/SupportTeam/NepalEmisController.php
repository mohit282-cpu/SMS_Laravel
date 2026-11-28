<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\GradingScale;
use App\Models\NepalDistrict;
use App\Models\NepalEducationLevel;
use App\Models\NepalProvince;
use App\Models\SchoolProfile;
use App\Models\SeeCandidate;
use App\Models\StudentRecord;
use App\Services\ErpSupportService;
use App\Services\IemisReportService;
use Illuminate\Http\Request;

class NepalEmisController extends Controller
{
    protected IemisReportService $iemis;
    protected ErpSupportService $erp;

    public function __construct(IemisReportService $iemis, ErpSupportService $erp)
    {
        $this->middleware('auth');
        $this->middleware('teamSA');
        $this->iemis = $iemis;
        $this->erp = $erp;
    }

    public function index(Request $request)
    {
        $session = $request->query('session') ?: Qs::getCurrentSession();
        $profile = SchoolProfile::current() ?: new SchoolProfile(['is_active' => true, 'school_type' => 'institutional', 'boarding_type' => 'day', 'calendar_preference' => 'both']);
        $iemisSummary = $this->iemis->generateSummary($session);

        $provinces = NepalProvince::orderBy('id')->get();
        $districts = NepalDistrict::orderBy('name')->get();
        $levels = NepalEducationLevel::orderBy('id')->get();
        $curricula = Curriculum::with(['educationLevel', 'subjects'])->orderBy('id', 'desc')->get();
        $gradingScales = GradingScale::orderBy('academic_year', 'desc')->orderBy('min_percentage', 'desc')->get();
        $seeCandidates = SeeCandidate::with('studentRecord.user')->where('academic_year', $session)->orderBy('id', 'desc')->get();
        $students = StudentRecord::with(['user', 'my_class'])->where('grad', 0)->orderBy('adm_no')->get();

        return view('pages.support_team.nepal_emis.index', compact(
            'session', 'profile', 'iemisSummary', 'provinces', 'districts',
            'levels', 'curricula', 'gradingScales', 'seeCandidates', 'students'
        ));
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'name_ne' => 'nullable|string|max:191',
            'emis_code' => 'nullable|string|max:50',
            'school_type' => 'required|in:community,institutional,montessori,religious,technical,other',
            'boarding_type' => 'required|in:day,boarding,both',
            'province_id' => 'nullable|exists:nepal_provinces,id',
            'district_id' => 'nullable|exists:nepal_districts,id',
            'local_level_manual' => 'nullable|string|max:150',
            'ward_no' => 'nullable|integer|min:1|max:35',
            'tole' => 'nullable|string|max:150',
            'pan_number' => 'nullable|string|max:30',
            'vat_number' => 'nullable|string|max:30',
            'established_bs' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'website' => 'nullable|string|max:191',
            'calendar_preference' => 'required|in:bs,ad,both',
        ]);

        $profile = SchoolProfile::current();
        if ($profile) {
            $profile->update($validated);
        } else {
            $validated['is_active'] = true;
            $profile = SchoolProfile::create($validated);
        }

        $this->erp->audit('emis.profile_update', 'administration', 'Updated School EMIS Profile', $profile, ['emis_code' => $profile->emis_code]);

        return back()->with('flash_success', __('School EMIS Profile updated successfully.'));
    }

    public function storeCurriculum(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'version' => 'required|string|max:40',
            'education_level_id' => 'nullable|exists:nepal_education_levels,id',
            'stream' => 'nullable|string|max:80',
            'effective_from_bs' => 'nullable|string|max:10',
            'subject_code' => 'nullable|string|max:40',
            'subject_name' => 'required|string|max:150',
            'subject_name_ne' => 'nullable|string|max:180',
            'subject_type' => 'required|in:compulsory,optional,elective,practical,internal',
            'theory_max' => 'required|integer|min:0|max:200',
            'theory_pass' => 'required|integer|min:0|max:200',
            'practical_max' => 'required|integer|min:0|max:200',
            'practical_pass' => 'required|integer|min:0|max:200',
            'credit_hour' => 'required|numeric|min:0.5|max:20',
        ]);

        $curriculum = Curriculum::firstOrCreate(
            ['name' => $validated['name'], 'version' => $validated['version']],
            [
                'education_level_id' => $validated['education_level_id'] ?? null,
                'stream' => $validated['stream'] ?? null,
                'effective_from_bs' => $validated['effective_from_bs'] ?? null,
                'is_active' => true,
            ]
        );

        CurriculumSubject::create([
            'curriculum_id' => $curriculum->id,
            'subject_code' => $validated['subject_code'] ?? null,
            'subject_name' => $validated['subject_name'],
            'subject_name_ne' => $validated['subject_name_ne'] ?? null,
            'subject_type' => $validated['subject_type'],
            'theory_max' => $validated['theory_max'],
            'theory_pass' => $validated['theory_pass'],
            'practical_max' => $validated['practical_max'],
            'practical_pass' => $validated['practical_pass'],
            'credit_hour' => $validated['credit_hour'],
        ]);

        return back()->with('flash_success', __('CDC Curriculum & subject blueprint saved.'));
    }

    public function storeSeeCandidate(Request $request)
    {
        $validated = $request->validate([
            'student_record_id' => 'required|exists:student_records,id',
            'academic_year' => 'required|string|max:40',
            'symbol_number' => 'nullable|string|max:40',
            'registration_number' => 'nullable|string|max:60',
            'optional_subject_1' => 'nullable|string|max:120',
            'optional_subject_2' => 'nullable|string|max:120',
            'gpa' => 'nullable|numeric|min:0|max:4',
            'overall_grade' => 'nullable|string|max:10',
            'result_status' => 'required|in:registered,eligible,appeared,passed,ng,absent,withheld',
        ]);

        SeeCandidate::updateOrCreate(
            ['student_record_id' => $validated['student_record_id'], 'academic_year' => $validated['academic_year']],
            $validated + ['is_eligible' => $request->boolean('is_eligible', true)]
        );

        return back()->with('flash_success', __('SEE candidate record updated.'));
    }

    public function exportJson(Request $request)
    {
        $session = $request->query('session') ?: Qs::getCurrentSession();
        $summary = $this->iemis->generateSummary($session);

        return response()->json($summary, 200, [
            'Content-Disposition' => 'attachment; filename="iemis-summary-' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $session) . '.json"',
        ]);
    }
}
