@extends('layouts.master')
@section('page_title', __('Academic Years (BS)'))
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
                            {{ __('Academic Years (BS) & Sessions') }}
                        </h3>
                        <p class="text-muted mb-0 font-size-sm">
                            {{ __('Nepali Bikram Sambat calendar cycles, session activation & institutional archiving') }}
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
                    <button type="button" class="btn btn-primary btn-sm font-weight-semibold shadow-sm ml-md-2" onclick="$('#tab-new-year-link').tab('show');" style="border-radius: 8px;">
                        <i class="icon-plus2 mr-1"></i>{{ __('Create Academic Year') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Active Session Highlight Banner --}}
    @if($current_session)
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 14px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(16, 185, 129, 0.02) 100%); border-left: 5px solid #10b981 !important;">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-center">
                        <div class="d-inline-flex align-items-center justify-content-center mr-3 text-white shadow-sm" style="width: 48px; height: 48px; border-radius: 12px; background: #10B981;">
                            <i class="icon-calendar52 icon-2x"></i>
                        </div>
                        <div>
                            <div class="text-uppercase font-size-xs text-muted font-weight-bold tracking-wider">{{ __('Current Active Academic Session') }}</div>
                            <h4 class="font-weight-bold mb-1 text-dark">
                                {{ $current_session->name }}
                                <span class="badge badge-success ml-2 py-1 px-2 font-size-xs">{{ __('Active') }}</span>
                            </h4>
                            <div class="text-muted font-size-xs">
                                <strong>{{ __('BS Range') }}:</strong> {{ $current_session->start_date_bs }} to {{ $current_session->end_date_bs }} BS
                                &bull; <strong>{{ __('AD Equivalent') }}:</strong> {{ $current_session->start_date ? $current_session->start_date->format('d M Y') : '-' }} to {{ $current_session->end_date ? $current_session->end_date->format('d M Y') : '-' }}
                            </div>
                        </div>
                    </div>
                    <div>
                        <a href="{{ route('academic_years.edit', $current_session->id) }}" class="btn btn-outline-success btn-sm font-weight-semibold shadow-sm" style="border-radius: 8px;">
                            <i class="icon-pencil mr-1"></i> {{ __('Edit Active Session') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- 4 Metric KPI Stat Cards --}}
    <div class="row mb-4">
        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #4f46e5 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Total Sessions') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($total_sessions ?? $academic_years->count()) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Registered school years') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(79, 70, 229, 0.1);">
                            <i class="icon-calendar52 icon-2x" style="color: #4f46e5;"></i>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Active Session') }}</span>
                            <h4 class="font-weight-bold text-success mb-0 mt-1 text-truncate" style="max-width: 140px;">{{ $current_session->name ?? __('None') }}</h4>
                            <span class="font-size-xs text-muted">{{ __('Authoritative cycle') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16, 185, 129, 0.1);">
                            <i class="icon-checkmark-circle2 icon-2x text-success"></i>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Open Sessions') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($open_sessions ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Active & upcoming') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(6, 182, 212, 0.1);">
                            <i class="icon-unlocked2 icon-2x" style="color: #06b6d4;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #f59e0b !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Archived Sessions') }}</span>
                            <h3 class="font-weight-bold text-warning mb-0 mt-1">{{ number_format($closed_sessions ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Closed historical cycles') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1);">
                            <i class="icon-archive icon-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Tabbed Container --}}
    <div class="card sms-card border-0 shadow-sm" style="border-radius: 16px;">
        <div class="card-header bg-white border-bottom p-0" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
            <ul class="nav nav-tabs nav-tabs-bottom mb-0 border-0 px-3 pt-2">
                <li class="nav-item">
                    <a href="#all-academic-years" id="tab-all-years-link" class="nav-link active font-weight-semibold" data-toggle="tab">
                        <i class="icon-calendar52 mr-1"></i> {{ __('All Academic Years') }}
                        <span class="badge badge-primary badge-pill ml-1">{{ $academic_years->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#new-academic-year" id="tab-new-year-link" class="nav-link font-weight-semibold" data-toggle="tab">
                        <i class="icon-plus2 mr-1"></i> {{ __('Create Academic Year') }}
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body p-3 p-md-4">
            <div class="tab-content">
                {{-- TAB 1: All Academic Years Table --}}
                <div class="tab-pane fade show active" id="all-academic-years">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped datatable-button-html5-columns">
                            <thead class="thead-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>{{ __('Session Name') }}</th>
                                <th>{{ __('Year (BS)') }}</th>
                                <th>{{ __('Start Date') }}</th>
                                <th>{{ __('End Date') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-center" style="width: 120px;">{{ __('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($academic_years as $ay)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="d-inline-flex align-items-center justify-content-center mr-2 text-primary bg-light rounded" style="width: 34px; height: 34px;">
                                                <i class="icon-calendar52"></i>
                                            </div>
                                            <div>
                                                <span class="font-weight-bold text-dark font-size-sm">{{ $ay->name }}</span>
                                                @if($ay->is_active)
                                                    <span class="badge badge-success py-0 px-1 ml-1 font-size-xs">{{ __('Current') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light border text-dark font-weight-semibold py-1 px-2" style="border-radius: 6px;">
                                            {{ App\Helpers\NepaliHelper::formatNumber($ay->year_bs) }} BS
                                        </span>
                                    </td>
                                    <td>
                                        <span class="font-weight-semibold text-dark">{{ $ay->start_date_bs }} BS</span>
                                        <span class="text-muted font-size-xs d-block">({{ $ay->start_date ? $ay->start_date->format('Y-m-d') : '' }} AD)</span>
                                    </td>
                                    <td>
                                        <span class="font-weight-semibold text-dark">{{ $ay->end_date_bs }} BS</span>
                                        <span class="text-muted font-size-xs d-block">({{ $ay->end_date ? $ay->end_date->format('Y-m-d') : '' }} AD)</span>
                                    </td>
                                    <td>
                                        @if($ay->is_active)
                                            <span class="badge badge-success py-1 px-2 font-weight-semibold shadow-sm" style="border-radius: 6px;">
                                                <i class="icon-checkmark-circle mr-1"></i>{{ __('Active Session') }}
                                            </span>
                                        @elseif($ay->is_closed)
                                            <span class="badge badge-secondary py-1 px-2 font-weight-semibold" style="border-radius: 6px;">
                                                <i class="icon-lock mr-1"></i>{{ __('Closed / Archived') }}
                                            </span>
                                        @else
                                            <span class="badge badge-light border text-muted py-1 px-2" style="border-radius: 6px;">
                                                <i class="icon-unlocked2 mr-1"></i>{{ __('Inactive Open') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="list-icons">
                                            <div class="dropdown">
                                                <a href="#" class="list-icons-item" data-toggle="dropdown" aria-expanded="false">
                                                    <i class="icon-menu9"></i>
                                                </a>

                                                <div class="dropdown-menu dropdown-menu-right shadow-sm">
                                                    <a href="{{ route('academic_years.edit', $ay->id) }}" class="dropdown-item">
                                                        <i class="icon-pencil text-primary"></i> {{ __('Edit Session') }}
                                                    </a>

                                                    @if(!$ay->is_active && !$ay->is_closed)
                                                        <a href="#" onclick="event.preventDefault(); document.getElementById('activate-form-{{ $ay->id }}').submit();" class="dropdown-item text-success">
                                                            <i class="icon-check"></i> {{ __('Set as Active Session') }}
                                                        </a>
                                                        <form id="activate-form-{{ $ay->id }}" method="post" action="{{ route('academic_years.activate', $ay->id) }}" style="display: none;">
                                                            @csrf @method('PUT')
                                                        </form>
                                                    @endif

                                                    @if($ay->is_closed)
                                                        <a href="#" onclick="event.preventDefault(); document.getElementById('reopen-form-{{ $ay->id }}').submit();" class="dropdown-item text-info">
                                                            <i class="icon-unlocked2"></i> {{ __('Reopen Session') }}
                                                        </a>
                                                        <form id="reopen-form-{{ $ay->id }}" method="post" action="{{ route('academic_years.reopen', $ay->id) }}" style="display: none;">
                                                            @csrf @method('PUT')
                                                        </form>
                                                    @else
                                                        <a href="#" onclick="event.preventDefault(); document.getElementById('close-form-{{ $ay->id }}').submit();" class="dropdown-item text-warning">
                                                            <i class="icon-lock"></i> {{ __('Close / Archive Session') }}
                                                        </a>
                                                        <form id="close-form-{{ $ay->id }}" method="post" action="{{ route('academic_years.close', $ay->id) }}" style="display: none;">
                                                            @csrf @method('PUT')
                                                        </form>
                                                    @endif

                                                    @if(!$ay->is_active)
                                                        <div class="dropdown-divider"></div>
                                                        <a id="{{ $ay->id }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item text-danger">
                                                            <i class="icon-trash"></i> {{ __('Delete Session') }}
                                                        </a>
                                                        <form method="post" id="item-delete-{{ $ay->id }}" action="{{ route('academic_years.destroy', $ay->id) }}" style="display: none;">
                                                            @csrf @method('DELETE')
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="icon-calendar52 icon-3x text-muted mb-2"></i>
                                            <h6 class="font-weight-bold text-dark">{{ __('No Academic Years Found') }}</h6>
                                            <p class="font-size-sm mb-3 text-muted">{{ __('Create your first Bikram Sambat academic session to enable institutional operations.') }}</p>
                                            <button type="button" class="btn btn-primary btn-sm" onclick="$('#tab-new-year-link').tab('show');">
                                                <i class="icon-plus2 mr-1"></i>{{ __('Create Academic Year') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB 2: Create Academic Year Form --}}
                <div class="tab-pane fade" id="new-academic-year">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="card border shadow-none" style="border-radius: 14px;">
                                <div class="card-header bg-light py-3" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                                    <h5 class="font-weight-bold mb-0 text-dark">
                                        <i class="icon-plus2 mr-2" style="color: #FF5700;"></i>{{ __('Configure New Academic Session (BS)') }}
                                    </h5>
                                    <span class="text-muted font-size-xs">{{ __('Provide session label, Bikram Sambat year, start and end dates, and activation preference.') }}</span>
                                </div>

                                <div class="card-body p-4">
                                    <form method="post" action="{{ route('academic_years.store') }}">
                                        @csrf
                                        <div class="form-group mb-3">
                                            <label class="font-weight-semibold">{{ __('Session Name') }}: <span class="text-danger">*</span></label>
                                            <input name="name" value="{{ old('name', '2083 BS') }}" required type="text" class="form-control" placeholder="{{ __('e.g. 2083 BS or 2083/84 BS') }}">
                                            <span class="form-text text-muted font-size-xs">{{ __('The institutional identifier used across admissions, fee invoices, and marks reports.') }}</span>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label class="font-weight-semibold">{{ __('Academic Year (BS)') }}: <span class="text-danger">*</span></label>
                                            <input name="year_bs" value="{{ old('year_bs', 2083) }}" required type="number" min="2000" max="2090" class="form-control" placeholder="2083">
                                            <span class="form-text text-muted font-size-xs">{{ __('Four-digit Bikram Sambat year (e.g. 2083).') }}</span>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 form-group mb-3">
                                                <label class="font-weight-semibold">{{ __('Start Date (BS)') }}: <span class="text-danger">*</span></label>
                                                <input name="start_date_bs" value="{{ old('start_date_bs', '2083-01-01') }}" required type="text" class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                                                <span class="form-text text-muted font-size-xs">{{ __('Typically Baishakh 1 (e.g. 2083-01-01).') }}</span>
                                            </div>

                                            <div class="col-md-6 form-group mb-3">
                                                <label class="font-weight-semibold">{{ __('End Date (BS)') }}: <span class="text-danger">*</span></label>
                                                <input name="end_date_bs" value="{{ old('end_date_bs', '2083-12-30') }}" required type="text" class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                                                <span class="form-text text-muted font-size-xs">{{ __('Typically Chaitra end (e.g. 2083-12-30).') }}</span>
                                            </div>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label class="font-weight-semibold">{{ __('Description / Notes') }}:</label>
                                            <textarea name="description" class="form-control" rows="2" placeholder="{{ __('Optional guidelines or notes regarding this session') }}">{{ old('description') }}</textarea>
                                        </div>

                                        <div class="form-group mb-4">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="check_is_active" checked>
                                                <label class="custom-control-label font-weight-semibold text-dark" for="check_is_active">
                                                    {{ __('Set as Active Session immediately for admissions, marksheets, and fee billing') }}
                                                </label>
                                            </div>
                                        </div>

                                        <div class="text-right">
                                            <button type="submit" class="btn btn-primary font-weight-semibold px-4 py-2 shadow-sm" style="border-radius: 8px;">
                                                <i class="icon-paperplane mr-1"></i>{{ __('Create Academic Session') }}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
