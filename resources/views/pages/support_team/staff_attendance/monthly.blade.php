@extends('layouts.master')
@section('page_title', __('Monthly Staff Attendance Summary (BS)'))
@section('content')

    <div class="card d-print-none">
        <div class="card-header header-elements-inline">
            <h6 class="card-title font-weight-bold">{{ __('Monthly Staff Attendance Summary (Bikram Sambat)') }}</h6>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <form method="get" action="{{ route('staff_attendance.monthly') }}">
                <div class="row">
                    <div class="col-md-3">
                        <label class="font-weight-bold">{{ __('BS Year') }}: <span class="text-danger">*</span></label>
                        <input type="number" name="year_bs" value="{{ $selected_year }}" min="2000" max="2090" required class="form-control" placeholder="2083">
                    </div>

                    <div class="col-md-3">
                        <label class="font-weight-bold">{{ __('BS Month') }}: <span class="text-danger">*</span></label>
                        <select name="month_bs" required class="form-control select">
                            @foreach(App\Services\BikramSambatService::MONTHS_NE as $mIdx => $mName)
                                <option {{ ($selected_month == $mIdx) ? 'selected' : '' }} value="{{ $mIdx }}">
                                    {{ $mName }} ({{ App\Services\BikramSambatService::MONTHS_EN[$mIdx] }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 pt-md-4">
                        <button type="submit" class="btn btn-primary btn-block"><i class="icon-filter3 mr-1"></i> {{ __('Generate Sheet') }}</button>
                    </div>

                    <div class="col-md-3 pt-md-4">
                        <button type="button" class="btn btn-outline-secondary btn-block" onclick="window.print()"><i class="icon-printer mr-1"></i> {{ __('Print Sheet') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header header-elements-inline">
            <div>
                <h6 class="card-title font-weight-bold">
                    {{ $month_name }} ({{ $month_name_en }}) {{ $selected_year }} BS - {{ __('Staff Attendance Sheet') }}
                </h6>
                <span class="text-muted font-size-sm">
                    {{ __('Days in month') }}: {{ $days_in_month }} | {{ __('P = Present, A = Absent, L = Late, V = Leave, H = Half Day') }}
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
                            <th style="{{ $isOff ? 'background-color: #f1f1f1; color: #dc3545;' : '' }}" title="{{ $dInfo['is_holiday'] ? $dInfo['holiday_title'] : ($dInfo['is_saturday'] ? 'Saturday' : '') }}">
                                {{ $d }}
                            </th>
                        @endfor
                        <th colspan="4" class="bg-secondary text-white">{{ __('Summary') }}</th>
                    </tr>
                    <tr>
                        @for($d = 1; $d <= $days_in_month; $d++)
                            @php
                                $dInfo = $dates[$d];
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
                    @forelse($matrix as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="text-left font-weight-semibold text-nowrap">{{ $row['name'] }}</td>
                            <td><span class="badge badge-light border">{{ $row['role'] }}</span></td>
                            @for($d = 1; $d <= $days_in_month; $d++)
                                @php
                                    $dInfo = $dates[$d];
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
                            <td colspan="{{ $days_in_month + 7 }}" class="text-center py-3 text-muted">
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
