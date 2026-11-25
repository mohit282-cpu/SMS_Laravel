@extends('layouts.master')
@section('page_title', __('Monthly Staff Attendance Summary (BS)'))
@section('content')

    {{-- Executive Header --}}
    <div class="card sms-card mb-4 d-print-none">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center">
                    <div class="d-inline-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px; border-radius: 14px; background: rgba(255, 87, 0, 0.12);">
                        <i class="icon-calendar52 icon-2x" style="color: #FF5700;"></i>
                    </div>
                    <div>
                        <h3 class="font-weight-bold mb-1 text-dark" style="font-size: 1.35rem; letter-spacing: -0.01em;">
                            {{ __('Monthly Staff Attendance Summary (BS)') }}
                        </h3>
                        <p class="text-muted mb-0 font-size-sm">
                            {{ __('Bikram Sambat monthly staff register, holiday indicators & percentage payroll readiness') }}
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
                    <a href="{{ route('staff_attendance.index') }}" class="btn btn-outline-primary btn-sm font-weight-semibold shadow-sm ml-md-2" style="border-radius: 8px;">
                        <i class="icon-user-check mr-1"></i>{{ __('Daily Staff Attendance') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card shadow-sm border-0 mb-4 d-print-none" style="border-radius: 14px;">
        <div class="card-header d-flex align-items-center justify-content-between bg-white border-bottom py-3">
            <h5 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.05rem;">
                <i class="icon-filter3 mr-2" style="color: #FF5700;"></i>{{ __('Monthly Staff Attendance Summary (Bikram Sambat)') }}
            </h5>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body p-3 p-md-4">
            <form method="get" action="{{ route('staff_attendance.monthly') }}">
                <div class="row align-items-end">
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('BS Year') }} <span class="text-danger">*</span></label>
                        <input type="number" name="year_bs" value="{{ $selected_year }}" min="2000" max="2090" required class="form-control" placeholder="2083">
                    </div>

                    <div class="col-md-3 mb-2 mb-md-0">
                        <label class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('BS Month') }} <span class="text-danger">*</span></label>
                        <select name="month_bs" required class="form-control select">
                            @foreach(App\Services\BikramSambatService::MONTHS_NE as $mIdx => $mName)
                                <option {{ ($selected_month == $mIdx) ? 'selected' : '' }} value="{{ $mIdx }}">
                                    {{ $mName }} ({{ App\Services\BikramSambatService::MONTHS_EN[$mIdx] }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 mb-2 mb-md-0">
                        <button type="submit" class="btn text-white btn-block font-weight-semibold shadow-sm" style="background: #FF5700; border-radius: 8px;">
                            <i class="icon-filter3 mr-1"></i> {{ __('Generate Sheet') }}
                        </button>
                    </div>

                    <div class="col-md-3">
                        <button type="button" class="btn btn-outline-secondary btn-block font-weight-semibold" style="border-radius: 8px;" onclick="window.print()">
                            <i class="icon-printer mr-1"></i> {{ __('Print Sheet') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @php
        $staffCol = collect($matrix);
        $totalStaffCount = $staffCol->count();
        $totalPresentStaffDays = $staffCol->sum(fn($r) => $r['stats']['present'] ?? 0);
        $avgStaffPct = $totalStaffCount > 0 ? round($staffCol->avg(fn($r) => $r['stats']['percentage'] ?? 0), 1) : 0;
    @endphp

    {{-- 4 Metric KPI Stat Cards --}}
    <div class="row mb-4 d-print-none">
        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #4f46e5 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Total Staff') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($totalStaffCount) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Active staff roster') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(79, 70, 229, 0.1);">
                            <i class="icon-users4 icon-2x" style="color: #4f46e5;"></i>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Days in Month') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ $days_in_month }}</h3>
                            <span class="font-size-xs text-muted">{{ $month_name_en }} {{ $selected_year }} BS</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(6, 182, 212, 0.1);">
                            <i class="icon-calendar22 icon-2x" style="color: #06b6d4;"></i>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Total Present Days') }}</span>
                            <h3 class="font-weight-bold text-success mb-0 mt-1">{{ number_format($totalPresentStaffDays) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Cumulative staff-days') }}</span>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Average Turnout') }}</span>
                            <h3 class="font-weight-bold {{ $avgStaffPct >= 75 ? 'text-success' : 'text-warning' }} mb-0 mt-1">{{ $avgStaffPct }}%</h3>
                            <span class="font-size-xs text-muted">{{ __('Monthly staff rate') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1);">
                            <i class="icon-stats-growth icon-2x" style="color: #f59e0b;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Monthly Staff Matrix Card --}}
    <div class="card shadow-sm border-0" style="border-radius: 14px;">
        <div class="card-header d-flex align-items-center justify-content-between bg-white border-bottom py-3">
            <div>
                <h5 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.05rem;">
                    {{ $month_name }} ({{ $month_name_en }}) {{ $selected_year }} BS - {{ __('Staff Attendance Sheet') }}
                </h5>
                <span class="text-muted font-size-xs">
                    {{ __('Days in month') }}: {{ $days_in_month }} &bull; {{ __('P = Present, A = Absent, L = Late, V = Leave, H = Half Day') }}
                </span>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm text-center font-size-sm mb-0">
                    <thead class="bg-light">
                    <tr>
                        <th rowspan="2" style="width: 40px;">#</th>
                        <th rowspan="2" class="text-left" style="min-width: 140px;">{{ __('Staff Name') }}</th>
                        <th rowspan="2" style="width: 100px;">{{ __('Role') }}</th>
                        @for($d = 1; $d <= $days_in_month; $d++)
                            @php
                                $dInfo = $dates[$d];
                                $isOff = ($dInfo['is_holiday'] || $dInfo['is_saturday']);
                            @endphp
                            <th style="{{ $isOff ? 'background-color: #fee2e2; color: #b91c1c;' : '' }}" title="{{ $dInfo['is_holiday'] ? $dInfo['holiday_title'] : ($dInfo['is_saturday'] ? 'Saturday' : '') }}">
                                {{ $d }}
                            </th>
                        @endfor
                        <th colspan="4" class="bg-dark text-white">{{ __('Summary') }}</th>
                    </tr>
                    <tr>
                        @for($d = 1; $d <= $days_in_month; $d++)
                            @php
                                $dInfo = $dates[$d];
                                $isOff = ($dInfo['is_holiday'] || $dInfo['is_saturday']);
                            @endphp
                            <th style="font-size: 10px; {{ $isOff ? 'background-color: #fee2e2; color: #b91c1c;' : '' }}">
                                {{ $dInfo['is_saturday'] ? 'शनि' : '' }}
                            </th>
                        @endfor
                        <th class="bg-success text-white" title="Present">P</th>
                        <th class="bg-danger text-white" title="Absent">A</th>
                        <th class="bg-warning text-dark" title="Late">L</th>
                        <th class="bg-info text-white" title="Percentage">%</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($matrix as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="text-left font-weight-semibold text-nowrap text-dark">{{ $row['name'] }}</td>
                            <td><span class="badge badge-light border">{{ $row['role'] }}</span></td>
                            @for($d = 1; $d <= $days_in_month; $d++)
                                @php
                                    $dInfo = $dates[$d];
                                    $isOff = ($dInfo['is_holiday'] || $dInfo['is_saturday']);
                                    $status = $row['days'][$d];
                                @endphp
                                <td style="{{ $isOff ? 'background-color: #fef2f2;' : '' }}">
                                    @if($status === 'present')
                                        <span class="text-success font-weight-bold">P</span>
                                    @elseif($status === 'absent')
                                        <span class="text-danger font-weight-bold">A</span>
                                    @elseif($status === 'late')
                                        <span class="text-warning font-weight-bold">L</span>
                                    @elseif($status === 'leave')
                                        <span class="text-info font-weight-bold">V</span>
                                    @elseif($status === 'half_day')
                                        <span class="text-secondary font-weight-bold">H</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            @endfor
                            <td class="font-weight-bold text-success">{{ $row['stats']['present'] }}</td>
                            <td class="font-weight-bold text-danger">{{ $row['stats']['absent'] }}</td>
                            <td class="font-weight-bold text-warning">{{ $row['stats']['late'] }}</td>
                            <td class="font-weight-bold {{ $row['stats']['percentage'] >= 75 ? 'text-success' : 'text-danger' }}">
                                {{ $row['stats']['percentage'] }}%
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $days_in_month + 7 }}" class="text-center py-4 text-muted">
                                {{ __('No staff members found.') }}
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
