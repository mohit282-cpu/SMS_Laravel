<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Event;
use App\Models\Exam;
use App\Models\MyClass;
use App\Models\Notice;
use App\Models\Section;
use App\Services\BikramSambatService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{
    protected BikramSambatService $bsService;

    public function __construct(BikramSambatService $bsService)
    {
        $this->middleware('auth');
        $this->middleware('teamSA')->except(['index', 'show', 'calendarEvents']);
        $this->bsService = $bsService;
    }

    /**
     * Display a listing of events and the calendar view.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdmin = in_array($user->user_type, ['super_admin', 'admin'], true);

        $query = Event::visibleTo($user)->with(['my_class', 'section', 'academic_year', 'creator']);

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter by academic session
        if ($request->filled('session')) {
            $query->where('session', $request->session);
        }

        // Filter by publication status (Admins only)
        if ($isAdmin && $request->filled('is_published')) {
            $query->where('is_published', (bool) $request->is_published);
        }

        // Filter by BS date range with interval overlap check: start_date <= toDate AND end_date >= fromDate
        $fromDateAd = null;
        $toDateAd = null;

        if ($request->filled('date_from_bs')) {
            if (!$this->bsService->isValidBsDate($request->date_from_bs)) {
                return back()->withInput()->with('flash_danger', __('Invalid from Bikram Sambat (BS) date filter.'));
            }
            try {
                $convFrom = $this->bsService->bsToAd($request->date_from_bs);
                $fromDateAd = $convFrom['ad_date'];
            } catch (\Exception $e) {
                return back()->withInput()->with('flash_danger', __('Invalid from Bikram Sambat (BS) date filter: ') . $e->getMessage());
            }
        }

        if ($request->filled('date_to_bs')) {
            if (!$this->bsService->isValidBsDate($request->date_to_bs)) {
                return back()->withInput()->with('flash_danger', __('Invalid to Bikram Sambat (BS) date filter.'));
            }
            try {
                $convTo = $this->bsService->bsToAd($request->date_to_bs);
                $toDateAd = $convTo['ad_date'];
            } catch (\Exception $e) {
                return back()->withInput()->with('flash_danger', __('Invalid to Bikram Sambat (BS) date filter: ') . $e->getMessage());
            }
        }

        if ($fromDateAd && $toDateAd) {
            if ($toDateAd < $fromDateAd) {
                return back()->withInput()->with('flash_danger', __('Filter end date cannot be earlier than start date.'));
            }
            $query->where(function ($q) use ($fromDateAd, $toDateAd) {
                $q->where('start_date', '<=', $toDateAd)
                  ->where('end_date', '>=', $fromDateAd);
            });
        } elseif ($fromDateAd) {
            $query->where('end_date', '>=', $fromDateAd);
        } elseif ($toDateAd) {
            $query->where('start_date', '<=', $toDateAd);
        }

        $events = $query->orderBy('start_date', 'desc')->paginate(20)->appends($request->query());

        $data = [
            'events' => $events,
            'categories' => Event::CATEGORIES,
            'academic_years' => AcademicYear::orderBy('year_bs', 'desc')->get(),
            'active_year' => AcademicYear::current(),
            'my_classes' => MyClass::orderBy('name')->get(),
            'sections' => Section::orderBy('name')->get(),
            'today_bs' => $this->bsService->today(),
            'isAdmin' => $isAdmin,
        ];

        return view('pages.support_team.events.index', $data);
    }

    /**
     * Show the form for creating a new event.
     */
    public function create()
    {
        $data = [
            'categories' => Event::CATEGORIES,
            'academic_years' => AcademicYear::orderBy('year_bs', 'desc')->get(),
            'active_year' => AcademicYear::current(),
            'my_classes' => MyClass::orderBy('name')->get(),
            'sections' => Section::orderBy('name')->get(),
            'today_bs' => $this->bsService->today(),
        ];

        return view('pages.support_team.events.create', $data);
    }

    /**
     * Store a newly created event in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:191',
            'title_ne' => 'nullable|string|max:191',
            'category' => 'required|in:' . implode(',', array_keys(Event::CATEGORIES)),
            'start_date_bs' => 'required|regex:/^\d{4}-\d{2}-\d{2}$/',
            'end_date_bs' => 'nullable|regex:/^\d{4}-\d{2}-\d{2}$/',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'target_audience' => 'required|in:all,teachers,students,parents,classes,class_section',
            'my_class_id' => 'nullable|required_if:target_audience,classes,class_section|exists:my_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'location' => 'nullable|string|max:191',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
        ]);

        $startDateBs = $request->start_date_bs;
        $endDateBs = $request->end_date_bs ?: $startDateBs;

        // Validate BS dates using BikramSambatService
        if (!$this->bsService->isValidBsDate($startDateBs)) {
            return back()->withInput()->with('flash_danger', __('Invalid start Bikram Sambat (BS) date.'));
        }
        if (!$this->bsService->isValidBsDate($endDateBs)) {
            return back()->withInput()->with('flash_danger', __('Invalid end Bikram Sambat (BS) date.'));
        }

        try {
            $convStart = $this->bsService->bsToAd($startDateBs);
            $convEnd = $this->bsService->bsToAd($endDateBs);
        } catch (\Exception $e) {
            return back()->withInput()->with('flash_danger', __('Date conversion error: ') . $e->getMessage());
        }

        if ($convEnd['ad_date'] < $convStart['ad_date']) {
            return back()->withInput()->with('flash_danger', __('End date cannot be earlier than start date.'));
        }

        // Validate timed events
        $isAllDay = $request->has('is_all_day') || (!$request->filled('start_time') && !$request->filled('end_time'));
        if (!$isAllDay && $request->filled('start_time') && $request->filled('end_time')) {
            if ($convStart['ad_date'] === $convEnd['ad_date'] && $request->end_time < $request->start_time) {
                return back()->withInput()->with('flash_danger', __('End time cannot be earlier than start time on the same date.'));
            }
        }

        // Validate target audience and class/section relationship
        $targetAudience = $request->target_audience;
        $classId = null;
        $sectionId = null;

        if (in_array($targetAudience, ['classes', 'class_section'], true)) {
            if (!$request->filled('my_class_id')) {
                return back()->withInput()->with('flash_danger', __('Please select a class for class-targeted events.'));
            }
            $classId = $request->my_class_id;
            if ($request->filled('section_id')) {
                $section = Section::where('id', $request->section_id)->where('my_class_id', $classId)->first();
                if (!$section) {
                    return back()->withInput()->with('flash_danger', __('The selected section does not belong to the selected class.'));
                }
                $sectionId = $section->id;
            }
        }

        // Academic session assignment
        $academicYear = null;
        if ($request->filled('academic_year_id')) {
            $academicYear = AcademicYear::find($request->academic_year_id);
        }
        if (!$academicYear) {
            $academicYear = AcademicYear::current();
        }
        $session = $academicYear ? $academicYear->code : Qs::getCurrentSession();

        $isClosure = $request->has('is_closure') || in_array($request->category, ['public_holiday', 'school_holiday'], true);
        $isPublished = $request->has('is_published');

        $event = Event::create([
            'title' => $request->title,
            'title_ne' => $request->title_ne ?: $request->title,
            'description' => $request->description,
            'category' => $request->category,
            'start_date' => $convStart['ad_date'],
            'end_date' => $convEnd['ad_date'],
            'start_date_bs' => $startDateBs,
            'end_date_bs' => $endDateBs,
            'start_time' => $isAllDay ? null : $request->start_time,
            'end_time' => $isAllDay ? null : $request->end_time,
            'is_all_day' => $isAllDay,
            'is_published' => $isPublished,
            'is_closure' => $isClosure,
            'academic_year_id' => $academicYear ? $academicYear->id : null,
            'session' => $session,
            'target_audience' => $targetAudience,
            'my_class_id' => $classId,
            'section_id' => $sectionId,
            'location' => $request->location,
            'instructions' => $request->instructions,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        // Push notification if published
        if ($isPublished) {
            $this->dispatchNotification($event);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Event created successfully.'),
                'event' => $event,
            ]);
        }

        return redirect()->route('events.index')->with('flash_success', __('Event created successfully.'));
    }

    /**
     * Display the specified event.
     */
    public function show($id)
    {
        $user = Auth::user();
        $event = Event::visibleTo($user)->with(['my_class', 'section', 'academic_year', 'creator'])->findOrFail($id);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'event' => $event,
                'category_name' => $event->category_name,
                'category_color' => $event->category_color,
                'category_badge' => $event->category_badge,
            ]);
        }

        return view('pages.support_team.events.show', compact('event'));
    }

    /**
     * Show the form for editing the specified event.
     */
    public function edit($id)
    {
        $event = Event::findOrFail($id);

        $data = [
            'event' => $event,
            'categories' => Event::CATEGORIES,
            'academic_years' => AcademicYear::orderBy('year_bs', 'desc')->get(),
            'active_year' => AcademicYear::current(),
            'my_classes' => MyClass::orderBy('name')->get(),
            'sections' => Section::orderBy('name')->get(),
            'today_bs' => $this->bsService->today(),
        ];

        return view('pages.support_team.events.edit', $data);
    }

    /**
     * Update the specified event in storage.
     */
    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:191',
            'title_ne' => 'nullable|string|max:191',
            'category' => 'required|in:' . implode(',', array_keys(Event::CATEGORIES)),
            'start_date_bs' => 'required|regex:/^\d{4}-\d{2}-\d{2}$/',
            'end_date_bs' => 'nullable|regex:/^\d{4}-\d{2}-\d{2}$/',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'target_audience' => 'required|in:all,teachers,students,parents,classes,class_section',
            'my_class_id' => 'nullable|required_if:target_audience,classes,class_section|exists:my_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'location' => 'nullable|string|max:191',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
        ]);

        $startDateBs = $request->start_date_bs;
        $endDateBs = $request->end_date_bs ?: $startDateBs;

        if (!$this->bsService->isValidBsDate($startDateBs)) {
            return back()->withInput()->with('flash_danger', __('Invalid start Bikram Sambat (BS) date.'));
        }
        if (!$this->bsService->isValidBsDate($endDateBs)) {
            return back()->withInput()->with('flash_danger', __('Invalid end Bikram Sambat (BS) date.'));
        }

        try {
            $convStart = $this->bsService->bsToAd($startDateBs);
            $convEnd = $this->bsService->bsToAd($endDateBs);
        } catch (\Exception $e) {
            return back()->withInput()->with('flash_danger', __('Date conversion error: ') . $e->getMessage());
        }

        if ($convEnd['ad_date'] < $convStart['ad_date']) {
            return back()->withInput()->with('flash_danger', __('End date cannot be earlier than start date.'));
        }

        $isAllDay = $request->has('is_all_day') || (!$request->filled('start_time') && !$request->filled('end_time'));
        if (!$isAllDay && $request->filled('start_time') && $request->filled('end_time')) {
            if ($convStart['ad_date'] === $convEnd['ad_date'] && $request->end_time < $request->start_time) {
                return back()->withInput()->with('flash_danger', __('End time cannot be earlier than start time on the same date.'));
            }
        }

        // Validate target audience and class/section relationship
        $targetAudience = $request->target_audience;
        $classId = null;
        $sectionId = null;

        if (in_array($targetAudience, ['classes', 'class_section'], true)) {
            if (!$request->filled('my_class_id')) {
                return back()->withInput()->with('flash_danger', __('Please select a class for class-targeted events.'));
            }
            $classId = $request->my_class_id;
            if ($request->filled('section_id')) {
                $section = Section::where('id', $request->section_id)->where('my_class_id', $classId)->first();
                if (!$section) {
                    return back()->withInput()->with('flash_danger', __('The selected section does not belong to the selected class.'));
                }
                $sectionId = $section->id;
            }
        }

        $academicYear = null;
        if ($request->filled('academic_year_id')) {
            $academicYear = AcademicYear::find($request->academic_year_id);
        }
        if (!$academicYear) {
            $academicYear = AcademicYear::current();
        }
        $session = $academicYear ? $academicYear->code : ($event->session ?: Qs::getCurrentSession());

        $isClosure = $request->has('is_closure') || in_array($request->category, ['public_holiday', 'school_holiday'], true);
        $wasPublished = $event->is_published;
        $isPublished = $request->has('is_published');

        $event->update([
            'title' => $request->title,
            'title_ne' => $request->title_ne ?: $request->title,
            'description' => $request->description,
            'category' => $request->category,
            'start_date' => $convStart['ad_date'],
            'end_date' => $convEnd['ad_date'],
            'start_date_bs' => $startDateBs,
            'end_date_bs' => $endDateBs,
            'start_time' => $isAllDay ? null : $request->start_time,
            'end_time' => $isAllDay ? null : $request->end_time,
            'is_all_day' => $isAllDay,
            'is_published' => $isPublished,
            'is_closure' => $isClosure,
            'academic_year_id' => $academicYear ? $academicYear->id : null,
            'session' => $session,
            'target_audience' => $targetAudience,
            'my_class_id' => $classId,
            'section_id' => $sectionId,
            'location' => $request->location,
            'instructions' => $request->instructions,
            'updated_by' => Auth::id(),
        ]);

        if (!$wasPublished && $isPublished) {
            $this->dispatchNotification($event);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Event updated successfully.'),
                'event' => $event,
            ]);
        }

        return redirect()->route('events.index')->with('flash_success', __('Event updated successfully.'));
    }

    /**
     * Remove the specified event from storage.
     */
    public function destroy($id)
    {
        $event = Event::findOrFail($id);

        // Safe deletion guard: if event is tied to an Exam record
        if ($event->exam_id) {
            return back()->with('flash_danger', __('This event is linked to an Examination schedule. Please manage it via the Exams module.'));
        }

        $event->delete();

        return redirect()->route('events.index')->with('flash_success', __('Event deleted successfully.'));
    }

    /**
     * Publish an event.
     */
    public function publish($id)
    {
        $event = Event::findOrFail($id);
        $event->update(['is_published' => true, 'updated_by' => Auth::id()]);
        $this->dispatchNotification($event);

        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => __('Event published.')]);
        }

        return back()->with('flash_success', __('Event published successfully.'));
    }

    /**
     * Unpublish an event.
     */
    public function unpublish($id)
    {
        $event = Event::findOrFail($id);
        $event->update(['is_published' => false, 'updated_by' => Auth::id()]);

        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => __('Event unpublished.')]);
        }

        return back()->with('flash_success', __('Event unpublished successfully.'));
    }

    /**
     * JSON feed for calendar components (Native Nepali BS Calendar & FullCalendar fallback).
     */
    public function calendarEvents(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user && in_array($user->user_type, ['super_admin', 'admin'], true);

        $eventsQuery = Event::visibleTo($user)->with(['my_class', 'section']);

        // Specific category filter
        if ($request->filled('category')) {
            $eventsQuery->where('category', $request->category);
        }

        // Specific session filter
        if ($request->filled('session')) {
            $eventsQuery->where('session', $request->session);
        }

        // Specific BS Year and Month filtering
        if ($request->filled('year_bs') && $request->filled('month_bs')) {
            $y = (int) $request->year_bs;
            $m = (int) $request->month_bs;

            if ($y < 2000 || $y > 2090 || $m < 1 || $m > 12) {
                return response()->json(['error' => 'Invalid year_bs or month_bs parameter.'], 422);
            }

            // Compute month start and end AD boundaries
            try {
                $daysInMonth = $this->bsService->getDaysInMonth($y, $m);
                $adStart = $this->bsService->bsToAd(sprintf('%04d-%02d-01', $y, $m))['ad_date'];
                $adEnd = $this->bsService->bsToAd(sprintf('%04d-%02d-%02d', $y, $m, $daysInMonth))['ad_date'];

                $eventsQuery->where(function ($q) use ($adStart, $adEnd) {
                    $q->where('start_date', '<=', $adEnd)
                      ->where('end_date', '>=', $adStart);
                });
            } catch (\Exception $e) {
                Log::warning('BS calendar events month boundary conversion failed: ' . $e->getMessage());
                return response()->json(['error' => 'Calendar date conversion failed.'], 422);
            }
        } elseif ($request->filled('start') && $request->filled('end')) {
            // Gregorian range fallback (FullCalendar)
            $adStart = substr($request->start, 0, 10);
            $adEnd = substr($request->end, 0, 10);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $adStart) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $adEnd)) {
                return response()->json(['error' => 'Invalid start or end date parameter.'], 422);
            }
            if ($adEnd < $adStart) {
                return response()->json(['error' => 'End date cannot be earlier than start date.'], 422);
            }
            $eventsQuery->where(function ($q) use ($adStart, $adEnd) {
                $q->where('start_date', '<=', $adEnd)
                  ->where('end_date', '>=', $adStart);
            });
        }

        $eventsList = $eventsQuery->orderBy('start_date', 'asc')->get();

        $items = [];
        $isNe = app()->getLocale() === 'ne';

        foreach ($eventsList as $event) {
            $title = ($isNe && $event->title_ne) ? $event->title_ne : $event->title;
            $cat = Event::CATEGORIES[$event->category] ?? null;
            $catName = $cat ? ($isNe ? $cat['name_ne'] : $cat['name_en']) : ucfirst(str_replace('_', ' ', $event->category));
            $color = $cat['color'] ?? '#1e88e5';

            $items[] = [
                'id' => 'event_' . $event->id,
                'event_id' => $event->id,
                'title' => $title,
                'title_ne' => $event->title_ne,
                'category' => $event->category,
                'category_name' => $catName,
                'color' => $color,
                'start' => $event->start_date ? $event->start_date->format('Y-m-d') : null,
                'end' => $event->end_date ? $event->end_date->copy()->addDay()->format('Y-m-d') : null, // exclusive for fullcalendar
                'raw_end' => $event->end_date ? $event->end_date->format('Y-m-d') : null,
                'start_date_bs' => $event->start_date_bs,
                'end_date_bs' => $event->end_date_bs ?: $event->start_date_bs,
                'start_time' => $event->start_time,
                'end_time' => $event->end_time,
                'is_all_day' => (bool) $event->is_all_day,
                'is_published' => (bool) $event->is_published,
                'is_closure' => (bool) $event->is_closure,
                'target_audience' => $event->target_audience,
                'class_name' => $event->my_class ? $event->my_class->name : null,
                'section_name' => $event->section ? $event->section->name : null,
                'location' => $event->location,
                'description' => $event->description,
                'instructions' => $event->instructions,
                'type' => 'event',
                'can_edit' => $isAdmin,
                'can_delete' => $isAdmin && !$event->exam_id,
            ];
        }

        // Include published notices
        if ($user) {
            $notices = Notice::visibleTo($user)
                ->where('is_published', true)
                ->get();

            foreach ($notices as $n) {
                $noticeStartAd = $n->notice_date ? $n->notice_date->format('Y-m-d') : null;
                $noticeEndAd = $n->expiry_date ? $n->expiry_date->format('Y-m-d') : $noticeStartAd;

                $items[] = [
                    'id' => 'notice_' . $n->id,
                    'notice_id' => $n->id,
                    'title' => '[Notice] ' . $n->title,
                    'category' => 'other',
                    'category_name' => __('Notice'),
                    'color' => '#6366f1',
                    'start' => $noticeStartAd,
                    'end' => $n->expiry_date ? $n->expiry_date->copy()->addDay()->format('Y-m-d') : ($noticeStartAd ? Carbon::parse($noticeStartAd)->addDay()->format('Y-m-d') : null),
                    'raw_end' => $noticeEndAd,
                    'start_date_bs' => $n->notice_date_bs,
                    'end_date_bs' => $n->notice_date_bs,
                    'is_all_day' => true,
                    'is_published' => true,
                    'is_closure' => false,
                    'description' => $n->content,
                    'type' => 'notice',
                    'can_edit' => false,
                    'can_delete' => false,
                ];
            }
        }

        // Include individual subject examination timetables
        if ($user) {
            $ttQuery = \App\Models\TimeTable::whereNotNull('exam_date')
                ->whereHas('tt_record', function ($q) {
                    $q->whereNotNull('exam_id');
                })
                ->with(['tt_record.exam', 'tt_record.my_class', 'subject', 'time_slot']);

            if (!$isAdmin) {
                // Non-admins must strictly only see published examinations (never draft or unpublished)
                $ttQuery->whereHas('tt_record.exam', function ($q) {
                    $q->where('status', 'published');
                });

                if ($user->user_type === 'student') {
                    $sr = \App\Models\StudentRecord::where('user_id', $user->id)->first();
                    if ($sr && $sr->my_class_id) {
                        $ttQuery->whereHas('tt_record', function ($q) use ($sr) {
                            $q->where('my_class_id', $sr->my_class_id);
                        });
                    } else {
                        // Student without enrolled class cannot see any timetables
                        $ttQuery->whereRaw('0 = 1');
                    }
                } elseif ($user->user_type === 'parent') {
                    $childClassIds = \App\Models\StudentRecord::where('my_parent_id', $user->id)
                        ->whereNotNull('my_class_id')
                        ->pluck('my_class_id')
                        ->unique()
                        ->toArray();
                    if (!empty($childClassIds)) {
                        $ttQuery->whereHas('tt_record', function ($q) use ($childClassIds) {
                            $q->whereIn('my_class_id', $childClassIds);
                        });
                    } else {
                        // Parent without children with classes cannot see any timetables
                        $ttQuery->whereRaw('0 = 1');
                    }
                } elseif ($user->user_type === 'teacher') {
                    $teacherSubjectIds = \App\Models\Subject::where('teacher_id', $user->id)->pluck('id')->toArray();
                    $teacherClassIds = \App\Models\Section::where('teacher_id', $user->id)->pluck('my_class_id')->unique()->toArray();
                    if (!empty($teacherSubjectIds) || !empty($teacherClassIds)) {
                        $ttQuery->where(function ($q) use ($teacherSubjectIds, $teacherClassIds) {
                            if (!empty($teacherSubjectIds)) {
                                $q->whereIn('subject_id', $teacherSubjectIds);
                            }
                            if (!empty($teacherClassIds)) {
                                $q->orWhereHas('tt_record', function ($sub) use ($teacherClassIds) {
                                    $sub->whereIn('my_class_id', $teacherClassIds);
                                });
                            }
                        });
                    } else {
                        // Teacher without assigned classes or subjects
                        $ttQuery->whereRaw('0 = 1');
                    }
                } else {
                    // Non-teaching staff (accountant, etc.) cannot view student examination timetables
                    $ttQuery->whereRaw('0 = 1');
                }
            }

            if (!empty($adStart) && !empty($adEnd)) {
                $ttQuery->whereBetween('exam_date', [$adStart, $adEnd]);
            }

            $subjectExams = $ttQuery->get();
            foreach ($subjectExams as $se) {
                try {
                    $examDateAd = substr($se->exam_date, 0, 10);
                    $bsConv = $this->bsService->adToBs($examDateAd);
                    $subName = $se->subject ? $se->subject->name : 'Exam';
                    $className = $se->tt_record && $se->tt_record->my_class ? $se->tt_record->my_class->name : '';
                    $examName = $se->tt_record && $se->tt_record->exam ? $se->tt_record->exam->name : 'Exam';
                    $timeStr = ($se->time_slot) ? ($se->time_slot->time_from . ' - ' . $se->time_slot->time_to) : '';

                    $items[] = [
                        'id' => 'tt_' . $se->id,
                        'event_id' => null,
                        'title' => "[Exam] {$subName}" . ($className ? " ({$className})" : ''),
                        'category' => 'examination',
                        'category_name' => __('Examination'),
                        'color' => '#FF5700',
                        'start' => $examDateAd,
                        'end' => Carbon::parse($examDateAd)->addDay()->format('Y-m-d'),
                        'raw_end' => $examDateAd,
                        'start_date_bs' => $bsConv['bs_date'],
                        'end_date_bs' => $bsConv['bs_date'],
                        'start_time' => $se->time_slot ? $se->time_slot->time_from : null,
                        'end_time' => $se->time_slot ? $se->time_slot->time_to : null,
                        'is_all_day' => empty($timeStr),
                        'is_published' => true,
                        'is_closure' => false,
                        'exam_name' => $examName,
                        'subject_name' => $subName,
                        'class_name' => $className,
                        'section_name' => null,
                        'description' => "Subject: {$subName}\nExamination: {$examName}\nClass: {$className}\nTime: {$timeStr}",
                        'type' => 'timetable_exam',
                        'can_edit' => false,
                        'can_delete' => false,
                    ];
                } catch (\Throwable $e) {
                    // Ignore date parse failure on unusual record
                }
            }
        }

        return response()->json($items);
    }

    /**
     * Dispatch web push notifications for an event to eligible subscribed recipients.
     */
    protected function dispatchNotification(Event $event): void
    {
        if (!$event->is_published) {
            return;
        }

        try {
            $notificationService = app(\App\Services\EventNotificationService::class);
            $subscriberIds = $notificationService->getSubscribedRecipientIds($event);

            if (!empty($subscriberIds)) {
                $isNe = app()->getLocale() === 'ne';
                $eventTitle = ($isNe && $event->title_ne) ? $event->title_ne : $event->title;
                $targetUrl = route('events.index');

                \Illuminate\Support\Facades\DB::afterCommit(function () use ($subscriberIds, $eventTitle, $event, $targetUrl) {
                    \App\Jobs\SendWebPushNotification::dispatch(
                        $subscriberIds,
                        __('School Calendar Event'),
                        $eventTitle . " ({$event->start_date_bs} BS)",
                        $targetUrl
                    );
                });
            }
        } catch (\Throwable $e) {
            Log::warning('WebPush notification dispatch failed for event: ' . $e->getMessage());
        }
    }
}
