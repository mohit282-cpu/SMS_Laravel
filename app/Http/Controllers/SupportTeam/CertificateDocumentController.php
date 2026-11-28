<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\NepaliHelper;
use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\MyClass;
use App\Models\SchoolProfile;
use App\Models\Section;
use App\Models\StudentCertificate;
use App\Models\StudentDocument;
use App\Models\StudentRecord;
use App\Services\ErpSupportService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateDocumentController extends Controller
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

        $certQuery = StudentCertificate::with(['studentRecord.user', 'studentRecord.my_class'])
            ->orderBy('id', 'desc');
        $docQuery = StudentDocument::with(['studentRecord.user', 'uploader'])
            ->orderBy('id', 'desc');

        if ($allowedSrIds !== null) {
            $certQuery->whereIn('student_record_id', $allowedSrIds ?: [0]);
            $docQuery->whereIn('student_record_id', $allowedSrIds ?: [0]);
        }

        $certificates = $certQuery->paginate(15, ['*'], 'cert_page');
        $documents = $docQuery->paginate(15, ['*'], 'doc_page');

        $students = $allowedSrIds === null
            ? StudentRecord::with(['user', 'my_class'])->where('grad', 0)->orderBy('adm_no')->get()
            : StudentRecord::with(['user', 'my_class'])->whereIn('id', $allowedSrIds)->get();

        $verifiedCertificate = null;
        if ($request->filled('verify_code')) {
            $code = trim((string) $request->query('verify_code'));
            $verifiedCertificate = StudentCertificate::with(['studentRecord.user', 'studentRecord.my_class'])
                ->where('verification_code', $code)
                ->orWhere('certificate_number', $code)
                ->first();
        }

        return view('pages.support_team.certificates.index', compact(
            'certificates', 'documents', 'students', 'verifiedCertificate'
        ));
    }

    public function storeCertificate(Request $request)
    {
        if (!Qs::userIsTeamSA()) {
            abort(403);
        }

        $validated = $request->validate([
            'student_record_id' => 'required|exists:student_records,id',
            'certificate_type' => 'required|in:character,transfer,bonafide,study,leaving',
            'issued_date_ad' => 'nullable|date',
            'issued_date_bs' => 'nullable|string|max:20',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $dates = $this->erp->resolveDualDates($validated['issued_date_ad'] ?? null, $validated['issued_date_bs'] ?? null, true);

        $cert = StudentCertificate::create([
            'student_record_id' => $validated['student_record_id'],
            'certificate_type' => $validated['certificate_type'],
            'certificate_number' => strtoupper(substr($validated['certificate_type'], 0, 3)) . '-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
            'verification_code' => strtoupper(Str::random(12)),
            'issued_date_ad' => $dates['ad'],
            'issued_date_bs' => $dates['bs'],
            'issued_by' => Auth::id(),
            'remarks' => $validated['remarks'] ?? null,
        ]);

        $this->erp->audit('certificate.issue', 'students', "Issued {$cert->certificate_type} certificate {$cert->certificate_number}", $cert);

        return back()->with('flash_success', __('Certificate :num issued cleanly.', ['num' => $cert->certificate_number]));
    }

    public function printCertificate($id)
    {
        $cert = StudentCertificate::with(['studentRecord.user', 'studentRecord.my_class', 'studentRecord.section'])->findOrFail($id);
        if (!$this->erp->canAccessStudentRecord(Auth::user(), (int) $cert->student_record_id)) {
            abort(403);
        }

        $school = SchoolProfile::current();
        return view('pages.support_team.certificates.print', compact('cert', 'school'));
    }

    public function storeDocument(Request $request)
    {
        if (!Qs::userIsTeamSA()) {
            abort(403);
        }

        $validated = $request->validate([
            'student_record_id' => 'required|exists:student_records,id',
            'document_type' => 'required|string|max:80',
            'title' => 'required|string|max:191',
            'document_file' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $stored = $this->erp->storePrivateUpload($request->file('document_file'), 'student_documents');

        $doc = StudentDocument::create([
            'student_record_id' => $validated['student_record_id'],
            'document_type' => $validated['document_type'],
            'title' => $validated['title'],
            'file_path' => $stored['path'],
            'original_name' => $stored['name'],
            'uploaded_by' => Auth::id(),
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return back()->with('flash_success', __('Document ":title" uploaded to student vault.', ['title' => $doc->title]));
    }

    public function downloadDocument($id)
    {
        $doc = StudentDocument::findOrFail($id);
        if (!$this->erp->canAccessStudentRecord(Auth::user(), (int) $doc->student_record_id)) {
            abort(403);
        }
        if (!Storage::disk('local')->exists($doc->file_path)) {
            abort(404);
        }

        return Storage::disk('local')->download($doc->file_path, $doc->original_name ?: basename($doc->file_path));
    }

    public function idCards(Request $request)
    {
        if (!Qs::userIsTeamSA()) {
            abort(403);
        }

        $classes = MyClass::orderBy('name')->get();
        $sections = Section::with('my_class')->orderBy('name')->get();
        $cardMode = $request->query('mode', 'student');

        $studentCards = collect();
        $staffCards = collect();

        if ($cardMode === 'staff') {
            $staffCards = User::whereIn('user_type', Qs::getStaff())->orderBy('name')->get();
        } else {
            $query = StudentRecord::with(['user.blood_group', 'my_class', 'section'])->where('grad', 0);
            if ($request->filled('my_class_id')) {
                $query->where('my_class_id', $request->integer('my_class_id'));
            }
            if ($request->filled('section_id')) {
                $query->where('section_id', $request->integer('section_id'));
            }
            $studentCards = $query->orderBy('adm_no')->take(60)->get();
        }

        $school = SchoolProfile::current();

        return view('pages.support_team.documents.id_cards', compact(
            'classes', 'sections', 'cardMode', 'studentCards', 'staffCards', 'school'
        ));
    }

    public function admitCards(Request $request)
    {
        if (!Qs::userIsTeamSA()) {
            abort(403);
        }

        $exams = Exam::orderBy('id', 'desc')->get();
        $classes = MyClass::orderBy('name')->get();
        $selectedExam = $request->filled('exam_id') ? Exam::find($request->integer('exam_id')) : $exams->first();
        $selectedClass = $request->filled('my_class_id') ? MyClass::find($request->integer('my_class_id')) : $classes->first();

        $students = ($selectedClass)
            ? StudentRecord::with(['user', 'my_class', 'section'])
                ->where('my_class_id', $selectedClass->id)
                ->where('grad', 0)
                ->orderBy('adm_no')
                ->get()
            : collect();

        $school = SchoolProfile::current();

        return view('pages.support_team.documents.admit_cards', compact(
            'exams', 'classes', 'selectedExam', 'selectedClass', 'students', 'school'
        ));
    }
}
