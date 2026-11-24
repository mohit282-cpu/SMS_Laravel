@extends('layouts.master')
@section('page_title', __('Holiday Calendar'))
@section('content')

    {{-- Executive Header --}}
    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center">
                    <div class="d-inline-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px; border-radius: 14px; background: rgba(255, 87, 0, 0.12);">
                        <i class="icon-calendar52 icon-2x" style="color: #FF5700;"></i>
                    </div>
                    <div>
                        <h3 class="font-weight-bold mb-1 text-dark" style="font-size: 1.35rem; letter-spacing: -0.01em;">
                            {{ __('School Holiday & Calendar Management (BS & AD)') }}
                        </h3>
                        <p class="text-muted mb-0 font-size-sm">
                            {{ __('Institutional breaks, public holidays, examination closures & approved special working days') }}
                        </p>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 mt-2 mt-md-0">
                    <span class="badge badge-light border px-3 py-2 text-dark font-size-sm shadow-sm" style="border-radius: 8px;">
                        <i class="icon-calendar22 mr-1 text-primary"></i> {{ \App\Helpers\NepaliHelper::formatDualDate(date('Y-m-d')) }}
                    </span>
                    <span class="badge badge-primary px-3 py-2 font-size-sm shadow-sm" style="border-radius: 8px;">
                        <i class="icon-graduation mr-1"></i> {{ __('Session') }}: {{ Qs::getSetting('current_session') }}
                    </span>
                    <button type="button" class="btn text-white btn-sm font-weight-semibold shadow-sm ml-md-2" onclick="$('#tab-new-holiday-link').tab('show');" style="background: #FF5700; border-radius: 8px;">
                        <i class="icon-plus2 mr-1"></i>{{ __('Add Holiday / Event') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- 4 Metric KPI Stat Cards --}}
    <div class="row mb-4">
        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #4f46e5 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Total Holidays') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($total_holidays ?? $holidays->count()) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Configured calendar entries') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(79, 70, 229, 0.1);">
                            <i class="icon-calendar52 icon-2x" style="color: #4f46e5;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #ef4444 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('School Closures') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($closure_count ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Official closed days') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(239, 68, 68, 0.1);">
                            <i class="icon-bed2 icon-2x" style="color: #ef4444;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #f59e0b !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Public Holidays') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($public_holiday_count ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('National & festive holidays') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1);">
                            <i class="icon-flag3 icon-2x" style="color: #f59e0b;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #10b981 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Upcoming Holidays') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($upcoming_count ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Future scheduled breaks') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16, 185, 129, 0.1);">
                            <i class="icon-alarm-check icon-2x" style="color: #10b981;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Module Card --}}
    <div class="card shadow-sm border-0" style="border-radius: 14px;">
        <div class="card-header d-flex align-items-center justify-content-between bg-white border-bottom py-3">
            <h5 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.05rem;">
                <i class="icon-calendar52 mr-2" style="color: #FF5700;"></i>{{ __('Holiday Operations & Calendars') }}
            </h5>
            <div class="d-flex align-items-center">
                {!! Qs::getPanelOptions() !!}
            </div>
        </div>

        <div class="card-body p-3 p-md-4">
            <ul class="nav nav-tabs nav-tabs-highlight mb-4">
                <li class="nav-item">
                    <a href="#bs-calendar-view" class="nav-link active font-weight-semibold" data-toggle="tab">
                        <i class="icon-calendar mr-1"></i> {{ __('Bikram Sambat Calendar (BS)') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#ad-calendar-view" class="nav-link font-weight-semibold" data-toggle="tab">
                        <i class="icon-calendar2 mr-1"></i> {{ __('Gregorian Calendar (AD)') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#all-holidays" class="nav-link font-weight-semibold" data-toggle="tab">
                        <i class="icon-list mr-1"></i> {{ __('All Holidays & Events') }}
                        <span class="badge badge-secondary ml-1 font-size-xs">{{ $holidays->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#new-holiday" id="tab-new-holiday-link" class="nav-link font-weight-semibold" data-toggle="tab">
                        <i class="icon-plus2 mr-1"></i> {{ __('Add Holiday / Event') }}
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                {{-- Bikram Sambat BS Calendar Tab --}}
                <div class="tab-pane fade show active" id="bs-calendar-view">
                    <div id="holiday-bs-calendar"></div>

                    {{-- Category Legend Bar --}}
                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <span class="font-size-xs font-weight-bold text-uppercase text-muted mr-2">{{ __('Legend') }}:</span>
                            <span class="d-inline-flex align-items-center mr-3 mb-1 font-size-xs">
                                <span class="mr-1 d-inline-block rounded-circle" style="width: 10px; height: 10px; background-color: #ef4444;"></span>
                                <span class="text-dark font-weight-medium">{{ __('Public / School Holiday (Closure)') }}</span>
                            </span>
                            <span class="d-inline-flex align-items-center mr-3 mb-1 font-size-xs">
                                <span class="mr-1 d-inline-block rounded-circle" style="width: 10px; height: 10px; background-color: #f59e0b;"></span>
                                <span class="text-dark font-weight-medium">{{ __('Examination Period') }}</span>
                            </span>
                            <span class="d-inline-flex align-items-center mr-3 mb-1 font-size-xs">
                                <span class="mr-1 d-inline-block rounded-circle" style="width: 10px; height: 10px; background-color: #3b82f6;"></span>
                                <span class="text-dark font-weight-medium">{{ __('Academic Event') }}</span>
                            </span>
                            <span class="d-inline-flex align-items-center mr-3 mb-1 font-size-xs">
                                <span class="mr-1 d-inline-block rounded-circle" style="width: 10px; height: 10px; background-color: #10b981;"></span>
                                <span class="text-dark font-weight-medium">{{ __('Custom Working Day') }}</span>
                            </span>
                            <span class="d-inline-flex align-items-center mr-3 mb-1 font-size-xs">
                                <span class="mr-1 d-inline-block rounded-circle" style="width: 10px; height: 10px; background-color: #6366f1;"></span>
                                <span class="text-dark font-weight-medium">{{ __('Notice / Announcement') }}</span>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- FullCalendar Gregorian AD Tab --}}
                <div class="tab-pane fade" id="ad-calendar-view">
                    <div class="row mb-3">
                        <div class="col-md-12 d-flex flex-wrap gap-2">
                            <span class="badge badge-danger p-2 mr-2"><i class="icon-primitive-dot"></i> {{ __('Public / School Holiday') }}</span>
                            <span class="badge badge-warning p-2 mr-2 text-white"><i class="icon-primitive-dot"></i> {{ __('Examination Period') }}</span>
                            <span class="badge badge-primary p-2 mr-2"><i class="icon-primitive-dot"></i> {{ __('Academic Event') }}</span>
                            <span class="badge badge-success p-2 mr-2"><i class="icon-primitive-dot"></i> {{ __('Custom Working Day') }}</span>
                        </div>
                    </div>
                    <div class="fullcalendar-basic" id="school-holiday-calendar"></div>
                </div>

                {{-- Table Tab --}}
                <div class="tab-pane fade" id="all-holidays">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped datatable-button-html5-columns">
                            <thead class="bg-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>{{ __('Event Title') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Dates (BS)') }}</th>
                                <th>{{ __('Dates (AD)') }}</th>
                                <th>{{ __('School Closure') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th class="text-center" style="width: 100px;">{{ __('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($holidays as $h)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="font-weight-semibold text-dark">{{ $h->title }}</div>
                                        @if($h->title_ne && $h->title_ne !== $h->title)
                                            <div class="text-muted font-size-xs">{{ $h->title_ne }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($h->type === 'public_holiday')
                                            <span class="badge badge-danger" style="border-radius: 6px; padding: 4px 8px;">{{ __('Public Holiday') }}</span>
                                        @elseif($h->type === 'school_holiday')
                                            <span class="badge badge-danger" style="border-radius: 6px; padding: 4px 8px;">{{ __('School Holiday') }}</span>
                                        @elseif($h->type === 'exam_period')
                                            <span class="badge badge-warning text-white" style="border-radius: 6px; padding: 4px 8px;">{{ __('Exam Period') }}</span>
                                        @elseif($h->type === 'academic_event')
                                            <span class="badge badge-primary" style="border-radius: 6px; padding: 4px 8px;">{{ __('Academic Event') }}</span>
                                        @else
                                            <span class="badge badge-success" style="border-radius: 6px; padding: 4px 8px;">{{ __('Working Day') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="font-weight-semibold text-dark">{{ $h->start_date_bs }}</span>
                                        @if($h->start_date_bs !== $h->end_date_bs)
                                            <span class="text-muted font-size-xs d-block">to {{ $h->end_date_bs }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-dark">{{ $h->start_date ? $h->start_date->format('d M Y') : '-' }}</span>
                                        @if($h->end_date && $h->start_date && $h->start_date->format('Y-m-d') !== $h->end_date->format('Y-m-d'))
                                            <span class="text-muted font-size-xs d-block">to {{ $h->end_date->format('d M Y') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($h->is_closure)
                                            <span class="badge badge-danger" style="border-radius: 6px; padding: 4px 8px;"><i class="icon-lock5 mr-1 font-size-xs"></i>{{ __('Closed') }}</span>
                                        @else
                                            <span class="badge badge-success" style="border-radius: 6px; padding: 4px 8px;"><i class="icon-unlocked mr-1 font-size-xs"></i>{{ __('Open') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-muted font-size-xs">{{ \Illuminate\Support\Str::limit($h->description ?: '-', 40) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <form method="post" action="{{ route('holidays.destroy', $h->id) }}" onsubmit="return confirm('{{ __('Are you sure you want to delete this event?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Delete Event') }}" style="border-radius: 6px;">
                                                <i class="icon-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="icon-calendar-cross icon-2x mb-2 d-block text-muted"></i>
                                        <span class="font-weight-medium">{{ __('No holidays or scheduled school breaks found.') }}</span>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Add Holiday Tab --}}
                <div class="tab-pane fade" id="new-holiday">
                    <form method="post" action="{{ route('holidays.store') }}" class="p-2">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-semibold text-dark">{{ __('Event Title (English)') }} <span class="text-danger">*</span></label>
                                <input name="title" value="{{ old('title') }}" required type="text" class="form-control" placeholder="e.g. Dashain Vacation">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-semibold text-dark">{{ __('Event Title (Nepali)') }}</label>
                                <input name="title_ne" value="{{ old('title_ne') }}" type="text" class="form-control" placeholder="उदा: दशैं बिदा">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-semibold text-dark">{{ __('Event Type') }} <span class="text-danger">*</span></label>
                                <select name="type" required class="form-control select">
                                    <option value="public_holiday" {{ old('type') === 'public_holiday' ? 'selected' : '' }}>{{ __('Public Holiday (सार्वजनिक बिदा)') }}</option>
                                    <option value="school_holiday" {{ old('type', 'school_holiday') === 'school_holiday' ? 'selected' : '' }}>{{ __('School Holiday (विद्यालय बिदा)') }}</option>
                                    <option value="exam_period" {{ old('type') === 'exam_period' ? 'selected' : '' }}>{{ __('Examination Period (परीक्षा अवधि)') }}</option>
                                    <option value="academic_event" {{ old('type') === 'academic_event' ? 'selected' : '' }}>{{ __('Academic Event / Celebration (शैक्षिक कार्यक्रम)') }}</option>
                                    <option value="custom_working_day" {{ old('type') === 'custom_working_day' ? 'selected' : '' }}>{{ __('Custom Working Day (कार्य दिन)') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-semibold text-dark">{{ __('Start Date (BS)') }} <span class="text-danger">*</span></label>
                                <input name="start_date_bs" value="{{ old('start_date_bs', $today_bs['bs_date'] ?? '') }}" required type="text" class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-semibold text-dark">{{ __('End Date (BS)') }} <span class="text-danger">*</span></label>
                                <input name="end_date_bs" value="{{ old('end_date_bs', $today_bs['bs_date'] ?? '') }}" required type="text" class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="font-weight-semibold text-dark">{{ __('Description / Notes') }}</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="{{ __('Optional details or notification remarks...') }}">{{ old('description') }}</textarea>
                            </div>
                            <div class="col-md-4 mb-3 d-flex align-items-center pt-3">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" name="is_closure" value="1" class="custom-control-input" id="is_closure" {{ old('is_closure', '1') ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-semibold text-danger" for="is_closure">
                                        {{ __('School remains closed on this day/period') }}
                                    </label>
                                    <div class="text-muted font-size-xs mt-1">{{ __('Affects student attendance calculations and marking as holiday.') }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="text-right border-top pt-3 mt-2">
                            <button type="submit" class="btn text-white px-4 font-weight-semibold shadow-sm" style="background: #FF5700; border-radius: 8px;">
                                <i class="icon-checkmark mr-1"></i> {{ __('Save & Broadcast Holiday') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Event Details Modal --}}
    <div class="modal fade" id="eventDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header d-flex align-items-center justify-content-between" style="background: #111827; color: #FFFFFF; border-bottom: 1px solid rgba(255,255,255,0.08);">
                    <h6 class="modal-title font-weight-bold mb-0 text-white" id="eventModalTitle">{{ __('Event Details') }}</h6>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.8;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3 d-flex align-items-center">
                        <span class="badge" id="eventModalType" style="background: #FF5700; color: #fff; padding: 4px 8px; border-radius: 6px;">EVENT</span>
                        <span class="text-muted font-size-sm ml-2 font-weight-medium" id="eventModalDate"></span>
                    </div>
                    <p class="text-dark mb-0" id="eventModalDesc" style="white-space: pre-wrap; font-size: 0.95rem; line-height: 1.6;"></p>
                </div>
                <div class="modal-footer bg-light p-3">
                    <a href="#" id="eventModalLink" class="btn btn-outline-primary btn-sm font-weight-semibold" style="display: none; border-radius: 6px;">{{ __('View Full Details') }}</a>
                    <button type="button" class="btn btn-secondary btn-sm font-weight-semibold" data-dismiss="modal" style="border-radius: 6px;">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Initialize Native Bikram Sambat BS Calendar
        if ($('#holiday-bs-calendar').length) {
            new NepaliBsCalendar('#holiday-bs-calendar', {
                feedUrl: '{{ route("calendar.events") }}',
                createEventUrl: null,
                canCreateEvent: false,
                initialView: 'month'
            });
        }

        // Initialize FullCalendar (Gregorian AD)
        if ($('#school-holiday-calendar').length) {
            $('#school-holiday-calendar').fullCalendar({
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'month,agendaWeek,agendaDay'
                },
                events: '{{ route("calendar.events") }}',
                editable: false,
                eventLimit: true
            });
        }

        // Re-render FullCalendar when switching to its tab to ensure correct dimensions
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            if ($(e.target).attr('href') === '#ad-calendar-view') {
                $('#school-holiday-calendar').fullCalendar('render');
            }
        });
    });
</script>
@endsection
