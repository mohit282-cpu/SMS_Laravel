<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\AdmissionInquiry;
use App\Models\EntranceResult;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LandingSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('super_admin');
    }

    /**
     * Super Admin Landing Page & Admissions Dashboard
     */
    public function index()
    {
        $settings = Setting::all()->pluck('description', 'type');

        $inquiryCount = AdmissionInquiry::where('status', 'pending')->count();
        $totalInquiries = AdmissionInquiry::count();
        $totalEntranceResults = EntranceResult::count();
        $batchesCount = EntranceResult::select('batch_year')->distinct()->count();

        return view('pages.support_team.landing_settings.index', compact(
            'settings',
            'inquiryCount',
            'totalInquiries',
            'totalEntranceResults',
            'batchesCount'
        ));
    }

    /**
     * Update Landing Page Settings
     */
    public function update(Request $request)
    {
        $data = $request->except(['_token', '_method']);

        foreach ($data as $key => $val) {
            Setting::updateOrCreate(
                ['type' => $key],
                ['description' => is_null($val) ? '' : $val]
            );
        }

        return back()->with('flash_success', 'Landing Page & Admissions settings updated successfully.');
    }

    /**
     * View and Manage Prospective Student Inquiries
     */
    public function inquiries(Request $request)
    {
        $status = $request->input('status');

        $query = AdmissionInquiry::query()->orderBy('id', 'desc');
        if (!empty($status)) {
            $query->where('status', $status);
        }

        $inquiries = $query->paginate(20);

        return view('pages.support_team.landing_settings.inquiries', compact('inquiries', 'status'));
    }

    /**
     * Update Status of an Admission Inquiry
     */
    public function update_inquiry_status(Request $request, $id)
    {
        $inquiry = AdmissionInquiry::findOrFail($id);
        $status = $request->input('status', 'contacted');
        $notes = $request->input('admin_notes');

        $inquiry->status = $status;
        if (!is_null($notes)) {
            $inquiry->admin_notes = $notes;
        }
        $inquiry->save();

        return back()->with('flash_success', 'Inquiry status updated to ' . strtoupper($status));
    }

    /**
     * Delete an Admission Inquiry
     */
    public function destroy_inquiry($id)
    {
        $inquiry = AdmissionInquiry::findOrFail($id);
        $inquiry->delete();

        return back()->with('flash_success', 'Inquiry record removed successfully.');
    }

    /**
     * Manage Entrance Examination Results (New Admissions)
     */
    public function manage_entrance_results(Request $request)
    {
        $batch = $request->input('batch_year');
        $class = $request->input('class_name');
        $search = $request->input('search');

        $query = EntranceResult::query()->orderBy('id', 'desc');

        if (!empty($batch)) {
            $query->where('batch_year', $batch);
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

        $results = $query->paginate(25);

        $batches = EntranceResult::select('batch_year')->distinct()->orderBy('batch_year', 'desc')->pluck('batch_year');
        $classes = EntranceResult::select('class_name')->distinct()->orderBy('class_name', 'asc')->pluck('class_name');

        return view('pages.support_team.landing_settings.entrance_results', compact(
            'results',
            'batches',
            'classes',
            'batch',
            'class',
            'search'
        ));
    }

    /**
     * Bulk Upload Entrance Examination Results via CSV
     */
    public function upload_entrance_results(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
            'batch_year' => 'required|string|max:100',
            'exam_name' => 'required|string|max:255',
            'class_name' => 'required|string|max:100',
        ]);

        $batch_year = $request->input('batch_year');
        $exam_name = $request->input('exam_name');
        $class_name = $request->input('class_name');

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if (!$header) {
            return back()->with('flash_danger', 'The uploaded CSV file is empty.');
        }

        // Expected Columns: Symbol No, Roll No, Student Name, Section, Attendance, Subject:Marks...
        $importedCount = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                if (empty($row[0])) continue;

                $symbol_no = trim($row[0] ?? '');
                $roll_no = trim($row[1] ?? '');
                $student_name = trim($row[2] ?? '');
                $section_name = trim($row[3] ?? 'GENERAL');
                $attendance = trim($row[4] ?? 'N/A');

                // Build subjects marks list from column index 5 onwards
                $marksList = [];
                $totalGp = 0;
                $subjectCount = 0;

                for ($i = 5; $i < count($header); $i++) {
                    $subjectName = trim($header[$i]);
                    $score = isset($row[$i]) ? floatval($row[$i]) : 0;

                    // Calculate letter grade & GP (Nepal CDC 4.0 scale)
                    $gpInfo = $this->calculateGradePoint($score);
                    $totalGp += $gpInfo['gp'];
                    $subjectCount++;

                    $marksList[] = [
                        'sn' => $subjectCount,
                        'subject' => strtoupper($subjectName),
                        'full_marks' => 100,
                        'grade' => $gpInfo['grade'],
                        'gp' => $gpInfo['gp'],
                    ];
                }

                $gpa = $subjectCount > 0 ? round($totalGp / $subjectCount, 2) : 0.00;
                $remarks = $gpa >= 1.6 ? 'ELIGIBLE FOR ADMISSION' : 'NOT QUALIFIED';

                EntranceResult::create([
                    'batch_year' => $batch_year,
                    'exam_name' => $exam_name,
                    'class_name' => $class_name,
                    'section_name' => $section_name,
                    'symbol_no' => $symbol_no,
                    'roll_no' => $roll_no,
                    'student_name' => $student_name,
                    'attendance' => $attendance,
                    'gpa' => $gpa,
                    'remarks' => $remarks,
                    'marks_json' => json_encode($marksList),
                ]);

                $importedCount++;
            }

            DB::commit();
            fclose($handle);
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            return back()->with('flash_danger', 'CSV Import Failed: ' . $e->getMessage());
        }

        return back()->with('flash_success', "Successfully imported {$importedCount} student entrance exam records.");
    }

    /**
     * Delete an individual Entrance Result record
     */
    public function destroy_entrance_result($id)
    {
        $result = EntranceResult::findOrFail($id);
        $result->delete();

        return back()->with('flash_success', 'Entrance result record deleted successfully.');
    }

    /**
     * Compute Nepal CDC Letter Grade and Grade Point
     */
    protected function calculateGradePoint($percentage)
    {
        if ($percentage >= 90) return ['grade' => 'A+', 'gp' => 4.0];
        if ($percentage >= 80) return ['grade' => 'A', 'gp' => 3.6];
        if ($percentage >= 70) return ['grade' => 'B+', 'gp' => 3.2];
        if ($percentage >= 60) return ['grade' => 'B', 'gp' => 2.8];
        if ($percentage >= 50) return ['grade' => 'C+', 'gp' => 2.4];
        if ($percentage >= 40) return ['grade' => 'C', 'gp' => 2.0];
        if ($percentage >= 35) return ['grade' => 'D', 'gp' => 1.6];
        return ['grade' => 'NG', 'gp' => 0.0];
    }
}
