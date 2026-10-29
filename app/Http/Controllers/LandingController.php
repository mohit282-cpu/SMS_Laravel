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

    protected function getCommonSettings()
    {
        return [
            'system_name' => Qs::getSystemName(),
            'system_title' => Qs::getSystemTitle(),
            'admissions_open' => $this->getSetting('admissions_open', '1'),
            'entrance_results_visible' => $this->getSetting('entrance_results_visible', '0'),
            'established_year' => $this->getSetting('established_year', '2052 B.S. (1995 A.D.)'),
            'hero_title' => $this->getSetting('hero_title', 'Inspiring Academic Excellence, Leadership & Character.'),
            'hero_subtitle' => $this->getSetting('hero_subtitle', 'Nurturing young minds from Early Childhood through Grade 12 with forward-thinking STEM laboratories, comprehensive humanities, and dedicated character discipline in Koshi Province.'),
            'stat_students' => $this->getSetting('stat_students', '1,850+'),
            'stat_teachers' => $this->getSetting('stat_teachers', '95+'),
            'stat_pass_rate' => $this->getSetting('stat_pass_rate', '99.4%'),
            'stat_experience' => $this->getSetting('stat_experience', '30+ Years'),
            'director_name' => $this->getSetting('director_name', 'Mrs. Sunita Sharma'),
            'director_title' => $this->getSetting('director_title', 'Academic Director (M.Sc., 20+ Yrs Curriculum)'),
            'principal_name' => $this->getSetting('principal_name', 'Prof. Dr. K. P. Adhikari'),
            'principal_title' => $this->getSetting('principal_title', 'Principal (Ph.D. in Education Administration)'),
            'phone' => $this->getSetting('phone', '+977-25-560000'),
            'address' => $this->getSetting('address', 'Ward No. 3, Main Highway Corridor, Itahari-5, Sunsari, Koshi Province, Nepal'),
            'system_email' => $this->getSetting('system_email', 'info@apexschool.edu.np'),
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
        $batch = substr(trim(str_replace(["\0", "\x00"], '', (string) $request->input('batch_year', ''))), 0, 50);
        $exam = substr(trim(str_replace(["\0", "\x00"], '', (string) $request->input('exam_name', ''))), 0, 100);
        $class = substr(trim(str_replace(["\0", "\x00"], '', (string) $request->input('class_name', ''))), 0, 50);
        $symbol = substr(trim(str_replace(["\0", "\x00"], '', (string) $request->input('symbol_no', ''))), 0, 50);
        $verification = substr(trim(str_replace(["\0", "\x00"], '', (string) $request->input('verification_query', ''))), 0, 100);

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

        // Require exact symbol number to prevent candidate enumeration and privacy leakage
        if (empty($symbol) || strlen($symbol) < 1) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'A valid Student Symbol Number is required to fetch entrance examination results.'
                ], 400);
            }
            return redirect()->route('admissions.results')->with('error_msg', 'A valid Student Symbol Number is required.');
        }

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

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => 'success',
                'data' => [
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
                ]
            ]);
        }

        $batches = EntranceResult::select('batch_year')->distinct()->orderBy('batch_year', 'desc')->pluck('batch_year');
        $exams = EntranceResult::select('exam_name')->distinct()->orderBy('exam_name', 'desc')->pluck('exam_name');
        $classes = EntranceResult::select('class_name')->distinct()->orderBy('class_name', 'asc')->pluck('class_name');
        $settings = ['system_name' => Qs::getSystemName()];

        return view('landing.entrance_results', compact('batches', 'exams', 'classes', 'results', 'settings', 'symbol'));
    }
}
