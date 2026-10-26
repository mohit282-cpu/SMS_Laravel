<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\NepaliHelper;
use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Services\BikramSambatService;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    protected $bsService;

    public function __construct(BikramSambatService $bsService)
    {
        $this->middleware('teamSA');
        $this->bsService = $bsService;
    }

    public function index()
    {
        $data['academic_years'] = AcademicYear::orderBy('year_bs', 'desc')->get();
        $data['current_session'] = AcademicYear::current();
        return view('pages.support_team.academic_years.index', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'year_bs' => 'required|integer|min:2000|max:2090',
            'start_date_bs' => 'required|string|regex:/^\d{4}-\d{2}-\d{2}$/',
            'end_date_bs' => 'required|string|regex:/^\d{4}-\d{2}-\d{2}$/',
        ]);

        try {
            $convStart = $this->bsService->bsToAd($request->start_date_bs);
            $convEnd = $this->bsService->bsToAd($request->end_date_bs);
        } catch (\Exception $e) {
            return back()->with('flash_danger', __('Invalid BS date: ') . $e->getMessage());
        }

        if ($convEnd['ad_date'] <= $convStart['ad_date']) {
            return back()->with('flash_danger', __('End date must be greater than start date.'));
        }

        $shouldActivate = $request->has('is_active') || (AcademicYear::where('is_active', true)->count() === 0);

        $ay = AcademicYear::create([
            'name' => trim($request->name),
            'year_bs' => $request->year_bs,
            'start_date' => $convStart['ad_date'],
            'end_date' => $convEnd['ad_date'],
            'start_date_bs' => $request->start_date_bs,
            'end_date_bs' => $request->end_date_bs,
            'is_active' => false, // Will be set by activateSession if applicable
            'is_closed' => false,
            'description' => $request->description,
        ]);

        if ($shouldActivate) {
            AcademicYear::activateSession($ay->id);
        }

        return back()->with('flash_success', __('Academic session created successfully.'));
    }

    public function edit($id)
    {
        $data['ay'] = AcademicYear::findOrFail($id);
        return view('pages.support_team.academic_years.edit', $data);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'year_bs' => 'required|integer|min:2000|max:2090',
            'start_date_bs' => 'required|string|regex:/^\d{4}-\d{2}-\d{2}$/',
            'end_date_bs' => 'required|string|regex:/^\d{4}-\d{2}-\d{2}$/',
        ]);

        try {
            $convStart = $this->bsService->bsToAd($request->start_date_bs);
            $convEnd = $this->bsService->bsToAd($request->end_date_bs);
        } catch (\Exception $e) {
            return back()->with('flash_danger', __('Invalid BS date: ') . $e->getMessage());
        }

        if ($convEnd['ad_date'] <= $convStart['ad_date']) {
            return back()->with('flash_danger', __('End date must be greater than start date.'));
        }

        $ay = AcademicYear::findOrFail($id);
        $wasActive = $ay->is_active;
        $shouldActivate = $request->has('is_active');

        $ay->update([
            'name' => trim($request->name),
            'year_bs' => $request->year_bs,
            'start_date' => $convStart['ad_date'],
            'end_date' => $convEnd['ad_date'],
            'start_date_bs' => $request->start_date_bs,
            'end_date_bs' => $request->end_date_bs,
            'description' => $request->description,
        ]);

        if ($shouldActivate || ($wasActive && !$ay->is_closed)) {
            AcademicYear::activateSession($ay->id);
        }

        return redirect()->route('academic_years.index')->with('flash_success', __('msg.update_ok'));
    }

    public function activate($id)
    {
        AcademicYear::activateSession($id);

        return back()->with('flash_success', __('Academic session activated successfully.'));
    }

    public function close($id)
    {
        $ay = AcademicYear::findOrFail($id);
        $wasActive = $ay->is_active;

        $ay->update(['is_active' => false, 'is_closed' => true]);

        if ($wasActive) {
            // If active session closed, activate the next open session or fallback
            $nextOpen = AcademicYear::where('is_closed', false)->orderBy('year_bs', 'desc')->first();
            if ($nextOpen) {
                AcademicYear::activateSession($nextOpen->id);
                return back()->with('flash_success', __('Session closed. Active session automatically switched to :name.', ['name' => $nextOpen->name]));
            }
        }

        return back()->with('flash_success', __('Academic session closed and archived successfully.'));
    }

    public function reopen($id)
    {
        $ay = AcademicYear::findOrFail($id);
        $ay->update(['is_closed' => false]);

        return back()->with('flash_success', __('Academic session reopened successfully.'));
    }

    public function destroy($id)
    {
        $ay = AcademicYear::findOrFail($id);

        if ($ay->is_active) {
            return back()->with('flash_danger', __('Cannot delete an active academic session.'));
        }

        // Integrity check: prevent deletion if historical records reference this session
        $hasStudents = \App\Models\StudentRecord::where('session', $ay->name)->exists();
        $hasExams = \App\Models\Exam::where('year', $ay->name)->exists();
        $hasPayments = \App\Models\Payment::where('year', $ay->name)->exists();

        if ($hasStudents || $hasExams || $hasPayments) {
            return back()->with('flash_danger', __('Cannot delete session: Historical student, payment, or examination records are associated with this academic session.'));
        }

        $ay->delete();

        return back()->with('flash_success', __('Academic session deleted successfully.'));
    }
}
