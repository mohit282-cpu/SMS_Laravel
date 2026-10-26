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
        return view('pages.support_team.academic_years.index', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'year_bs' => 'required|integer|min:2000|max:2090',
            'start_date_bs' => 'required|string',
            'end_date_bs' => 'required|string',
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

        $is_active = $request->has('is_active') ? true : false;

        if ($is_active) {
            // Only one active year allowed at a time
            AcademicYear::where('is_active', true)->update(['is_active' => false]);
            \App\Models\Setting::where('type', 'current_session')->update(['description' => $request->name]);
        }

        AcademicYear::create([
            'name' => $request->name,
            'year_bs' => $request->year_bs,
            'start_date' => $convStart['ad_date'],
            'end_date' => $convEnd['ad_date'],
            'start_date_bs' => $request->start_date_bs,
            'end_date_bs' => $request->end_date_bs,
            'is_active' => $is_active,
            'is_closed' => false,
            'description' => $request->description,
        ]);

        return back()->with('flash_success', __('Academic year created successfully.'));
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
            'start_date_bs' => 'required|string',
            'end_date_bs' => 'required|string',
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
        $is_active = $request->has('is_active') ? true : false;

        if ($is_active) {
            AcademicYear::where('id', '!=', $id)->where('is_active', true)->update(['is_active' => false]);
            \App\Models\Setting::where('type', 'current_session')->update(['description' => $request->name]);
        }

        $ay->update([
            'name' => $request->name,
            'year_bs' => $request->year_bs,
            'start_date' => $convStart['ad_date'],
            'end_date' => $convEnd['ad_date'],
            'start_date_bs' => $request->start_date_bs,
            'end_date_bs' => $request->end_date_bs,
            'is_active' => $is_active,
            'description' => $request->description,
        ]);

        return redirect()->route('academic_years.index')->with('flash_success', __('msg.update_ok'));
    }

    public function activate($id)
    {
        AcademicYear::where('is_active', true)->update(['is_active' => false]);
        $ay = AcademicYear::findOrFail($id);
        $ay->update(['is_active' => true, 'is_closed' => false]);

        // Keep system current_session setting in sync
        \App\Models\Setting::where('type', 'current_session')->update(['description' => $ay->name]);

        return back()->with('flash_success', __('Academic year activated successfully.'));
    }

    public function close($id)
    {
        $ay = AcademicYear::findOrFail($id);
        $ay->update(['is_active' => false, 'is_closed' => true]);

        return back()->with('flash_success', __('Academic year closed and archived successfully.'));
    }

    public function reopen($id)
    {
        $ay = AcademicYear::findOrFail($id);
        $ay->update(['is_closed' => false]);

        return back()->with('flash_success', __('Academic year reopened successfully.'));
    }

    public function destroy($id)
    {
        $ay = AcademicYear::findOrFail($id);
        if ($ay->is_active) {
            return back()->with('flash_danger', __('Cannot delete an active academic year.'));
        }
        $ay->delete();

        return back()->with('flash_success', __('Academic year deleted successfully.'));
    }
}
