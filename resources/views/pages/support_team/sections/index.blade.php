@extends('layouts.master')
@section('page_title', 'Manage Class Sections')
@section('content')

    {{-- Executive Header --}}
    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center">
                    <div class="d-inline-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px; border-radius: 14px; background: rgba(255, 87, 0, 0.12);">
                        <i class="icon-grid6 icon-2x" style="color: #FF5700;"></i>
                    </div>
                    <div>
                        <h3 class="font-weight-bold mb-1 text-dark" style="font-size: 1.35rem; letter-spacing: -0.01em;">
                            {{ __('Manage Class Sections') }}
                        </h3>
                        <p class="text-muted mb-0 font-size-sm">
                            {{ __('Classroom cohorts, section allocations & teacher-in-charge assignments') }}
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
                    <button type="button" class="btn btn-primary btn-sm font-weight-semibold shadow-sm ml-1" onclick="$('#tab-new-section-link').tab('show');" style="border-radius: 8px;">
                        <i class="icon-plus2 mr-1"></i>{{ __('Create New Section') }}
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Total Sections') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($total_sections ?? $sections->count()) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Classroom cohorts') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(79, 70, 229, 0.1);">
                            <i class="icon-grid6 icon-2x" style="color: #4f46e5;"></i>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Academic Classes') }}</span>
                            <h3 class="font-weight-bold text-success mb-0 mt-1">{{ number_format($total_classes ?? $my_classes->count()) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Grade levels') }}</span>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Class Teachers') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($assigned_teachers ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Teachers in charge') }}</span>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Enrolled Pupils') }}</span>
                            <h3 class="font-weight-bold text-warning mb-0 mt-1">{{ number_format($total_students ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Student allocations') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1);">
                            <i class="icon-users4 icon-2x text-warning"></i>
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
                    <a href="#all-sections" id="tab-all-sections-link" class="nav-link active font-weight-semibold" data-toggle="tab">
                        <i class="icon-grid6 mr-1"></i> {{ __('All Sections Overview') }}
                        <span class="badge badge-primary badge-pill ml-1">{{ $sections->count() }}</span>
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
                    <a href="#new-section" id="tab-new-section-link" class="nav-link font-weight-semibold" data-toggle="tab">
                        <i class="icon-plus2 mr-1"></i> {{ __('Create New Section') }}
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body p-3 p-md-4">
            <div class="tab-content">
                {{-- TAB 1: All Sections Overview --}}
                <div class="tab-pane fade show active" id="all-sections">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped datatable-button-html5-columns">
                            <thead class="thead-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>{{ __('Section Name') }}</th>
                                <th>{{ __('Academic Class') }}</th>
                                <th>{{ __('Class Teacher (Teacher-in-Charge)') }}</th>
                                <th>{{ __('Enrolled Pupils') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-center" style="width: 120px;">{{ __('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($sections as $s)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="d-inline-flex align-items-center justify-content-center mr-2 text-primary bg-light rounded" style="width: 32px; height: 32px;">
                                                <i class="icon-grid6"></i>
                                            </div>
                                            <div>
                                                <span class="font-weight-bold text-dark font-size-sm">{{ $s->name }}</span>
                                            </div>
                                        </div>
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
                                        <span class="badge badge-light border py-1 px-2 font-weight-semibold text-dark">
                                            <i class="icon-user mr-1 text-primary"></i>
                                            {{ number_format($s->student_record_count ?? 0) }} {{ __('Pupils') }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($s->active)
                                            <span class="badge badge-success py-1 px-2 font-weight-semibold">
                                                <i class="icon-checkmark-circle mr-1"></i>{{ __('Default Section') }}
                                            </span>
                                        @else
                                            <span class="badge badge-light border text-muted py-1 px-2">
                                                {{ __('Additional Section') }}
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
                                                        <a href="{{ route('sections.edit', $s->id) }}" class="dropdown-item">
                                                            <i class="icon-pencil text-primary"></i> {{ __('Edit Section') }}
                                                        </a>
                                                    @endif

                                                    @if($s->my_class_id)
                                                        <a href="{{ route('students.list', $s->my_class_id) }}" class="dropdown-item">
                                                            <i class="icon-users text-info"></i> {{ __('View Class Students') }}
                                                        </a>
                                                    @endif

                                                    @if(Qs::userIsSuperAdmin() && !$s->active)
                                                        <div class="dropdown-divider"></div>
                                                        <a id="{{ $s->id }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item text-danger">
                                                            <i class="icon-trash"></i> {{ __('Delete Section') }}
                                                        </a>
                                                        <form method="post" id="item-delete-{{ $s->id }}" action="{{ route('sections.destroy', $s->id) }}" class="hidden">
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
                                            <i class="icon-grid6 icon-3x text-muted mb-2"></i>
                                            <h6 class="font-weight-bold text-dark">{{ __('No Sections Found') }}</h6>
                                            <p class="font-size-sm mb-3 text-muted">{{ __('Create sections for your academic classes.') }}</p>
                                            <button type="button" class="btn btn-primary btn-sm" onclick="$('#tab-new-section-link').tab('show');">
                                                <i class="icon-plus2 mr-1"></i>{{ __('Create New Section') }}
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
                @foreach($my_classes as $d)
                    <div class="tab-pane fade" id="c{{ $d->id }}">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="font-weight-bold text-dark mb-0">
                                <i class="icon-windows2 mr-2 text-primary"></i>{{ $d->name }} — {{ __('Sections') }}
                            </h5>
                            <button type="button" class="btn btn-outline-primary btn-sm font-weight-semibold" onclick="$('#my_class_id').val('{{ $d->id }}'); $('#tab-new-section-link').tab('show');">
                                <i class="icon-plus2 mr-1"></i>{{ __('Add Section to :class', ['class' => $d->name]) }}
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-striped datatable-button-html5-columns">
                                <thead class="thead-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>{{ __('Section Name') }}</th>
                                    <th>{{ __('Class Teacher') }}</th>
                                    <th>{{ __('Enrolled Pupils') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th class="text-center" style="width: 120px;">{{ __('Action') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @php $classSections = $sections->where('my_class_id', $d->id); @endphp
                                @forelse($classSections as $s)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="font-weight-bold text-dark">{{ $s->name }}</span>
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
                                            <span class="badge badge-light border py-1 px-2 font-weight-semibold text-dark">
                                                <i class="icon-user mr-1 text-primary"></i>
                                                {{ number_format($s->student_record_count ?? 0) }} {{ __('Pupils') }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($s->active)
                                                <span class="badge badge-success py-1 px-2 font-weight-semibold">
                                                    <i class="icon-checkmark-circle mr-1"></i>{{ __('Default Section') }}
                                                </span>
                                            @else
                                                <span class="badge badge-light border text-muted py-1 px-2">
                                                    {{ __('Additional Section') }}
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
                                                            <a href="{{ route('sections.edit', $s->id) }}" class="dropdown-item">
                                                                <i class="icon-pencil text-primary"></i> {{ __('Edit Section') }}
                                                            </a>
                                                        @endif

                                                        @if(Qs::userIsSuperAdmin() && !$s->active)
                                                            <div class="dropdown-divider"></div>
                                                            <a id="{{ $s->id }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item text-danger">
                                                                <i class="icon-trash"></i> {{ __('Delete Section') }}
                                                            </a>
                                                            <form method="post" id="item-delete-{{ $s->id }}" action="{{ route('sections.destroy', $s->id) }}" class="hidden">
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
                                            {{ __('No sections found for :class.', ['class' => $d->name]) }}
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach

                {{-- TAB: Create New Section Form --}}
                <div class="tab-pane fade" id="new-section">
                    <div class="row justify-content-center">
                        <div class="col-lg-7">
                            <div class="alert alert-info border-0 shadow-sm mb-4" style="border-radius: 12px;">
                                <div class="d-flex align-items-center">
                                    <i class="icon-info22 icon-2x text-info mr-3"></i>
                                    <div>
                                        <h6 class="font-weight-bold mb-1 text-dark">{{ __('Section Provisioning & Class Teacher Assignment') }}</h6>
                                        <p class="mb-0 font-size-sm text-dark">
                                            {{ __('Each class has a default Section "A". Create additional sections (e.g. B, C) and assign an authorized teacher-in-charge to supervise the cohort.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="card border shadow-none" style="border-radius: 14px;">
                                <div class="card-header bg-light py-3" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                                    <h5 class="font-weight-bold mb-0 text-dark">
                                        <i class="icon-plus2 mr-2" style="color: #FF5700;"></i>{{ __('Register New Section') }}
                                    </h5>
                                    <span class="text-muted font-size-xs">{{ __('Configure section identifier, target class, and designated class teacher.') }}</span>
                                </div>

                                <div class="card-body p-4">
                                    <form class="ajax-store" method="post" action="{{ route('sections.store') }}">
                                        @csrf
                                        <div class="form-group mb-3">
                                            <label class="font-weight-semibold">{{ __('Section Name') }}: <span class="text-danger">*</span></label>
                                            <input name="name" value="{{ old('name') }}" required type="text" class="form-control" placeholder="{{ __('e.g. B, C, Science, Commerce') }}">
                                            <span class="form-text text-muted font-size-xs">{{ __('Short section identifier or cohort label.') }}</span>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="my_class_id" class="font-weight-semibold">{{ __('Academic Class') }}: <span class="text-danger">*</span></label>
                                            <select required data-placeholder="{{ __('Select Class') }}" class="form-control select" name="my_class_id" id="my_class_id">
                                                @foreach($my_classes as $c)
                                                    <option {{ old('my_class_id') == $c->id ? 'selected' : '' }} value="{{ $c->id }}">{{ $c->name }}</option>
                                                @endforeach
                                            </select>
                                            <span class="form-text text-muted font-size-xs">{{ __('The academic grade this section belongs to.') }}</span>
                                        </div>

                                        <div class="form-group mb-4">
                                            <label for="teacher_id" class="font-weight-semibold">{{ __('Class Teacher (Optional)') }}:</label>
                                            <select data-placeholder="{{ __('Select Class Teacher') }}" class="form-control select-search" name="teacher_id" id="teacher_id">
                                                <option value="">{{ __('None (Assign Later)') }}</option>
                                                @foreach($teachers as $t)
                                                    <option {{ old('teacher_id') == Qs::hash($t->id) ? 'selected' : '' }} value="{{ Qs::hash($t->id) }}">{{ $t->name }}</option>
                                                @endforeach
                                            </select>
                                            <span class="form-text text-muted font-size-xs">{{ __('Designated teacher-in-charge responsible for attendance and report cards.') }}</span>
                                        </div>

                                        <div class="text-right">
                                            <button id="ajax-btn" type="submit" class="btn btn-primary font-weight-semibold px-4 py-2 shadow-sm" style="border-radius: 8px;">
                                                <i class="icon-paperplane mr-1"></i>{{ __('Create Section') }}
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
