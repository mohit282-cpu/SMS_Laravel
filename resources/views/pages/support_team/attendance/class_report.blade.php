@extends('layouts.master')
@section('page_title', __('Class-wise Attendance Comparison Report (BS)'))
@section('content')

    {{-- Executive Header --}}
    <div class="card sms-card mb-4 d-print-none">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center">
                    <div class="d-inline-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px; border-radius: 14px; background: rgba(255, 87, 0, 0.12);">
                        <i class="icon-stats-growth icon-2x" style="color: #FF5700;"></i>
                    </div>
                    <div>
                        <h3 class="font-weight-bold mb-1 text-dark" style="font-size: 1.35rem; letter-spacing: -0.01em;">
                            {{ __('Class-wise Attendance Comparison Report') }}
                        </h3>
                        <p class="text-muted mb-0 font-size-sm">
                            {{ __('Cross-class daily attendance compliance, school-wide turnout rate & section drill-down') }}
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
                    <a href="{{ route('attendance.index', ['attendance_date_bs' => $selected_date]) }}" class="btn btn-outline-primary btn-sm font-weight-semibold shadow-sm ml-md-2" style="border-radius: 8px;">
                        <i class="icon-user-check mr-1"></i>{{ __('Daily Attendance') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(isset($has_assignment) && !$has_assignment)
        <div class="alert alert-warning border-0 shadow-sm" style="border-radius: 12px;">
            <h5 class="alert-heading font-weight-semibold mb-1"><i class="icon-warning22 mr-1"></i> {{ __('No Class / Section Assigned') }}</h5>
            {{ __('You are not currently assigned as a class teacher to any class or section. Student attendance comparison reports cannot be accessed until an administrator assigns you to a section.') }}
        </div>
    @else
        {{-- Filter Form Card --}}
        <div class="card shadow-sm border-0 mb-4 d-print-none" style="border-radius: 14px;">
            <div class="card-header d-flex align-items-center justify-content-between bg-white border-bottom py-3">
                <h5 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.05rem;">
                    <i class="icon-filter3 mr-2" style="color: #FF5700;"></i>{{ __('Select Date for Comparison') }}
                </h5>
                {!! Qs::getPanelOptions() !!}
            </div>

            <div class="card-body p-3 p-md-4">
                <form method="get" action="{{ route('attendance.class_report') }}">
                    <div class="row align-items-end">
                        <div class="col-md-4 mb-2 mb-md-0">
                            <label class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('Select Attendance Date (BS)') }} <span class="text-danger">*</span></label>
                            <input type="text" name="attendance_date_bs" value="{{ $selected_date }}" required class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                            @if(isset($date_info) && $date_info['ad_date'])
                                <span class="form-text text-muted font-size-xs mb-0">{{ $date_info['ad_date'] }} AD</span>
                            @endif
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <button type="submit" class="btn text-white btn-block font-weight-semibold shadow-sm" style="background: #FF5700; border-radius: 8px;">
                                <i class="icon-filter3 mr-1"></i> {{ __('Generate Report') }}
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-outline-secondary btn-block font-weight-semibold" style="border-radius: 8px;" onclick="window.print()">
                                <i class="icon-printer mr-1"></i> {{ __('Print Report') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @php
            $reportCol = collect($classes_report);
            $totEnrolled = $reportCol->sum('total_students');
            $totRecorded = $reportCol->sum('recorded_attendance');
            $totPresent = $reportCol->sum('present');
            $totAbsent = $reportCol->sum('absent');
            $overallPct = $totRecorded > 0 ? round(($totPresent / $totRecorded) * 100, 1) : 0.0;
        @endphp

        {{-- 4 Metric KPI Stat Cards --}}
        <div class="row mb-4 d-print-none">
            <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
                <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #4f46e5 !important;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Total Enrolled') }}</span>
                                <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($totEnrolled) }}</h3>
                                <span class="font-size-xs text-muted">{{ __('Across :count classes', ['count' => $reportCol->count()]) }}</span>
                            </div>
                            <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(79, 70, 229, 0.1);">
                                <i class="icon-users icon-2x" style="color: #4f46e5;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
                <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #06b6d4 !important;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Recorded Today') }}</span>
                                <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($totRecorded) }}</h3>
                                <span class="font-size-xs text-muted">{{ __('Students marked') }}</span>
                            </div>
                            <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(6, 182, 212, 0.1);">
                                <i class="icon-clipboard3 icon-2x" style="color: #06b6d4;"></i>
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
                                <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Present Students') }}</span>
                                <h3 class="font-weight-bold text-success mb-0 mt-1">{{ number_format($totPresent) }}</h3>
                                <span class="font-size-xs text-muted">{{ __('Absent: :count', ['count' => $totAbsent]) }}</span>
                            </div>
                            <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16, 185, 129, 0.1);">
                                <i class="icon-checkmark4 icon-2x" style="color: #10b981;"></i>
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
                                <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('School Turnout') }}</span>
                                <h3 class="font-weight-bold {{ $overallPct >= 75 ? 'text-success' : 'text-warning' }} mb-0 mt-1">{{ $overallPct }}%</h3>
                                <span class="font-size-xs text-muted">{{ __('Overall attendance rate') }}</span>
                            </div>
                            <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1);">
                                <i class="icon-stats-growth icon-2x" style="color: #f59e0b;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(isset($date_info) && $date_info['warning'])
            <div class="alert alert-{{ $date_info['is_holiday'] ? 'danger' : 'warning' }} border-0 alert-dismissible shadow-sm" style="border-radius: 12px;">
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                <span class="font-weight-semibold"><i class="icon-warning22 mr-1"></i> {{ $date_info['warning'] }}</span>
            </div>
        @endif

        <div class="card shadow-sm border-0" style="border-radius: 14px;">
            <div class="card-header d-flex align-items-center justify-content-between bg-white border-bottom py-3">
                <h5 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.05rem;">
                    <i class="icon-list mr-2" style="color: #FF5700;"></i>{{ __('Attendance Overview by Class') }}
                    <span class="text-muted ml-2 font-size-sm">[{{ $selected_date }} BS]</span>
                </h5>
            </div>

            <div class="card-body p-3 p-md-4">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="bg-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>{{ __('Class') }}</th>
                            <th>{{ __('Enrolled') }}</th>
                            <th>{{ __('Recorded') }}</th>
                            <th>{{ __('Present') }}</th>
                            <th>{{ __('Absent') }}</th>
                            <th>{{ __('Late') }}</th>
                            <th>{{ __('Leave') }}</th>
                            <th style="min-width: 160px;">{{ __('Attendance %') }}</th>
                            <th class="d-print-none text-center">{{ __('Action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($classes_report as $cr)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="font-weight-bold text-dark">{{ $cr['class']->name }}</td>
                                <td>{{ $cr['total_students'] }}</td>
                                <td>
                                    @if($cr['recorded_attendance'] > 0)
                                        <span class="badge badge-success" style="border-radius: 6px; padding: 4px 8px;">{{ $cr['recorded_attendance'] }} / {{ $cr['total_students'] }}</span>
                                    @else
                                        <span class="badge badge-secondary" style="border-radius: 6px; padding: 4px 8px;">{{ __('Not Recorded') }}</span>
                                    @endif
                                </td>
                                <td class="text-success font-weight-semibold">{{ $cr['present'] }}</td>
                                <td class="text-danger font-weight-semibold">{{ $cr['absent'] }}</td>
                                <td class="text-warning font-weight-semibold">{{ $cr['late'] }}</td>
                                <td class="text-info font-weight-semibold">{{ $cr['leave'] }}</td>
                                <td>
                                    @if($cr['recorded_attendance'] > 0)
                                        <div class="d-flex align-items-center">
                                            <div class="progress w-100 mr-2" style="height: 10px; border-radius: 5px;">
                                                <div class="progress-bar {{ $cr['percentage'] >= 75 ? 'bg-success' : ($cr['percentage'] >= 60 ? 'bg-warning' : 'bg-danger') }}" style="width: {{ $cr['percentage'] }}%;"></div>
                                            </div>
                                            <span class="font-weight-bold font-size-sm">{{ $cr['percentage'] }}%</span>
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="d-print-none text-center">
                                    <a href="{{ route('attendance.index', ['my_class_id' => $cr['class']->id, 'attendance_date_bs' => $selected_date]) }}" class="btn btn-sm btn-outline-primary font-weight-semibold" style="border-radius: 6px;">
                                        <i class="icon-eye mr-1"></i>{{ __('View / Record') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot class="bg-light font-weight-bold">
                        <tr>
                            <td colspan="2">{{ __('Total School Summary') }}</td>
                            <td>{{ $totEnrolled }}</td>
                            <td>{{ $totRecorded }}</td>
                            <td class="text-success">{{ $totPresent }}</td>
                            <td class="text-danger">{{ $totAbsent }}</td>
                            <td colspan="2"></td>
                            <td>
                                <span>{{ $overallPct }}%</span>
                            </td>
                            <td class="d-print-none"></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif

@endsection
