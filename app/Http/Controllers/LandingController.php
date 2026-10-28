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

    /**
     * Institutional Homepage
     */
    public function index()
    {
        $settings = [
            'system_name' => Qs::getSystemName(),
            'system_title' => Qs::getSystemTitle(),
            'admissions_open' => $this->getSetting('admissions_open', '1'),
            'established_year' => $this->getSetting('established_year', '2052 B.S. (1995 A.D.)'),
            'hero_title' => $this->getSetting('hero_title', 'Inspiring Academic Excellence, Leadership & Character.'),
            'hero_subtitle' => $this->getSetting('hero_subtitle', 'Nurturing young minds from Early Childhood through Grade 12 with forward-thinking STEM laboratories, comprehensive humanities, and dedicated character discipline in Koshi Province.'),
            'stat_students' => $this->getSetting('stat_students', '1,850+'),
            'stat_teachers' => $this->getSetting('stat_teachers', '95+'),
            'stat_pass_rate' => $this->getSetting('stat_pass_rate', '99.4%'),
            'stat_experience' => $this->getSetting('stat_experience', '30+ Years'),
            'phone' => $this->getSetting('phone', '+977-25-560000'),
            'address' => $this->getSetting('address', 'Ward No. 3, Main Highway Corridor, Itahari-5, Sunsari, Koshi Province, Nepal'),
            'system_email' => $this->getSetting('system_email', 'info@apexschool.edu.np'),
        ];

        // Fetch latest 3 campus events
        $recentEvents = Event::where('is_published', true)
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
        $settings = [
            'system_name' => Qs::getSystemName(),
            'established_year' => $this->getSetting('established_year', '2052 B.S. (1995 A.D.)'),
            'director_name' => $this->getSetting('director_name', 'Er. Bijay Shrestha'),
            'director_title' => $this->getSetting('director_title', 'Executive Director & Founder'),
            'principal_name' => $this->getSetting('principal_name', 'Prof. Dr. Madhav Prasad Khatiwada'),
            'principal_title' => $this->getSetting('principal_title', 'Principal & Academic Head'),
            'vice_principal_name' => $this->getSetting('vice_principal_name', 'Mrs. Sunita Thapa'),
            'vice_principal_title' => $this->getSetting('vice_principal_title', 'Vice Principal (Student Welfare)'),
            'phone' => $this->getSetting('phone', '+977-25-560000'),
            'address' => $this->getSetting('address', 'Ward No. 3, Main Highway Corridor, Itahari-5, Sunsari, Koshi Province, Nepal'),
            'system_email' => $this->getSetting('system_email', 'info@apexschool.edu.np'),
        ];

        return view('landing.about', compact('settings'));
    }

    /**
     * Campus Life & Events
     */
    public function events()
    {
        $settings = [
            'system_name' => Qs::getSystemName(),
            'phone' => $this->getSetting('phone', '+977-25-560000'),
            'system_email' => $this->getSetting('system_email', 'info@apexschool.edu.np'),
        ];

        $events = Event::where('is_published', true)
            ->orderBy('id', 'desc')
            ->paginate(9);

        return view('landing.events', compact('settings', 'events'));
    }

    /**
     * Contact & Admissions Desk
     */
    public function contact()
    {
        $settings = [
            'system_name' => Qs::getSystemName(),
            'phone' => $this->getSetting('phone', '+977-25-560000'),
            'address' => $this->getSetting('address', 'Ward No. 3, Main Highway Corridor, Itahari-5, Sunsari, Koshi Province, Nepal'),
            'system_email' => $this->getSetting('system_email', 'info@apexschool.edu.np'),
        ];

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
        $batches = EntranceResult::select('batch_year')->distinct()->orderBy('batch_year', 'desc')->pluck('batch_year');
        $exams = EntranceResult::select('exam_name')->distinct()->orderBy('exam_name', 'desc')->pluck('exam_name');
        $classes = EntranceResult::select('class_name')->distinct()->orderBy('class_name', 'asc')->pluck('class_name');

        $settings = [
            'system_name' => Qs::getSystemName(),
            'phone' => $this->getSetting('phone', '+977-25-560000'),
        ];

        return view('landing.entrance_results', compact('batches', 'exams', 'classes', 'settings'));
    }

    /**
     * Search Endpoint for Entrance Results (supports JSON AJAX and Form Submit)
     */
    public function entrance_result_search(Request $request)
    {
        $batch = trim($request->input('batch_year', ''));
        $exam = trim($request->input('exam_name', ''));
        $class = trim($request->input('class_name', ''));
        $symbol = trim($request->input('symbol_no', ''));
        $verification = trim($request->input('verification_query', ''));
        $search = trim($request->input('search_query', ''));

        if (empty($search) && !empty($symbol)) {
            $search = $symbol;
        }

        if (empty($search) && empty($verification)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'status' => 'error', 'message' => 'Please enter a Student Symbol Number or Name.'], 400);
            }
            return redirect()->route('admissions.results')->with('error_msg', 'Please enter a Symbol Number or Student Name.');
        }

        $query = EntranceResult::query();

        if (!empty($batch)) {
            $query->where('batch_year', $batch);
        }
        if (!empty($exam)) {
            $query->where('exam_name', $exam);
        }
        if (!empty($class)) {
            $query->where('class_name', $class);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('symbol_no', $search)
                  ->orWhere('student_name', 'like', '%' . $search . '%');
            });
        }

        if (!empty($verification)) {
            $query->where(function ($q) use ($verification) {
                $q->where('student_name', 'like', '%' . $verification . '%')
                  ->orWhere('roll_no', $verification);
            });
        }

        $results = $query->get();

        if ($request->ajax() || $request->wantsJson()) {
            if ($results->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'status' => 'not_found',
                    'message' => 'No examination record matched your criteria. Please verify your symbol number, class, and examination.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'status' => 'success',
                'data' => $results->map(function ($r) {
                    return [
                        'id' => $r->id,
                        'batch_year' => $r->batch_year,
                        'exam_name' => $r->exam_name,
                        'class_name' => $r->class_name,
                        'section_name' => $r->section_name,
                        'symbol_no' => $r->symbol_no,
                        'roll_no' => $r->roll_no,
                        'student_name' => $r->student_name,
                        'attendance' => $r->attendance,
                        'gpa' => number_format((float)$r->gpa, 2),
                        'remarks' => $r->remarks,
                        'marks' => $r->marks,
                        'created_at' => (string)$r->created_at,
                    ];
                })->first() // Return primary matched result object
            ]);
        }

        $batches = EntranceResult::select('batch_year')->distinct()->orderBy('batch_year', 'desc')->pluck('batch_year');
        $exams = EntranceResult::select('exam_name')->distinct()->orderBy('exam_name', 'desc')->pluck('exam_name');
        $classes = EntranceResult::select('class_name')->distinct()->orderBy('class_name', 'asc')->pluck('class_name');
        $settings = ['system_name' => Qs::getSystemName()];

        return view('landing.entrance_results', compact('batches', 'exams', 'classes', 'results', 'settings', 'search'));
    }
}
