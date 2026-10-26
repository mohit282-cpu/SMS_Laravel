<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Services\BikramSambatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HolidayController extends Controller
{
    protected $bsService;

    public function __construct(BikramSambatService $bsService)
    {
        $this->middleware('teamSA')->except(['events']);
        $this->middleware('super_admin', ['only' => ['destroy']]);
        $this->bsService = $bsService;
    }

    public function index()
    {
        $data['holidays'] = Holiday::orderBy('start_date', 'asc')->get();
        return view('pages.support_team.holidays.index', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:191',
            'type' => 'required|in:public_holiday,school_holiday,exam_period,academic_event,custom_working_day',
            'start_date_bs' => 'required|string',
            'end_date_bs' => 'required|string',
        ]);

        try {
            $convStart = $this->bsService->bsToAd($request->start_date_bs);
            $convEnd = $this->bsService->bsToAd($request->end_date_bs);
        } catch (\Exception $e) {
            return back()->with('flash_danger', __('Invalid BS date: ') . $e->getMessage());
        }

        if ($convEnd['ad_date'] < $convStart['ad_date']) {
            return back()->with('flash_danger', __('End date cannot be earlier than start date.'));
        }

        Holiday::create([
            'title' => $request->title,
            'title_ne' => $request->title_ne ?: $request->title,
            'type' => $request->type,
            'start_date' => $convStart['ad_date'],
            'end_date' => $convEnd['ad_date'],
            'start_date_bs' => $request->start_date_bs,
            'end_date_bs' => $request->end_date_bs,
            'is_closure' => $request->has('is_closure') ? true : false,
            'description' => $request->description,
            'created_by' => Auth::id(),
        ]);

        try {
            $subscriberIds = \App\Models\PushSubscription::pluck('user_id')->unique()->toArray();
            if (!empty($subscriberIds)) {
                $eventTitle = (app()->getLocale() === 'ne' && $request->title_ne) ? $request->title_ne : $request->title;
                \App\Jobs\SendWebPushNotification::dispatch(
                    $subscriberIds,
                    __('School Announcement'),
                    $eventTitle . " ({$request->start_date_bs} BS)",
                    route('dashboard')
                );
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('WebPush notification dispatch failed for holiday: ' . $e->getMessage());
        }

        return back()->with('flash_success', __('Holiday/Event saved successfully.'));
    }

    public function destroy($id)
    {
        Holiday::findOrFail($id)->delete();
        return back()->with('flash_success', __('Holiday/Event deleted successfully.'));
    }

    /**
     * JSON feed for FullCalendar on Dashboard or calendar view.
     */
    public function events()
    {
        $holidays = Holiday::all();
        $events = [];

        foreach ($holidays as $h) {
            $color = '#e53935'; // red for holiday/closure
            if ($h->type === 'exam_period') {
                $color = '#fb8c00'; // orange
            } elseif ($h->type === 'academic_event') {
                $color = '#1e88e5'; // blue
            } elseif ($h->type === 'custom_working_day') {
                $color = '#43a047'; // green
            }

            $title = (app()->getLocale() === 'ne' && $h->title_ne) ? $h->title_ne : $h->title;

            $events[] = [
                'id' => 'holiday_' . $h->id,
                'title' => $title . " ({$h->start_date_bs} BS)",
                'start' => $h->start_date->format('Y-m-d'),
                // Fullcalendar end date is exclusive, add 1 day
                'end' => $h->end_date->addDay()->format('Y-m-d'),
                'color' => $color,
                'description' => $h->description,
                'type' => 'holiday',
            ];
        }

        $user = auth()->user();
        if ($user) {
            $notices = \App\Models\Notice::visibleTo($user)
                ->where('is_published', true)
                ->get();

            foreach ($notices as $n) {
                $endDate = $n->expiry_date ? $n->expiry_date->copy()->addDay()->format('Y-m-d') : $n->notice_date->copy()->addDay()->format('Y-m-d');
                $events[] = [
                    'id' => 'notice_' . $n->id,
                    'title' => '[Notice] ' . $n->title . ($n->notice_date_bs ? " ({$n->notice_date_bs} BS)" : ''),
                    'start' => $n->notice_date->format('Y-m-d'),
                    'end' => $endDate,
                    'color' => '#673ab7',
                    'description' => $n->content,
                    'type' => 'notice',
                    'notice_id' => $n->id,
                ];
            }
        }

        return response()->json($events);
    }
}
