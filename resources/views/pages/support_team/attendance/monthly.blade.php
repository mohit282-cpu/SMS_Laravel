@extends('layouts.master')
@section('page_title', __('Monthly Class Attendance Sheet (BS)'))
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
                            {{ __('Monthly Class Attendance Sheet (BS)') }}
                        </h3>
                        <p class="text-muted mb-0 font-size-sm">
                            {{ __('Bikram Sambat monthly register matrix, holiday markings & attendance percentage summary') }}
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
                    <a href="{{ route('attendance.index', ['my_class_id' => $selected_class, 'section_id' => $selected_section]) }}" class="btn btn-outline-primary btn-sm font-weight-semibold shadow-sm ml-md-2" style="border-radius: 8px;">
                        <i class="icon-user-check mr-1"></i>{{ __('Daily Attendance') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(isset($has_assignment) && !$has_assignment)
        <div class="alert alert-warning border-0 shadow-sm" style="border-radius: 12px;">
            <h5 class="alert-heading font-weight-semibold mb-1"><i class="icon-warning22 mr-1"></i> {{ __('No Class / Section Assigned') }}</h5>
            {{ __('You are not currently assigned as a class teacher to any class or section. Student attendance cannot be accessed or recorded until an administrator assigns you to a section.') }}
        </div>
    @else
        {{-- Filter Card --}}
        <div class="card shadow-sm border-0 mb-4 d-print-none" style="border-radius: 14px;">
            <div class="card-header d-flex align-items-center justify-content-between bg-white border-bottom py-3">
                <h5 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.05rem;">
                    <i class="icon-filter3 mr-2" style="color: #FF5700;"></i>{{ __('Monthly Attendance Sheet (Bikram Sambat)') }}
                </h5>
                {!! Qs::getPanelOptions() !!}
            </div>

            <div class="card-body p-3 p-md-4">
                <form method="get" action="{{ route('attendance.monthly') }}">
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-2 mb-md-0">
                            <label class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('Class') }} <span class="text-danger">*</span></label>
                            <select onchange="getAttendanceSections(this.value)" required id="my_class_id" name="my_class_id" class="form-control select">
                                <option value="">{{ __('Select Class') }}</option>
                                @foreach($my_classes as $c)
                                    <option {{ ($selected_class == $c->id) ? 'selected' : '' }} value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3 mb-2 mb-md-0">
                            <label class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('Section') }}</label>
                            <select id="section_id" name="section_id" class="form-control select">
                                @if(Auth::user()->user_type !== 'teacher' || count($sections) > 1)
                                    <option value="">{{ __('All Sections') }}</option>
                                @endif
                                @foreach($sections as $s)
                                    <option {{ ($selected_section == $s->id) ? 'selected' : '' }} value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2 mb-2 mb-md-0">
                            <label class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('BS Year') }} <span class="text-danger">*</span></label>
                            <input type="number" name="year_bs" value="{{ $selected_year }}" min="2000" max="2090" required class="form-control" placeholder="2083">
                        </div>

                        <div class="col-md-2 mb-2 mb-md-0">
                            <label class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('BS Month') }} <span class="text-danger">*</span></label>
                            <select name="month_bs" required class="form-control select">
                                @foreach(App\Services\BikramSambatService::MONTHS_NE as $mIdx => $mName)
                                    <option {{ ($selected_month == $mIdx) ? 'selected' : '' }} value="{{ $mIdx }}">
                                        {{ $mName }} ({{ App\Services\BikramSambatService::MONTHS_EN[$mIdx] }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn text-white btn-block font-weight-semibold shadow-sm" style="background: #FF5700; border-radius: 8px;">
                                <i class="icon-filter3 mr-1"></i> {{ __('View Sheet') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if($matrix_data)
            @php
                $matrixCollection = collect($matrix_data['matrix']);
                $enrolledCount = $matrixCollection->count();
                $totalPresent = $matrixCollection->sum(fn($r) => $r['stats']['present'] ?? 0);
                $avgPct = $enrolledCount > 0 ? round($matrixCollection->avg(fn($r) => $r['stats']['percentage'] ?? 0), 1) : 0;
            @endphp

            {{-- 4 Metric KPI Stat Cards --}}
            <div class="row mb-4 d-print-none">
                <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
                    <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #4f46e5 !important;">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Enrolled Students') }}</span>
                                    <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($enrolledCount) }}</h3>
                                    <span class="font-size-xs text-muted">{{ __('In monthly register') }}</span>
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
                                    <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Days in Month') }}</span>
                                    <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ $matrix_data['days_in_month'] }}</h3>
                                    <span class="font-size-xs text-muted">{{ $matrix_data['month_name_en'] }} {{ $matrix_data['year_bs'] }} BS</span>
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
                                    <h3 class="font-weight-bold text-success mb-0 mt-1">{{ number_format($totalPresent) }}</h3>
                                    <span class="font-size-xs text-muted">{{ __('Cumulative student-days') }}</span>
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
                                    <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Class Average') }}</span>
                                    <h3 class="font-weight-bold {{ $avgPct >= 75 ? 'text-success' : 'text-warning' }} mb-0 mt-1">{{ $avgPct }}%</h3>
                                    <span class="font-size-xs text-muted">{{ __('Monthly attendance rate') }}</span>
                                </div>
                                <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1);">
                                    <i class="icon-stats-growth icon-2x" style="color: #f59e0b;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0" style="border-radius: 14px;">
                <div class="card-header d-flex align-items-center justify-content-between bg-white border-bottom py-3">
                    <div>
                        <h5 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.05rem;">
                            {{ $matrix_data['month_name'] }} ({{ $matrix_data['month_name_en'] }}) {{ $matrix_data['year_bs'] }} BS - {{ __('Attendance Sheet') }}
                        </h5>
                        <span class="text-muted font-size-xs">
                            {{ __('Days in month') }}: {{ $matrix_data['days_in_month'] }} &bull; {{ __('P = Present, A = Absent, L = Late, V = Leave, H = Half Day') }}
                        </span>
                    </div>
                    <div class="d-print-none">
                        <button type="button" class="btn btn-sm btn-outline-secondary font-weight-semibold" style="border-radius: 8px;" onclick="window.print()">
                            <i class="icon-printer mr-1"></i> {{ __('Print Sheet') }}
                        </button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm text-center font-size-sm mb-0">
                            <thead class="bg-light">
                            <tr>
                                <th rowspan="2" style="width: 40px;">#</th>
                                <th rowspan="2" class="text-left" style="min-width: 140px;">{{ __('Student Name') }}</th>
                                <th rowspan="2" style="width: 80px;">{{ __('Adm No') }}</th>
                                @for($d = 1; $d <= $matrix_data['days_in_month']; $d++)
                                    @php
                                        $dInfo = $matrix_data['dates'][$d];
                                        $isOff = ($dInfo['is_holiday'] || $dInfo['is_saturday']);
                                    @endphp
                                    <th style="{{ $isOff ? 'background-color: #fee2e2; color: #b91c1c;' : '' }}" title="{{ $dInfo['is_holiday'] ? $dInfo['holiday_title'] : ($dInfo['is_saturday'] ? 'Saturday' : '') }}">
                                        {{ $d }}
                                    </th>
                                @endfor
                                <th colspan="4" class="bg-dark text-white">{{ __('Summary') }}</th>
                            </tr>
                            <tr>
                                @for($d = 1; $d <= $matrix_data['days_in_month']; $d++)
                                    @php
                                        $dInfo = $matrix_data['dates'][$d];
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
                            @forelse($matrix_data['matrix'] as $row)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="text-left font-weight-semibold text-nowrap">
                                        <a href="{{ route('attendance.student_history', $row['student_record']->id) }}" target="_blank">{{ $row['name'] }}</a>
                                    </td>
                                    <td><code>{{ $row['adm_no'] }}</code></td>
                                    @for($d = 1; $d <= $matrix_data['days_in_month']; $d++)
                                        @php
                                            $dInfo = $matrix_data['dates'][$d];
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
                                    <td colspan="{{ $matrix_data['days_in_month'] + 7 }}" class="text-center py-4 text-muted">
                                        {{ __('No students found for this class or section.') }}
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    @endif

    <script>
        function getAttendanceSections(classId) {
            if (!classId) return;
            var url = '{{ route('attendance.get_sections', ':id') }}'.replace(':id', classId);
            var sectionSelect = $('#section_id');
            $.ajax({
                dataType: 'json',
                url: url,
                success: function(resp) {
                    sectionSelect.empty();
                    if (resp.length > 1 || {{ Auth::user()->user_type !== 'teacher' ? 'true' : 'false' }}) {
                        sectionSelect.append($('<option>', { value: '', text: '{{ __('All Sections') }}' }));
                    }
                    $.each(resp, function(i, s) {
                        sectionSelect.append($('<option>', { value: s.id, text: s.name }));
                    });
                }
            });
        }
    </script>

@endsection
