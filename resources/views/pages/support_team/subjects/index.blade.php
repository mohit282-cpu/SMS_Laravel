@extends('layouts.master')
@section('page_title', 'Manage Subjects')
@section('content')

    {{-- Executive Header --}}
    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center">
                    <div class="d-inline-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px; border-radius: 14px; background: rgba(255, 87, 0, 0.12);">
                        <i class="icon-books icon-2x" style="color: #FF5700;"></i>
                    </div>
                    <div>
                        <h3 class="font-weight-bold mb-1 text-dark" style="font-size: 1.35rem; letter-spacing: -0.01em;">
                            {{ __('Manage Subjects & Curriculum') }}
                        </h3>
                        <p class="text-muted mb-0 font-size-sm">
                            {{ __('Academic courses, credit hours, evaluation schemes & instructor assignments') }}
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
                    <a href="{{ route('classes.index') }}" class="btn btn-outline-primary btn-sm font-weight-semibold shadow-sm ml-md-2" style="border-radius: 8px;">
                        <i class="icon-windows2 mr-1"></i>{{ __('Manage Classes') }}
                    </a>
                    <button type="button" class="btn btn-primary btn-sm font-weight-semibold shadow-sm ml-1" onclick="$('#tab-new-subject-link').tab('show');" style="border-radius: 8px;">
                        <i class="icon-plus2 mr-1"></i>{{ __('Add New Subject') }}
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Total Subjects') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($total_subjects ?? $subjects->count()) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Curriculum courses') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(79, 70, 229, 0.1);">
                            <i class="icon-books icon-2x" style="color: #4f46e5;"></i>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Classes Covered') }}</span>
                            <h3 class="font-weight-bold text-success mb-0 mt-1">{{ number_format($total_classes ?? $my_classes->count()) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Grade levels offered') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16, 185, 129, 0.1);">
                            <i class="icon-windows2 icon-2x text-success"></i>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Subject Teachers') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($assigned_teachers ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Faculty allocations') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(6, 182, 212, 0.1);">
                            <i class="icon-user-tie icon-2x" style="color: #06b6d4;"></i>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Practical Courses') }}</span>
                            <h3 class="font-weight-bold text-warning mb-0 mt-1">{{ number_format($practical_subjects ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Theory + Lab schemes') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1);">
                            <i class="icon-lab icon-2x text-warning"></i>
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
                    <a href="#all-subjects" id="tab-all-subjects-link" class="nav-link active font-weight-semibold" data-toggle="tab">
                        <i class="icon-books mr-1"></i> {{ __('All Subjects Overview') }}
                        <span class="badge badge-primary badge-pill ml-1">{{ $subjects->count() }}</span>
                    </a>
                </li>

                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle font-weight-semibold" data-toggle="dropdown" data-default="Filter by Class">
                        <i class="icon-filter3 mr-1"></i> <span class="selected-text">{{ __('Filter by Class') }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow-sm">
                        @foreach($my_classes as $c)
                            <a href="#c{{ $c->id }}" class="dropdown-item" data-toggle="tab">
                                <i class="icon-windows2 mr-2 text-primary"></i>{{ $c->name }}
                            </a>
                        @endforeach
                    </div>
                </li>

                <li class="nav-item">
                    <a href="#new-subject" id="tab-new-subject-link" class="nav-link font-weight-semibold" data-toggle="tab">
                        <i class="icon-plus2 mr-1"></i> {{ __('Add New Subject') }}
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body p-3 p-md-4">
            <div class="tab-content">
                {{-- TAB 1: All Subjects Overview --}}
                <div class="tab-pane fade show active" id="all-subjects">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped datatable-button-html5-columns">
                            <thead class="thead-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>{{ __('Subject Title') }}</th>
                                <th>{{ __('Short Code') }}</th>
                                <th>{{ __('Academic Class') }}</th>
                                <th>{{ __('Subject Teacher') }}</th>
                                <th>{{ __('Evaluation Scheme') }}</th>
                                <th class="text-center" style="width: 120px;">{{ __('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($subjects as $s)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="d-inline-flex align-items-center justify-content-center mr-2 text-primary bg-light rounded" style="width: 32px; height: 32px;">
                                                <i class="icon-books"></i>
                                            </div>
                                            <div>
                                                <span class="font-weight-bold text-dark font-size-sm">{{ $s->name }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light border text-dark font-weight-semibold py-1 px-2" style="border-radius: 6px;">
                                            {{ $s->slug ?: '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-light border text-dark font-weight-semibold py-1 px-2" style="border-radius: 6px;">
                                            {{ $s->my_class->name ?? '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($s->teacher_id && $s->teacher)
                                            <a href="{{ route('users.show', Qs::hash($s->teacher_id)) }}" class="font-weight-semibold text-dark">
                                                <i class="icon-user-tie mr-1 text-primary"></i>{{ $s->teacher->name }}
                                            </a>
                                        @else
                                            <span class="badge badge-light text-muted border font-italic py-1 px-2" style="font-size: 0.8rem;">
                                                <i class="icon-warning22 text-warning mr-1"></i>{{ __('Not Assigned') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($s->has_practical)
                                            <span class="badge badge-info py-1 px-2 font-weight-semibold mr-1" style="border-radius: 6px;">
                                                <i class="icon-lab mr-1"></i>{{ __('Theory + Lab') }}
                                            </span>
                                        @else
                                            <span class="badge badge-light border text-muted py-1 px-2" style="border-radius: 6px;">
                                                {{ __('Theory Only') }}
                                            </span>
                                        @endif
                                        @if($s->credit_hour)
                                            <span class="text-muted font-size-xs ml-1">({{ $s->credit_hour }} CH)</span>
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
                                                        <a href="{{ route('subjects.edit', $s->id) }}" class="dropdown-item">
                                                            <i class="icon-pencil text-primary"></i> {{ __('Edit Subject') }}
                                                        </a>
                                                    @endif

                                                    @if(Qs::userIsSuperAdmin())
                                                        <div class="dropdown-divider"></div>
                                                        <a id="{{ $s->id }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item text-danger">
                                                            <i class="icon-trash"></i> {{ __('Delete Subject') }}
                                                        </a>
                                                        <form method="post" id="item-delete-{{ $s->id }}" action="{{ route('subjects.destroy', $s->id) }}" class="hidden">
                                                            @csrf
                                                            @method('delete')
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
                                            <i class="icon-books icon-3x text-muted mb-2"></i>
                                            <h6 class="font-weight-bold text-dark">{{ __('No Subjects Found') }}</h6>
                                            <p class="font-size-sm mb-3 text-muted">{{ __('Create curriculum courses to begin academic operations.') }}</p>
                                            <button type="button" class="btn btn-primary btn-sm" onclick="$('#tab-new-subject-link').tab('show');">
                                                <i class="icon-plus2 mr-1"></i>{{ __('Add New Subject') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Class Specific Tabs --}}
                @foreach($my_classes as $c)
                    <div class="tab-pane fade" id="c{{ $c->id }}">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="font-weight-bold text-dark mb-0">
                                <i class="icon-windows2 mr-2 text-primary"></i>{{ $c->name }} — {{ __('Curriculum Subjects') }}
                            </h5>
                            <button type="button" class="btn btn-outline-primary btn-sm font-weight-semibold" onclick="$('#my_class_id').val('{{ $c->id }}'); $('#tab-new-subject-link').tab('show');">
                                <i class="icon-plus2 mr-1"></i>{{ __('Add Subject to :class', ['class' => $c->name]) }}
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-striped datatable-button-html5-columns">
                                <thead class="thead-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>{{ __('Subject Title') }}</th>
                                    <th>{{ __('Short Code') }}</th>
                                    <th>{{ __('Subject Teacher') }}</th>
                                    <th>{{ __('Evaluation Scheme') }}</th>
                                    <th class="text-center" style="width: 120px;">{{ __('Action') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @php $classSubjects = $subjects->where('my_class_id', $c->id); @endphp
                                @forelse($classSubjects as $s)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="font-weight-bold text-dark">{{ $s->name }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light border text-dark">{{ $s->slug ?: '—' }}</span>
                                        </td>
                                        <td>
                                            @if($s->teacher_id && $s->teacher)
                                                <a href="{{ route('users.show', Qs::hash($s->teacher_id)) }}" class="font-weight-semibold text-dark">
                                                    <i class="icon-user-tie mr-1 text-primary"></i>{{ $s->teacher->name }}
                                                </a>
                                            @else
                                                <span class="badge badge-light text-muted border font-italic">{{ __('Not Assigned') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($s->has_practical)
                                                <span class="badge badge-info py-1 px-2 font-weight-semibold">
                                                    <i class="icon-lab mr-1"></i>{{ __('Theory + Lab') }}
                                                </span>
                                            @else
                                                <span class="badge badge-light border text-muted py-1 px-2">
                                                    {{ __('Theory Only') }}
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
                                                            <a href="{{ route('subjects.edit', $s->id) }}" class="dropdown-item">
                                                                <i class="icon-pencil text-primary"></i> {{ __('Edit Subject') }}
                                                            </a>
                                                        @endif

                                                        @if(Qs::userIsSuperAdmin())
                                                            <div class="dropdown-divider"></div>
                                                            <a id="{{ $s->id }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item text-danger">
                                                                <i class="icon-trash"></i> {{ __('Delete Subject') }}
                                                            </a>
                                                            <form method="post" id="item-delete-{{ $s->id }}" action="{{ route('subjects.destroy', $s->id) }}" class="hidden">
                                                                @csrf
                                                                @method('delete')
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            {{ __('No subjects found for :class.', ['class' => $c->name]) }}
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach

                {{-- TAB: Add New Subject Form --}}
                <div class="tab-pane fade" id="new-subject">
                    <div class="row justify-content-center">
                        <div class="col-lg-7">
                            <div class="card border shadow-none" style="border-radius: 14px;">
                                <div class="card-header bg-light py-3" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                                    <h5 class="font-weight-bold mb-0 text-dark">
                                        <i class="icon-plus2 mr-2" style="color: #FF5700;"></i>{{ __('Register New Subject') }}
                                    </h5>
                                    <span class="text-muted font-size-xs">{{ __('Configure curriculum title, short code, target grade, and instructor assignment.') }}</span>
                                </div>

                                <div class="card-body p-4">
                                    <form class="ajax-store" method="post" action="{{ route('subjects.store') }}">
                                        @csrf
                                        <div class="form-group mb-3">
                                            <label class="font-weight-semibold">{{ __('Subject Title') }}: <span class="text-danger">*</span></label>
                                            <input name="name" value="{{ old('name') }}" required type="text" class="form-control" placeholder="{{ __('e.g. Mathematics, Compulsory English, Science') }}">
                                            <span class="form-text text-muted font-size-xs">{{ __('Official subject course name displayed on marksheets and report cards.') }}</span>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label class="font-weight-semibold">{{ __('Short Code / Abbreviation') }}: <span class="text-danger">*</span></label>
                                            <input name="slug" value="{{ old('slug') }}" required type="text" class="form-control" placeholder="{{ __('e.g. MATH, ENG, SCI') }}">
                                            <span class="form-text text-muted font-size-xs">{{ __('Short code utilized on timetables, exam schedules, and grade sheets.') }}</span>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="my_class_id" class="font-weight-semibold">{{ __('Academic Class') }}: <span class="text-danger">*</span></label>
                                            <select required data-placeholder="{{ __('Select Class') }}" class="form-control select" name="my_class_id" id="my_class_id">
                                                <option value="">{{ __('Choose Class...') }}</option>
                                                @foreach($my_classes as $c)
                                                    <option {{ old('my_class_id') == $c->id ? 'selected' : '' }} value="{{ $c->id }}">{{ $c->name }}</option>
                                                @endforeach
                                            </select>
                                            <span class="form-text text-muted font-size-xs">{{ __('Grade level to which this subject belongs.') }}</span>
                                        </div>

                                        <div class="form-group mb-4">
                                            <label for="teacher_id" class="font-weight-semibold">{{ __('Subject Teacher') }}: <span class="text-danger">*</span></label>
                                            <select required data-placeholder="{{ __('Select Teacher') }}" class="form-control select-search" name="teacher_id" id="teacher_id">
                                                <option value="">{{ __('Choose Instructor...') }}</option>
                                                @foreach($teachers as $t)
                                                    <option {{ old('teacher_id') == Qs::hash($t->id) ? 'selected' : '' }} value="{{ Qs::hash($t->id) }}">{{ $t->name }}</option>
                                                @endforeach
                                            </select>
                                            <span class="form-text text-muted font-size-xs">{{ __('Designated teacher responsible for marks evaluation and timetable scheduling.') }}</span>
                                        </div>

                                        <div class="text-right">
                                            <button id="ajax-btn" type="submit" class="btn btn-primary font-weight-semibold px-4 py-2 shadow-sm" style="border-radius: 8px;">
                                                <i class="icon-paperplane mr-1"></i>{{ __('Create Subject') }}
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
