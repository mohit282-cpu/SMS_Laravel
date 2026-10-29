<?php

namespace App\Http\Controllers;

use App\Helpers\Qs;
use App\Models\AdmissionInquiry;
use App\Models\EntranceResult;
use App\Models\Event;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LandingController extends Controller
{
    /**
     * Get a setting by key with a fallback
     */
    protected function getSetting($key, $default = '')
    {
        $setting = Setting::where('type', $key)->first();
        return $setting ? $setting->description : $default;
    }

    protected function getCommonSettings(): array
    {
        $allSettings = Setting::all()->pluck('description', 'type');

        return [
            'system_name' => $allSettings->get('system_name') ?: Qs::getSystemName(),
            'system_title' => $allSettings->get('system_title') ?: Qs::getSystemTitle(),
            'admissions_open' => (string) ($allSettings->get('admissions_open') ?? '1'),
            'entrance_results_visible' => (string) ($allSettings->get('entrance_results_visible') ?? '0'),
            'established_year' => (string) ($allSettings->get('established_year') ?? ''),
            'hero_title' => (string) ($allSettings->get('hero_title') ?: 'Inspiring Academic Excellence, Leadership & Character.'),
            'hero_subtitle' => (string) ($allSettings->get('hero_subtitle') ?: 'Nurturing young minds through holistic education, comprehensive academics, and character development.'),
            'stat_students' => (string) ($allSettings->get('stat_students') ?? ''),
            'stat_teachers' => (string) ($allSettings->get('stat_teachers') ?? ''),
            'stat_pass_rate' => (string) ($allSettings->get('stat_pass_rate') ?? ''),
            'stat_experience' => (string) ($allSettings->get('stat_experience') ?? ''),
            'director_name' => (string) ($allSettings->get('director_name') ?? ''),
            'director_title' => (string) ($allSettings->get('director_title') ?? ''),
            'principal_name' => (string) ($allSettings->get('principal_name') ?? ''),
            'principal_title' => (string) ($allSettings->get('principal_title') ?? ''),
            'phone' => (string) ($allSettings->get('phone') ?: ''),
            'address' => (string) ($allSettings->get('address') ?: ''),
            'system_email' => (string) ($allSettings->get('system_email') ?: ''),
        ];
    }

    /**
     * Institutional Homepage
     */
    public function index()
    {
        $settings = $this->getCommonSettings();

        // Fetch latest 3 campus events explicitly published and visible to guests
        $recentEvents = Event::visibleTo(null)
            ->orderBy('id', 'desc')
            ->take(3)
            ->get();

        return view('landing.index', compact('settings', 'recentEvents'));
    }

    /**
     * About Us & Institutional Profile
     */
    public function about()
    {
        $settings = $this->getCommonSettings();

        return view('landing.about', compact('settings'));
    }

    /**
     * Campus Life & Events
     */
    public function events()
    {
        $settings = $this->getCommonSettings();

        $events = Event::visibleTo(null)
            ->orderBy('id', 'desc')
            ->paginate(9);

        return view('landing.events', compact('settings', 'events'));
    }

    /**
     * Contact & Admissions Desk
     */
    public function contact()
    {
        $settings = $this->getCommonSettings();

        return view('landing.contact', compact('settings'));
    }

    /**
     * Handle Public Prospective Student Inquiry
     */
    public function submit_inquiry(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:100',
            'email' => 'nullable|email|max:255',
            'academic_level' => 'nullable|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        AdmissionInquiry::create($validated);

        return back()->with('success_msg', 'Thank you, ' . htmlspecialchars($validated['full_name']) . '. Your admissions inquiry has been recorded successfully. Our admissions desk will contact you shortly.');
    }

    /**
     * New Admission & Entrance Examination Result Portal
     */
    public function entrance_results()
    {
        $settings = $this->getCommonSettings();
        $isVisible = ($settings['entrance_results_visible'] ?? '0') === '1';
        $isSuperAdmin = auth()->check() && Qs::userIsSuperAdmin();

        $batches = EntranceResult::select('batch_year')->distinct()->orderBy('batch_year', 'desc')->pluck('batch_year');
        $exams = EntranceResult::select('exam_name')->distinct()->orderBy('exam_name', 'desc')->pluck('exam_name');
        $classes = EntranceResult::select('class_name')->distinct()->orderBy('class_name', 'asc')->pluck('class_name');

        return view('landing.entrance_results', compact('batches', 'exams', 'classes', 'settings', 'isVisible', 'isSuperAdmin'));
    }

    /**
     * Search Endpoint for Entrance Results (supports JSON AJAX and Form Submit)
     */
    public function entrance_result_search(Request $request)
    {
        $settings = $this->getCommonSettings();
        $isVisible = ($settings['entrance_results_visible'] ?? '0') === '1';
        $isSuperAdmin = auth()->check() && Qs::userIsSuperAdmin();

        if (!$isVisible && !$isSuperAdmin) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'status' => 'inactive',
                    'message' => 'Entrance Examination Results are currently closed or under administrative review.'
                ], 403);
            }
            return redirect()->route('admissions.results');
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'symbol_no' => 'required|string|max:50',
            'batch_year' => 'nullable|string|max:50',
            'exam_name' => 'nullable|string|max:100',
            'class_name' => 'nullable|string|max:50',
            'verification_query' => 'nullable|string|max:100',
        ], [
            'symbol_no.required' => 'A valid Student Symbol Number is required to fetch entrance examination results.',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => $validator->errors()->first('symbol_no') ?: 'Invalid input parameters provided.'
                ], 400);
            }
            return redirect()->route('admissions.results')->with('error_msg', $validator->errors()->first('symbol_no') ?: 'A valid Student Symbol Number is required.');
        }

        $validated = $validator->validated();
        $symbol = trim($validated['symbol_no']);
        $batch = !empty($validated['batch_year']) ? trim($validated['batch_year']) : null;
        $exam = !empty($validated['exam_name']) ? trim($validated['exam_name']) : null;
        $class = !empty($validated['class_name']) ? trim($validated['class_name']) : null;
        $verification = !empty($validated['verification_query']) ? trim($validated['verification_query']) : null;

        $query = EntranceResult::query();

        // Exact symbol number match (strictly prevents wildcards or broad enumeration)
        $query->where('symbol_no', $symbol);

        if (!empty($batch)) {
            $query->where('batch_year', $batch);
        }
        if (!empty($exam)) {
            $query->where('exam_name', $exam);
        }
        if (!empty($class)) {
            $query->where('class_name', $class);
        }

        // Secondary verification factor if provided: verify exact roll_no or student_name match
        if (!empty($verification)) {
            $query->where(function ($q) use ($verification) {
                $q->where('student_name', $verification)
                  ->orWhere('student_name', 'like', '%' . $verification . '%')
                  ->orWhere('roll_no', $verification);
            });
        }

        $result = $query->first();

        if (!$result) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'status' => 'not_found',
                    'message' => 'No examination record matched your criteria. Please verify your symbol number, class, and examination.'
                ], 404);
            }
            $results = collect();
        } else {
            $results = collect([$result]);
        }

        $initialData = $result ? [
            'id' => $result->id,
            'batch_year' => $result->batch_year,
            'exam_name' => $result->exam_name,
            'class_name' => $result->class_name,
            'section_name' => $result->section_name,
            'symbol_no' => $result->symbol_no,
            'roll_no' => $result->roll_no,
            'student_name' => $result->student_name,
            'attendance' => $result->attendance,
            'gpa' => number_format((float)$result->gpa, 2),
            'remarks' => $result->remarks,
            'marks' => $result->marks,
            'created_at' => (string)$result->created_at,
        ] : null;

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => 'success',
                'data' => $initialData
            ]);
        }

        $batches = EntranceResult::select('batch_year')->distinct()->orderBy('batch_year', 'desc')->pluck('batch_year');
        $exams = EntranceResult::select('exam_name')->distinct()->orderBy('exam_name', 'desc')->pluck('exam_name');
        $classes = EntranceResult::select('class_name')->distinct()->orderBy('class_name', 'asc')->pluck('class_name');
        $settings = $this->getCommonSettings();

        return view('landing.entrance_results', compact('batches', 'exams', 'classes', 'results', 'settings', 'symbol', 'isVisible', 'isSuperAdmin', 'initialData'));
    }
}
