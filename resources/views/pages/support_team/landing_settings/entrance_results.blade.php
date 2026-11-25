@extends('layouts.master')
@section('page_title', __('Entrance Exam Results Management'))

@section('content')

    {{-- Executive Header --}}
    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center">
                    <div class="d-inline-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px; border-radius: 14px; background: rgba(255, 87, 0, 0.12);">
                        <i class="icon-file-check icon-2x" style="color: #FF5700;"></i>
                    </div>
                    <div>
                        <h3 class="font-weight-bold mb-1 text-dark" style="font-size: 1.35rem; letter-spacing: -0.01em;">
                            {{ __('Entrance Exam Results Management') }}
                        </h3>
                        <p class="text-muted mb-0 font-size-sm">
                            {{ __('Bulk CDC 4.0 GPA entrance result imports, candidate evaluation records & public portal publishing') }}
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
                    <button type="button" class="btn text-white btn-sm font-weight-semibold shadow-sm ml-md-2" data-toggle="modal" data-target="#uploadCsvModal" style="background: #FF5700; border-radius: 8px;">
                        <i class="icon-upload mr-1"></i> {{ __('Bulk Import CSV') }}
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Total Candidates') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($total_candidates ?? $results->total()) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Evaluated applicants') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(79, 70, 229, 0.1);">
                            <i class="icon-users icon-2x" style="color: #4f46e5;"></i>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Qualified (>= 1.6 GPA)') }}</span>
                            <h3 class="font-weight-bold text-success mb-0 mt-1">{{ number_format($passed_count ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Eligible for admission') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16, 185, 129, 0.1);">
                            <i class="icon-checkmark4 icon-2x" style="color: #10b981;"></i>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Published Batches') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($batches_count ?? count($batches)) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Academic cycles') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(6, 182, 212, 0.1);">
                            <i class="icon-calendar52 icon-2x" style="color: #06b6d4;"></i>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Grade Levels') }}</span>
                            <h3 class="font-weight-bold text-warning mb-0 mt-1">{{ number_format($classes_count ?? count($classes)) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Tested entry grades') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1);">
                            <i class="icon-office icon-2x" style="color: #f59e0b;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Subnav --}}
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
        <div class="card-body p-2 p-md-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ route('landing_settings.index') }}" class="btn btn-light border btn-sm mr-2 font-weight-semibold" style="border-radius: 8px;">
                    <i class="icon-gear mr-1"></i> {{ __('General & Admissions Controls') }}
                </a>
                <a href="{{ route('landing_settings.inquiries') }}" class="btn btn-light border btn-sm mr-2 font-weight-semibold" style="border-radius: 8px;">
                    <i class="icon-bubbles4 mr-1 text-info"></i> {{ __('View Inquiries') }}
                </a>
                <a href="{{ route('landing_settings.entrance_results') }}" class="btn text-white btn-sm font-weight-semibold" style="background: #FF5700; border-radius: 8px;">
                    <i class="icon-file-text2 mr-1"></i> {{ __('Manage Entrance Exam Results') }}
                    <span class="badge badge-light text-dark ml-1">{{ $results->total() }}</span>
                </a>
            </div>
            <div>
                <a href="{{ route('admissions.results') }}" target="_blank" class="btn btn-outline-dark btn-sm font-weight-semibold" style="border-radius: 8px;">
                    <i class="icon-eye mr-1"></i> {{ __('View Public Results Portal') }} ↗
                </a>
            </div>
        </div>
    </div>

    {{-- Filter & Search Card --}}
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
        <div class="card-body p-3 p-md-4">
            <form method="get" action="{{ route('landing_settings.entrance_results') }}" class="row align-items-end">
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="font-weight-semibold font-size-xs text-uppercase text-muted mb-1">{{ __('Batch Year') }}</label>
                    <select name="batch_year" class="form-control form-control-sm select">
                        <option value="">{{ __('All Batches') }}</option>
                        @foreach($batches as $b)
                            <option value="{{ $b }}" {{ $batch === $b ? 'selected' : '' }}>{{ $b }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="font-weight-semibold font-size-xs text-uppercase text-muted mb-1">{{ __('Class / Grade') }}</label>
                    <select name="class_name" class="form-control form-control-sm select">
                        <option value="">{{ __('All Classes') }}</option>
                        @foreach($classes as $c)
                            <option value="{{ $c }}" {{ $class === $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 mb-2 mb-md-0">
                    <label class="font-weight-semibold font-size-xs text-uppercase text-muted mb-1">{{ __('Search Symbol / Name') }}</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="e.g. 1001 or Pooja">
                </div>

                <div class="col-md-2 d-flex">
                    <button type="submit" class="btn btn-primary btn-sm btn-block font-weight-semibold shadow-sm mr-1">
                        <i class="icon-filter3 mr-1"></i> {{ __('Filter') }}
                    </button>
                    <a href="{{ route('landing_settings.entrance_results') }}" class="btn btn-light btn-sm" title="{{ __('Reset') }}">
                        <i class="icon-reset"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Results Table --}}
    <div class="card shadow-sm border-0" style="border-radius: 14px;">
        <div class="card-header d-flex align-items-center justify-content-between bg-white border-bottom py-3">
            <h5 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.05rem;">
                <i class="icon-file-check mr-2" style="color: #FF5700;"></i> {{ __('Entrance Examination Candidates') }}
                <span class="badge badge-secondary ml-1 font-size-xs">{{ $results->total() }}</span>
            </h5>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body p-3 p-md-4">
            @if(count($results) > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>{{ __('Batch') }}</th>
                                <th>{{ __('Symbol No') }}</th>
                                <th>{{ __('Candidate Name') }}</th>
                                <th>{{ __('Class / Grade') }}</th>
                                <th>{{ __('Section') }}</th>
                                <th>{{ __('GPA (4.0 Scale)') }}</th>
                                <th>{{ __('Remarks') }}</th>
                                <th class="text-center" style="width: 90px;">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $res)
                                <tr>
                                    <td>{{ $results->firstItem() + $loop->index }}</td>
                                    <td><span class="badge badge-light border font-weight-semibold">{{ $res->batch_year }}</span></td>
                                    <td><code class="font-weight-bold text-dark">{{ $res->symbol_no }}</code></td>
                                    <td class="font-weight-semibold text-dark">{{ $res->student_name }}</td>
                                    <td>{{ $res->class_name }}</td>
                                    <td>{{ $res->section_name }}</td>
                                    <td>
                                        <span class="badge {{ $res->gpa >= 3.2 ? 'badge-success' : ($res->gpa >= 2.0 ? 'badge-primary' : 'badge-danger') }}" style="border-radius: 6px; padding: 4px 8px;">
                                            {{ number_format((float)$res->gpa, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="font-size-xs font-weight-semibold {{ $res->gpa >= 1.6 ? 'text-success' : 'text-danger' }}">
                                            {{ $res->remarks }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <form method="post" action="{{ route('landing_settings.entrance_results.delete', $res->id) }}" onsubmit="return confirm('Delete entrance evaluation for {{ addslashes($res->student_name) }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius: 6px;" title="{{ __('Delete Candidate Record') }}">
                                                <i class="icon-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <div class="text-muted font-size-sm">
                        {{ __('Showing :from to :to of :total candidates', [
                            'from' => $results->firstItem() ?? 0,
                            'to' => $results->lastItem() ?? 0,
                            'total' => $results->total()
                        ]) }}
                    </div>
                    <div>
                        {{ $results->appends(request()->query())->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-5 text-muted">
                    <i class="icon-file-text2 icon-2x mb-2 d-block text-muted"></i>
                    <span class="font-weight-medium">{{ __('No entrance examination records found matching criteria.') }}</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Upload CSV Modal --}}
    <div id="uploadCsvModal" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header d-flex align-items-center justify-content-between" style="background: #111827; color: #FFFFFF;">
                    <h6 class="modal-title font-weight-bold mb-0 text-white"><i class="icon-upload mr-2 text-success"></i>{{ __('Bulk Import Entrance Exam Results (CSV)') }}</h6>
                    <button type="button" class="close text-white" data-dismiss="modal" style="opacity: 0.8;">&times;</button>
                </div>
                <form method="post" action="{{ route('landing_settings.entrance_results.upload') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="alert alert-info border-0 font-size-xs">
                            <strong>{{ __('Expected CSV Format') }}:</strong>
                            <p class="mb-0">{{ __('Header Row: Symbol No, Roll No, Student Name, Section, Attendance, Subject1, Subject2, Subject3...') }}</p>
                            <p class="mb-0 mt-1">{{ __('Grades (A+, A, B+, etc.) and Grade Points (GP) will be automatically computed using the Nepal National CDC 4.0 scale.') }}</p>
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-semibold text-dark">{{ __('Target Academic Batch') }} <span class="text-danger">*</span></label>
                            <input type="text" name="batch_year" value="2083 B.S." class="form-control" required placeholder="e.g. 2083 B.S.">
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-semibold text-dark">{{ __('Examination Title') }} <span class="text-danger">*</span></label>
                            <input type="text" name="exam_name" value="Entrance & Admission Examination 2083" class="form-control" required>
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-semibold text-dark">{{ __('Grade / Class') }} <span class="text-danger">*</span></label>
                            <input type="text" name="class_name" value="Grade: 1" class="form-control" required placeholder="e.g. Grade: 1 or Grade: 11 Science">
                        </div>

                        <div class="form-group mb-0">
                            <label class="font-weight-semibold text-dark">{{ __('Select CSV File') }} <span class="text-danger">*</span></label>
                            <input type="file" name="csv_file" class="form-control-file" accept=".csv,text/csv" required>
                        </div>
                    </div>

                    <div class="modal-footer bg-light p-3">
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn text-white btn-sm font-weight-semibold shadow-sm" style="background: #FF5700; border-radius: 8px;">
                            <i class="icon-upload mr-1"></i> {{ __('Import & Publish Results') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
