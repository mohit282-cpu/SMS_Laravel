@extends('layouts.master')
@section('page_title', __('Manage TimeTables'))
@section('content')

    {{-- Executive Header with Dual Date & Academic Session --}}
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-3 border-bottom">
        <div>
            <div class="d-flex flex-wrap align-items-center mb-1 gap-2">
                <span class="badge badge-primary mr-2">
                    <i class="icon-calendar52 mr-1" aria-hidden="true"></i> {{ __('Timetable Management') }}
                </span>
                <span class="badge badge-light border text-dark mr-2">
                    <i class="icon-calendar22 mr-1 text-primary" aria-hidden="true"></i> {{ \App\Helpers\NepaliHelper::formatDualDate(date('Y-m-d')) }}
                </span>
                <span class="badge badge-light border text-muted">
                    <i class="icon-graduation2 mr-1 text-secondary" aria-hidden="true"></i> {{ __('Session') }}: <strong>{{ Qs::getCurrentSession() }}</strong>
                </span>
            </div>
            <h2 class="font-weight-bold text-dark mb-0" style="font-size: 1.35rem;">{{ __('Manage TimeTables & Schedules') }}</h2>
        </div>
        <div class="d-flex align-items-center mt-3 mt-md-0" style="gap: 0.5rem;">
            @if(Qs::userIsTeacher())
                <a href="{{ route('tt.my_timetable') }}" class="btn btn-sm btn-outline-primary">
                    <i class="icon-user-check mr-1" aria-hidden="true"></i> {{ __('My Teaching Schedule') }}
                </a>
            @endif
            @if(Qs::userIsTeamSA())
                <a href="#add-tt" data-toggle="tab" class="btn btn-sm text-white" style="background: #FF5700; border-radius: 8px;">
                    <i class="icon-plus2 mr-1" aria-hidden="true"></i> {{ __('New Timetable') }}
                </a>
            @endif
        </div>
    </div>

    {{-- Metric Stat Cards Overview --}}
    <div class="row">
        <div class="col-sm-6 col-xl-3">
            <div class="sms-stat-card">
                <div>
                    <div class="stat-number">{{ Qs::formatNumber($tt_records->count()) }}</div>
                    <div class="stat-label">{{ __('Total Timetables') }}</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-navy">
                    <i class="icon-calendar52" aria-hidden="true"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="sms-stat-card">
                <div>
                    <div class="stat-number">{{ Qs::formatNumber($tt_records->whereNull('exam_id')->count()) }}</div>
                    <div class="stat-label">{{ __('Class Schedules') }}</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-orange">
                    <i class="icon-calendar" aria-hidden="true"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="sms-stat-card">
                <div>
                    <div class="stat-number">{{ Qs::formatNumber($tt_records->whereNotNull('exam_id')->count()) }}</div>
                    <div class="stat-label">{{ __('Exam Routines') }}</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-rose">
                    <i class="icon-file-text" aria-hidden="true"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="sms-stat-card">
                <div>
                    <div class="stat-number">{{ Qs::formatNumber(count($my_classes ?? [])) }}</div>
                    <div class="stat-label">{{ __('Classes Configured') }}</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-emerald">
                    <i class="icon-windows2" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Timetable Content Card --}}
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between">
            <h5 class="font-weight-bold mb-0 text-dark">
                <i class="icon-calendar mr-2 text-primary" aria-hidden="true"></i>{{ __('Timetable Records & Class Routines') }}
            </h5>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <ul class="nav nav-tabs nav-tabs-highlight" id="tt-tabs" role="tablist">
                {{-- Tab 1: All Timetables Overview --}}
                <li class="nav-item">
                    <a href="#all-tt" class="nav-link active font-weight-semibold" data-toggle="tab" role="tab">
                        <i class="icon-list mr-1" aria-hidden="true"></i> {{ __('All Timetables') }}
                        <span class="badge badge-primary ml-1">{{ $tt_records->count() }}</span>
                    </a>
                </li>

                {{-- Tab 2: Create Timetable (TeamSA only) --}}
                @if(Qs::userIsTeamSA())
                    <li class="nav-item">
                        <a href="#add-tt" class="nav-link font-weight-semibold" data-toggle="tab" role="tab">
                            <i class="icon-plus2 mr-1" aria-hidden="true"></i> {{ __('Create Timetable') }}
                        </a>
                    </li>
                @endif

                {{-- Tab 3: Filter by Class Dropdown --}}
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle font-weight-semibold" data-toggle="dropdown" data-default="Filter By Class">
                        <i class="icon-filter3 mr-1" aria-hidden="true"></i> <span class="selected-text">{{ __('Filter By Class') }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        @foreach($my_classes as $mc)
                            <a href="#ttr{{ $mc->id }}" class="dropdown-item" data-toggle="tab">
                                <i class="icon-windows2 mr-2 text-muted" aria-hidden="true"></i>{{ $mc->name }}
                                <span class="badge badge-light border ml-auto">{{ $tt_records->where('my_class_id', $mc->id)->count() }}</span>
                            </a>
                        @endforeach
                    </div>
                </li>
            </ul>

            <div class="tab-content pt-3">
                {{-- =========================================================
                     TAB 1: ALL TIMETABLES OVERVIEW
                     ========================================================= --}}
                <div class="tab-pane fade show active" id="all-tt" role="tabpanel">
                    @if($tt_records->isEmpty())
                        <div class="text-center py-5">
                            <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px; border-radius: 50%; background: rgba(108, 117, 125, 0.1);">
                                <i class="icon-calendar3 icon-2x text-muted" aria-hidden="true"></i>
                            </div>
                            <h5 class="font-weight-bold text-dark mb-1">{{ __('No Timetables Created Yet') }}</h5>
                            <p class="text-muted max-w-500 mx-auto mb-3" style="max-width: 480px;">
                                {{ __('Get started by creating weekly class routines or examination schedules for your classes.') }}
                            </p>
                            @if(Qs::userIsTeamSA())
                                <a href="#add-tt" data-toggle="tab" class="btn text-white btn-sm" style="background: #FF5700;">
                                    <i class="icon-plus2 mr-1" aria-hidden="true"></i>{{ __('Create First Timetable') }}
                                </a>
                            @endif
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table datatable-button-html5-columns table-hover table-striped">
                                <thead>
                                <tr>
                                    <th style="width: 60px;">#</th>
                                    <th>{{ __('Timetable Name') }}</th>
                                    <th>{{ __('Class') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Academic Session') }}</th>
                                    <th class="text-center" style="width: 140px;">{{ __('Action') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($tt_records as $ttr)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <a href="{{ route('ttr.show', $ttr->id) }}" class="font-weight-semibold text-dark">
                                                <i class="icon-calendar mr-1 text-primary" aria-hidden="true"></i>{{ $ttr->name }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge badge-light border text-dark font-weight-medium">
                                                <i class="icon-windows2 mr-1 text-info" aria-hidden="true"></i>{{ $ttr->my_class->name ?? '-' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($ttr->exam_id)
                                                <span class="badge badge-warning text-dark font-weight-semibold">
                                                    <i class="icon-file-text mr-1" aria-hidden="true"></i>{{ $ttr->exam->name ?? __('Exam Routine') }}
                                                </span>
                                            @else
                                                <span class="badge badge-success font-weight-semibold">
                                                    <i class="icon-calendar mr-1" aria-hidden="true"></i>{{ __('Class Timetable') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-light border font-weight-normal">{{ $ttr->year }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="list-icons">
                                                <div class="dropdown">
                                                    <a href="#" class="list-icons-item" data-toggle="dropdown" aria-label="{{ __('Options') }}">
                                                        <i class="icon-menu9"></i>
                                                    </a>
                                                    <div class="dropdown-menu dropdown-menu-right">
                                                        {{-- View --}}
                                                        <a href="{{ route('ttr.show', $ttr->id) }}" class="dropdown-item">
                                                            <i class="icon-eye text-primary mr-2" aria-hidden="true"></i>{{ __('View Timetable') }}
                                                        </a>

                                                        {{-- Print --}}
                                                        <a target="_blank" href="{{ route('ttr.print', $ttr->id) }}" class="dropdown-item">
                                                            <i class="icon-printer text-secondary mr-2" aria-hidden="true"></i>{{ __('Print Routine') }}
                                                        </a>

                                                        @if(Qs::userIsTeamSA())
                                                            {{-- Manage Slots & Subjects --}}
                                                            <a href="{{ route('ttr.manage', $ttr->id) }}" class="dropdown-item">
                                                                <i class="icon-plus-circle2 text-success mr-2" aria-hidden="true"></i>{{ __('Manage Slots & Subjects') }}
                                                            </a>

                                                            {{-- Edit --}}
                                                            <a href="{{ route('ttr.edit', $ttr->id) }}" class="dropdown-item">
                                                                <i class="icon-pencil text-warning mr-2" aria-hidden="true"></i>{{ __('Edit Details') }}
                                                            </a>
                                                        @endif

                                                        {{-- Delete --}}
                                                        @if(Qs::userIsSuperAdmin())
                                                            <div class="dropdown-divider"></div>
                                                            <a id="{{ $ttr->id }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item text-danger">
                                                                <i class="icon-trash text-danger mr-2" aria-hidden="true"></i>{{ __('Delete') }}
                                                            </a>
                                                            <form method="post" id="item-delete-{{ $ttr->id }}" action="{{ route('ttr.destroy', $ttr->id) }}" class="d-none">
                                                                @csrf @method('delete')
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- =========================================================
                     TAB 2: CREATE TIMETABLE (TeamSA only)
                     ========================================================= --}}
                @if(Qs::userIsTeamSA())
                    <div class="tab-pane fade" id="add-tt" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="card border shadow-none" style="border-radius: 12px; background: #fafafa;">
                                    <div class="card-body p-4">
                                        <div class="mb-4 pb-2 border-bottom">
                                            <h5 class="font-weight-bold text-dark mb-1">
                                                <i class="icon-plus-circle2 mr-2 text-primary" aria-hidden="true"></i>{{ __('Create New Timetable') }}
                                            </h5>
                                            <p class="text-muted font-size-sm mb-0">
                                                {{ __('Configure the basic timetable structure. Once created, you will configure time slots and assign subjects.') }}
                                            </p>
                                        </div>

                                        <form class="ajax-store" method="post" action="{{ route('ttr.store') }}">
                                            @csrf
                                            <div class="form-group row align-items-center">
                                                <label class="col-lg-3 col-form-label font-weight-semibold">
                                                    {{ __('Timetable Name') }} <span class="text-danger">*</span>
                                                </label>
                                                <div class="col-lg-9">
                                                    <input name="name" value="{{ old('name') }}" required type="text" class="form-control" placeholder="{{ __('e.g. Grade 10 Regular Schedule or First Term Routine') }}">
                                                </div>
                                            </div>

                                            <div class="form-group row align-items-center">
                                                <label for="my_class_id" class="col-lg-3 col-form-label font-weight-semibold">
                                                    {{ __('Target Class') }} <span class="text-danger">*</span>
                                                </label>
                                                <div class="col-lg-9">
                                                    <select required data-placeholder="{{ __('Select Class') }}" class="form-control select" name="my_class_id" id="my_class_id">
                                                        <option value=""></option>
                                                        @foreach($my_classes as $mc)
                                                            <option {{ old('my_class_id') == $mc->id ? 'selected' : '' }} value="{{ $mc->id }}">{{ $mc->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-group row align-items-center">
                                                <label for="exam_id" class="col-lg-3 col-form-label font-weight-semibold">
                                                    {{ __('Schedule Type') }}
                                                </label>
                                                <div class="col-lg-9">
                                                    <select class="select form-control" name="exam_id" id="exam_id">
                                                        <option value="">{{ __('Regular Class Timetable (Weekly Days)') }}</option>
                                                        @foreach($exams as $ex)
                                                            <option {{ old('exam_id') == $ex->id ? 'selected' : '' }} value="{{ $ex->id }}">
                                                                {{ __('Exam: :name', ['name' => $ex->name]) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <small class="form-text text-muted">
                                                        {{ __('Select "Regular Class Timetable" for standard weekly lessons (Sun–Fri), or select a published examination for exam routines.') }}
                                                    </small>
                                                </div>
                                            </div>

                                            <div class="text-right pt-3 border-top mt-4">
                                                <button id="ajax-btn" type="submit" class="btn btn-primary px-4 font-weight-semibold">
                                                    {{ __('Create Timetable') }} <i class="icon-paperplane ml-2" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="card border shadow-none" style="border-radius: 12px; background: #F8FAFC;">
                                    <div class="card-body p-3">
                                        <h6 class="font-weight-bold text-dark mb-2">
                                            <i class="icon-info22 mr-2 text-info" aria-hidden="true"></i>{{ __('Timetable Workflow') }}
                                        </h6>
                                        <ol class="text-muted font-size-sm pl-3 mb-0" style="line-height: 1.8;">
                                            <li><strong>{{ __('Create Header') }}:</strong> {{ __('Provide name, target class, and choose class vs exam type.') }}</li>
                                            <li><strong>{{ __('Add Time Slots') }}:</strong> {{ __('Define period timings (e.g. 10:00 AM – 10:45 AM).') }}</li>
                                            <li><strong>{{ __('Assign Subjects') }}:</strong> {{ __('Assign subject teachers to slots for each day.') }}</li>
                                            <li><strong>{{ __('Publish & Print') }}:</strong> {{ __('Timetables are instantly available to students, parents, and teachers.') }}</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- =========================================================
                     TAB 3: PER-CLASS TIMETABLE PANES
                     ========================================================= --}}
                @foreach($my_classes as $mc)
                    <div class="tab-pane fade" id="ttr{{ $mc->id }}" role="tabpanel">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <div>
                                <h6 class="font-weight-bold text-dark mb-0">
                                    <i class="icon-windows2 mr-2 text-primary" aria-hidden="true"></i>{{ __('Timetables for :class', ['class' => $mc->name]) }}
                                </h6>
                                <span class="text-muted font-size-xs">{{ __('Class-specific schedule records') }}</span>
                            </div>
                            @if(Qs::userIsTeamSA())
                                <a href="#add-tt" data-toggle="tab" class="btn btn-sm btn-outline-primary">
                                    <i class="icon-plus2 mr-1" aria-hidden="true"></i>{{ __('Add for :class', ['class' => $mc->name]) }}
                                </a>
                            @endif
                        </div>

                        @php $class_records = $tt_records->where('my_class_id', $mc->id); @endphp
                        @if($class_records->isEmpty())
                            <div class="text-center py-4">
                                <i class="icon-calendar-empty icon-2x text-muted mb-2 d-block" aria-hidden="true"></i>
                                <p class="text-muted font-size-sm mb-2">{{ __('No timetables found for :class.', ['class' => $mc->name]) }}</p>
                                @if(Qs::userIsTeamSA())
                                    <a href="#add-tt" data-toggle="tab" class="btn btn-sm text-white" style="background: #FF5700;">
                                        <i class="icon-plus2 mr-1" aria-hidden="true"></i>{{ __('Create Timetable') }}
                                    </a>
                                @endif
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table datatable-button-html5-columns table-hover table-striped">
                                    <thead>
                                    <tr>
                                        <th style="width: 60px;">#</th>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Class') }}</th>
                                        <th>{{ __('Type') }}</th>
                                        <th>{{ __('Year') }}</th>
                                        <th class="text-center" style="width: 140px;">{{ __('Action') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($class_records as $ttr)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <a href="{{ route('ttr.show', $ttr->id) }}" class="font-weight-semibold text-dark">
                                                    <i class="icon-calendar mr-1 text-primary" aria-hidden="true"></i>{{ $ttr->name }}
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge badge-light border text-dark">{{ $ttr->my_class->name ?? '-' }}</span>
                                            </td>
                                            <td>
                                                @if($ttr->exam_id)
                                                    <span class="badge badge-warning text-dark font-weight-semibold">
                                                        <i class="icon-file-text mr-1" aria-hidden="true"></i>{{ $ttr->exam->name ?? __('Exam Routine') }}
                                                    </span>
                                                @else
                                                    <span class="badge badge-success font-weight-semibold">
                                                        <i class="icon-calendar mr-1" aria-hidden="true"></i>{{ __('Class Timetable') }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-light border">{{ $ttr->year }}</span>
                                            </td>
                                            <td class="text-center">
                                                <div class="list-icons">
                                                    <div class="dropdown">
                                                        <a href="#" class="list-icons-item" data-toggle="dropdown" aria-label="{{ __('Options') }}">
                                                            <i class="icon-menu9"></i>
                                                        </a>
                                                        <div class="dropdown-menu dropdown-menu-right">
                                                            {{-- View --}}
                                                            <a href="{{ route('ttr.show', $ttr->id) }}" class="dropdown-item">
                                                                <i class="icon-eye text-primary mr-2" aria-hidden="true"></i>{{ __('View') }}
                                                            </a>

                                                            {{-- Print --}}
                                                            <a target="_blank" href="{{ route('ttr.print', $ttr->id) }}" class="dropdown-item">
                                                                <i class="icon-printer text-secondary mr-2" aria-hidden="true"></i>{{ __('Print') }}
                                                            </a>

                                                            @if(Qs::userIsTeamSA())
                                                                {{-- Manage --}}
                                                                <a href="{{ route('ttr.manage', $ttr->id) }}" class="dropdown-item">
                                                                    <i class="icon-plus-circle2 text-success mr-2" aria-hidden="true"></i>{{ __('Manage') }}
                                                                </a>

                                                                {{-- Edit --}}
                                                                <a href="{{ route('ttr.edit', $ttr->id) }}" class="dropdown-item">
                                                                    <i class="icon-pencil text-warning mr-2" aria-hidden="true"></i>{{ __('Edit') }}
                                                                </a>
                                                            @endif

                                                            {{-- Delete --}}
                                                            @if(Qs::userIsSuperAdmin())
                                                                <div class="dropdown-divider"></div>
                                                                <a id="{{ $ttr->id }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item text-danger">
                                                                    <i class="icon-trash text-danger mr-2" aria-hidden="true"></i>{{ __('Delete') }}
                                                                </a>
                                                                <form method="post" id="item-delete-{{ $ttr->id }}" action="{{ route('ttr.destroy', $ttr->id) }}" class="d-none">
                                                                    @csrf @method('delete')
                                                                </form>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Sync dropdown label with active class tab selection
        $('.dropdown-menu a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
            var selectedText = $(this).text().trim();
            $(this).closest('.nav-item.dropdown').find('.selected-text').text(selectedText);
        });

        // Reset dropdown label when clicking "All Timetables" or "Create Timetable"
        $('a[href="#all-tt"], a[href="#add-tt"]').on('shown.bs.tab', function(e) {
            $('.nav-item.dropdown .selected-text').text('{{ __("Filter By Class") }}');
        });
    });
</script>
@endsection
