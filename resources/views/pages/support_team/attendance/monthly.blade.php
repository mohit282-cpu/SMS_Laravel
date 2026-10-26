@extends('layouts.master')
@section('page_title', __('Monthly Class Attendance Sheet (BS)'))
@section('content')

    @if(isset($has_assignment) && !$has_assignment)
        <div class="alert alert-warning border-0">
            <h5 class="alert-heading font-weight-semibold mb-1"><i class="icon-warning22 mr-1"></i> {{ __('No Class / Section Assigned') }}</h5>
            {{ __('You are not currently assigned as a class teacher to any class or section. Student attendance cannot be accessed or recorded until an administrator assigns you to a section.') }}
        </div>
    @else
        <div class="card d-print-none">
            <div class="card-header header-elements-inline">
                <h6 class="card-title font-weight-bold">{{ __('Monthly Attendance Sheet (Bikram Sambat)') }}</h6>
                {!! Qs::getPanelOptions() !!}
            </div>

            <div class="card-body">
                <form method="get" action="{{ route('attendance.monthly') }}">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="font-weight-bold">{{ __('Class') }}: <span class="text-danger">*</span></label>
                            <select onchange="getAttendanceSections(this.value)" required id="my_class_id" name="my_class_id" class="form-control select">
                                <option value="">{{ __('Select Class') }}</option>
                                @foreach($my_classes as $c)
                                    <option {{ ($selected_class == $c->id) ? 'selected' : '' }} value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="font-weight-bold">{{ __('Section') }}:</label>
                            <select id="section_id" name="section_id" class="form-control select">
                                @if(Auth::user()->user_type !== 'teacher' || count($sections) > 1)
                                    <option value="">{{ __('All Sections') }}</option>
                                @endif
                                @foreach($sections as $s)
                                    <option {{ ($selected_section == $s->id) ? 'selected' : '' }} value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="font-weight-bold">{{ __('BS Year') }}: <span class="text-danger">*</span></label>
                            <input type="number" name="year_bs" value="{{ $selected_year }}" min="2000" max="2090" required class="form-control" placeholder="2083">
                        </div>

                        <div class="col-md-2">
                            <label class="font-weight-bold">{{ __('BS Month') }}: <span class="text-danger">*</span></label>
                            <select name="month_bs" required class="form-control select">
                                @foreach(App\Services\BikramSambatService::MONTHS_NE as $mIdx => $mName)
                                    <option {{ ($selected_month == $mIdx) ? 'selected' : '' }} value="{{ $mIdx }}">
                                        {{ $mName }} ({{ App\Services\BikramSambatService::MONTHS_EN[$mIdx] }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2 pt-md-4">
                            <button type="submit" class="btn btn-primary btn-block"><i class="icon-filter3 mr-1"></i> {{ __('View Sheet') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if($matrix_data)
            <div class="card">
                <div class="card-header header-elements-inline">
                    <div>
                        <h6 class="card-title font-weight-bold">
                            {{ $matrix_data['month_name'] }} ({{ $matrix_data['month_name_en'] }}) {{ $matrix_data['year_bs'] }} BS - {{ __('Attendance Sheet') }}
                        </h6>
                        <span class="text-muted font-size-sm">
                            {{ __('Days in month') }}: {{ $matrix_data['days_in_month'] }} | {{ __('P = Present, A = Absent, L = Late, V = Leave, H = Half Day') }}
                        </span>
                    </div>
                    <div class="header-elements">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="icon-printer mr-1"></i> {{ __('Print Sheet') }}</button>
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
                                    <th style="{{ $isOff ? 'background-color: #f1f1f1; color: #dc3545;' : '' }}" title="{{ $dInfo['is_holiday'] ? $dInfo['holiday_title'] : ($dInfo['is_saturday'] ? 'Saturday' : '') }}">
                                        {{ $d }}
                                    </th>
                                @endfor
                                <th colspan="4" class="bg-secondary text-white">{{ __('Summary') }}</th>
                            </tr>
                            <tr>
                                @for($d = 1; $d <= $matrix_data['days_in_month']; $d++)
                                    @php
                                        $dInfo = $matrix_data['dates'][$d];
                                        $isOff = ($dInfo['is_holiday'] || $dInfo['is_saturday']);
                                    @endphp
                                    <th style="font-size: 10px; {{ $isOff ? 'background-color: #f1f1f1; color: #dc3545;' : '' }}">
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
                                    <td>{{ $row['adm_no'] }}</td>
                                    @for($d = 1; $d <= $matrix_data['days_in_month']; $d++)
                                        @php
                                            $dInfo = $matrix_data['dates'][$d];
                                            $isOff = ($dInfo['is_holiday'] || $dInfo['is_saturday']);
                                            $status = $row['days'][$d];
                                        @endphp
                                        <td style="{{ $isOff ? 'background-color: #f9f9f9;' : '' }}">
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
                                    <td colspan="{{ $matrix_data['days_in_month'] + 7 }}" class="text-center py-3 text-muted">
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
