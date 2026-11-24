@extends('layouts.master')
@section('page_title', __('Manage Examinations'))
@section('content')

    {{-- Executive Header --}}
    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center">
                    <div class="d-inline-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px; border-radius: 14px; background: rgba(255, 87, 0, 0.12);">
                        <i class="icon-pencil7 icon-2x" style="color: #FF5700;"></i>
                    </div>
                    <div>
                        <h3 class="font-weight-bold mb-1 text-dark" style="font-size: 1.35rem; letter-spacing: -0.01em;">
                            {{ __('Manage Examinations & Evaluations') }}
                        </h3>
                        <p class="text-muted mb-0 font-size-sm">
                            {{ __('Terminal evaluations, Bikram Sambat schedules, result publication & academic assessments') }}
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
                    <a href="{{ route('marks.index') }}" class="btn btn-outline-primary btn-sm font-weight-semibold shadow-sm ml-md-2" style="border-radius: 8px;">
                        <i class="icon-calculator mr-1"></i>{{ __('Manage Marks') }}
                    </a>
                    <button type="button" class="btn btn-primary btn-sm font-weight-semibold shadow-sm ml-1" onclick="$('#tab-new-exam-link').tab('show');" style="border-radius: 8px;">
                        <i class="icon-plus2 mr-1"></i>{{ __('Create Examination') }}
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Total Examinations') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($total_exams ?? $exams->count()) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Evaluations created') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(79, 70, 229, 0.1);">
                            <i class="icon-pencil7 icon-2x" style="color: #4f46e5;"></i>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Published Results') }}</span>
                            <h3 class="font-weight-bold text-success mb-0 mt-1">{{ number_format($published_exams ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Visible on report cards') }}</span>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Active & Scheduled') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($ongoing_exams ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Ongoing / upcoming') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(6, 182, 212, 0.1);">
                            <i class="icon-calendar2 icon-2x" style="color: #06b6d4;"></i>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Current Session') }}</span>
                            <h3 class="font-weight-bold text-warning mb-0 mt-1">{{ number_format($session_exams ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('In active school year') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1);">
                            <i class="icon-graduation icon-2x text-warning"></i>
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
                    <a href="#all-exams" id="tab-all-exams-link" class="nav-link active font-weight-semibold" data-toggle="tab">
                        <i class="icon-list mr-1"></i> {{ __('All Examinations') }}
                        <span class="badge badge-primary badge-pill ml-1">{{ $exams->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#new-exam" id="tab-new-exam-link" class="nav-link font-weight-semibold" data-toggle="tab">
                        <i class="icon-plus2 mr-1"></i> {{ __('Create Examination') }}
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body p-3 p-md-4">
            <div class="tab-content">
                {{-- TAB 1: All Examinations Table --}}
                <div class="tab-pane fade show active" id="all-exams">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped datatable-button-html5-columns">
                            <thead class="thead-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>{{ __('Examination Title') }}</th>
                                <th>{{ __('Evaluation Type') }}</th>
                                <th>{{ __('Academic Term') }}</th>
                                <th>{{ __('Session') }}</th>
                                <th>{{ __('Schedule (BS)') }}</th>
                                <th>{{ __('Result Status') }}</th>
                                <th class="text-center" style="width: 120px;">{{ __('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($exams as $ex)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="d-inline-flex align-items-center justify-content-center mr-2 text-primary bg-light rounded" style="width: 34px; height: 34px;">
                                                <i class="icon-pencil7"></i>
                                            </div>
                                            <div>
                                                <span class="font-weight-bold text-dark font-size-sm">{{ $ex->name }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light border text-dark font-weight-semibold py-1 px-2" style="border-radius: 6px;">
                                            {{ ucwords(str_replace('_', ' ', $ex->exam_type ?? 'terminal')) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary py-1 px-2 font-weight-semibold">
                                            {{ __('Term') }} {{ $ex->term }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted font-weight-semibold">{{ $ex->year }}</span>
                                    </td>
                                    <td>
                                        @if($ex->start_date_bs)
                                            <span class="badge badge-light border text-dark py-1 px-2 font-weight-semibold">
                                                <i class="icon-calendar22 mr-1 text-primary"></i>
                                                {{ $ex->start_date_bs }} @if($ex->end_date_bs && $ex->end_date_bs !== $ex->start_date_bs) &ndash; {{ $ex->end_date_bs }} @endif BS
                                            </span>
                                        @else
                                            <span class="text-muted font-italic font-size-xs">&mdash;</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(($ex->status ?? 'published') === 'published')
                                            <span class="badge badge-success py-1 px-2 font-weight-semibold shadow-sm" style="border-radius: 6px;">
                                                <i class="icon-checkmark-circle mr-1"></i>{{ __('Published') }}
                                            </span>
                                        @elseif($ex->status === 'completed')
                                            <span class="badge badge-info py-1 px-2 font-weight-semibold shadow-sm" style="border-radius: 6px;">
                                                <i class="icon-check mr-1"></i>{{ __('Completed') }}
                                            </span>
                                        @elseif($ex->status === 'ongoing')
                                            <span class="badge badge-warning py-1 px-2 font-weight-semibold shadow-sm text-white" style="border-radius: 6px;">
                                                <i class="icon-spinner4 spinner mr-1"></i>{{ __('Ongoing') }}
                                            </span>
                                        @elseif($ex->status === 'scheduled')
                                            <span class="badge badge-light border text-primary py-1 px-2 font-weight-semibold" style="border-radius: 6px;">
                                                <i class="icon-calendar mr-1"></i>{{ __('Scheduled') }}
                                            </span>
                                        @else
                                            <span class="badge badge-secondary py-1 px-2 font-weight-semibold" style="border-radius: 6px;">
                                                {{ ucfirst($ex->status ?? 'Draft') }}
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
                                                    @if(Qs::userIsTeamSA())
                                                        <a href="{{ route('exams.edit', $ex->id) }}" class="dropdown-item">
                                                            <i class="icon-pencil text-primary"></i> {{ __('Edit Examination') }}
                                                        </a>

                                                        <a href="{{ route('marks.index') }}" class="dropdown-item">
                                                            <i class="icon-calculator text-info"></i> {{ __('Enter / Manage Marks') }}
                                                        </a>

                                                        <div class="dropdown-divider"></div>

                                                        @if(($ex->status ?? 'published') !== 'published')
                                                            <form method="post" action="{{ route('exams.publish', $ex->id) }}">
                                                                @csrf @method('put')
                                                                <button type="submit" class="dropdown-item text-success">
                                                                    <i class="icon-checkmark"></i> {{ __('Publish Results') }}
                                                                </button>
                                                            </form>
                                                        @else
                                                            <form method="post" action="{{ route('exams.unpublish', $ex->id) }}">
                                                                @csrf @method('put')
                                                                <button type="submit" class="dropdown-item text-warning">
                                                                    <i class="icon-eye-blocked"></i> {{ __('Unpublish Results') }}
                                                                </button>
                                                            </form>
                                                        @endif
                                                    @endif

                                                    @if(Qs::userIsSuperAdmin())
                                                        <div class="dropdown-divider"></div>
                                                        <form method="post" action="{{ route('exams.destroy', $ex->id) }}" onsubmit="return confirm('{{ __('Are you sure you want to delete this examination?') }}');">
                                                            @csrf @method('delete')
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="icon-trash"></i> {{ __('Delete Examination') }}
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="icon-pencil7 icon-3x text-muted mb-2"></i>
                                            <h6 class="font-weight-bold text-dark">{{ __('No Examinations Found') }}</h6>
                                            <p class="font-size-sm mb-3 text-muted">{{ __('Create a terminal or annual examination to evaluate academic progress.') }}</p>
                                            <button type="button" class="btn btn-primary btn-sm" onclick="$('#tab-new-exam-link').tab('show');">
                                                <i class="icon-plus2 mr-1"></i>{{ __('Create Examination') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB 2: Create Examination Form --}}
                <div class="tab-pane fade" id="new-exam">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="alert alert-info border-0 shadow-sm mb-4" style="border-radius: 12px;">
                                <div class="d-flex align-items-center">
                                    <i class="icon-info22 icon-2x text-info mr-3"></i>
                                    <div>
                                        <h6 class="font-weight-bold mb-1 text-dark">{{ __('Target Academic Session') }}: {{ Qs::getCurrentSession() }}</h6>
                                        <p class="mb-0 font-size-sm text-dark">
                                            {{ __('Configuring evaluation for the current active session. Bikram Sambat start and end dates automatically link to the academic calendar feed.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="card border shadow-none" style="border-radius: 14px;">
                                <div class="card-header bg-light py-3" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                                    <h5 class="font-weight-bold mb-0 text-dark">
                                        <i class="icon-plus2 mr-2" style="color: #FF5700;"></i>{{ __('Configure New Examination') }}
                                    </h5>
                                    <span class="text-muted font-size-xs">{{ __('Provide assessment title, terminal cycle, Bikram Sambat schedule, and publication status.') }}</span>
                                </div>

                                <div class="card-body p-4">
                                    <form method="post" action="{{ route('exams.store') }}">
                                        @csrf
                                        <div class="form-group mb-3">
                                            <label class="font-weight-semibold">{{ __('Examination Name') }}: <span class="text-danger">*</span></label>
                                            <input name="name" value="{{ old('name') }}" required type="text" class="form-control" placeholder="{{ __('e.g. First Terminal Examination 2083') }}">
                                            <span class="form-text text-muted font-size-xs">{{ __('Official title displayed on marksheets, report cards, and student grade profiles.') }}</span>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 form-group mb-3">
                                                <label class="font-weight-semibold">{{ __('Evaluation Type') }}: <span class="text-danger">*</span></label>
                                                <select class="form-control select" name="exam_type">
                                                    <option value="first_terminal">{{ __('First Terminal Examination (प्रथम त्रैमासिक)') }}</option>
                                                    <option value="second_terminal">{{ __('Second Terminal Examination (दोस्रो त्रैमासिक)') }}</option>
                                                    <option value="third_terminal">{{ __('Third Terminal Examination (तेस्रो त्रैमासिक)') }}</option>
                                                    <option value="mid_term">{{ __('Mid-Term Examination (मध्यसत्र)') }}</option>
                                                    <option value="annual">{{ __('Annual Examination (वार्षिक परीक्षा)') }}</option>
                                                    <option value="unit_test">{{ __('Unit Test / Continuous Assessment (एकाइ परीक्षा)') }}</option>
                                                    <option value="practical">{{ __('Practical / Project Evaluation (प्रयोगात्मक परीक्षा)') }}</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6 form-group mb-3">
                                                <label for="term" class="font-weight-semibold">{{ __('Academic Term') }}: <span class="text-danger">*</span></label>
                                                <select class="form-control select" name="term" id="term" required>
                                                    <option {{ old('term') == 1 ? 'selected' : '' }} value="1">{{ __('Term 1 (प्रथम सत्र)') }}</option>
                                                    <option {{ old('term') == 2 ? 'selected' : '' }} value="2">{{ __('Term 2 (दोस्रो सत्र)') }}</option>
                                                    <option {{ old('term') == 3 ? 'selected' : '' }} value="3">{{ __('Term 3 (तेस्रो सत्र)') }}</option>
                                                    <option {{ old('term') == 4 ? 'selected' : '' }} value="4">{{ __('Term 4 (चौथो सत्र)') }}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 form-group mb-3">
                                                <label class="font-weight-semibold">{{ __('Start Date (Bikram Sambat)') }}:</label>
                                                <input name="start_date_bs" value="{{ old('start_date_bs') }}" type="text" class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                                                <span class="form-text text-muted font-size-xs">{{ __('e.g. 2083-05-15') }}</span>
                                            </div>

                                            <div class="col-md-6 form-group mb-3">
                                                <label class="font-weight-semibold">{{ __('End Date (Bikram Sambat)') }}:</label>
                                                <input name="end_date_bs" value="{{ old('end_date_bs') }}" type="text" class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                                                <span class="form-text text-muted font-size-xs">{{ __('e.g. 2083-05-25') }}</span>
                                            </div>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label class="font-weight-semibold">{{ __('Initial Publication Status') }}:</label>
                                            <select class="form-control select" name="status">
                                                <option value="draft">{{ __('Draft (मस्यौदा) — Hidden from students') }}</option>
                                                <option value="scheduled">{{ __('Scheduled (तालिकाबद्ध) — Calendar visible') }}</option>
                                                <option value="ongoing">{{ __('Ongoing (सञ्चालनमा) — Exams in progress') }}</option>
                                                <option value="completed">{{ __('Completed (सम्पन्न) — Marks entry active') }}</option>
                                                <option value="published" selected>{{ __('Published (प्रकाशित) — Results visible to parents & pupils') }}</option>
                                            </select>
                                        </div>

                                        <div class="form-group mb-4">
                                            <label class="font-weight-semibold">{{ __('Description / Guidelines') }}:</label>
                                            <textarea name="description" class="form-control" rows="2" placeholder="{{ __('Optional exam guidelines, instructions, or evaluation criteria') }}">{{ old('description') }}</textarea>
                                        </div>

                                        <div class="text-right">
                                            <button type="submit" class="btn btn-primary font-weight-semibold px-4 py-2 shadow-sm" style="border-radius: 8px;">
                                                <i class="icon-paperplane mr-1"></i>{{ __('Create Examination') }}
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
