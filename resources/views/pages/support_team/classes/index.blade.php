@extends('layouts.master')
@section('page_title', 'Manage Classes')
@section('content')

    {{-- Executive Header --}}
    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center">
                    <div class="d-inline-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px; border-radius: 14px; background: rgba(255, 87, 0, 0.12);">
                        <i class="icon-windows2 icon-2x" style="color: #FF5700;"></i>
                    </div>
                    <div>
                        <h3 class="font-weight-bold mb-1 text-dark" style="font-size: 1.35rem; letter-spacing: -0.01em;">
                            {{ __('Manage Classes') }}
                        </h3>
                        <p class="text-muted mb-0 font-size-sm">
                            {{ __('Academic grade levels, section allocations & student distribution') }}
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
                    <a href="{{ route('sections.index') }}" class="btn btn-outline-primary btn-sm font-weight-semibold shadow-sm ml-md-2" style="border-radius: 8px;">
                        <i class="icon-grid6 mr-1"></i>{{ __('Manage Sections') }}
                    </a>
                    <button type="button" class="btn btn-primary btn-sm font-weight-semibold shadow-sm ml-1" onclick="$('#tab-new-class-link').tab('show');" style="border-radius: 8px;">
                        <i class="icon-plus2 mr-1"></i>{{ __('Create New Class') }}
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Total Classes') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($total_classes ?? $my_classes->count()) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Configured grades') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(79, 70, 229, 0.1);">
                            <i class="icon-windows2 icon-2x" style="color: #4f46e5;"></i>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Total Sections') }}</span>
                            <h3 class="font-weight-bold text-success mb-0 mt-1">{{ number_format($total_sections ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Classroom cohorts') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16, 185, 129, 0.1);">
                            <i class="icon-grid6 icon-2x text-success"></i>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Enrolled Students') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($total_students ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Across all grades') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(6, 182, 212, 0.1);">
                            <i class="icon-users4 icon-2x" style="color: #06b6d4;"></i>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Class Levels') }}</span>
                            <h3 class="font-weight-bold text-warning mb-0 mt-1">{{ number_format($total_class_types ?? count($class_types)) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Education tiers') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1);">
                            <i class="icon-tree7 icon-2x text-warning"></i>
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
                    <a href="#all-classes" id="tab-all-classes-link" class="nav-link active font-weight-semibold" data-toggle="tab">
                        <i class="icon-windows2 mr-1"></i> {{ __('Manage Classes') }}
                        <span class="badge badge-primary badge-pill ml-1">{{ $my_classes->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#new-class" id="tab-new-class-link" class="nav-link font-weight-semibold" data-toggle="tab">
                        <i class="icon-plus2 mr-1"></i> {{ __('Create New Class') }}
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body p-3 p-md-4">
            <div class="tab-content">
                {{-- TAB 1: Class Directory Table --}}
                <div class="tab-pane fade show active" id="all-classes">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped datatable-button-html5-columns">
                            <thead class="thead-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>{{ __('Class Name') }}</th>
                                <th>{{ __('Class Level') }}</th>
                                <th>{{ __('Sections') }}</th>
                                <th>{{ __('Enrolled Students') }}</th>
                                <th class="text-center" style="width: 120px;">{{ __('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($my_classes as $c)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="d-inline-flex align-items-center justify-content-center mr-2 text-primary bg-light rounded" style="width: 32px; height: 32px;">
                                                <i class="icon-windows2"></i>
                                            </div>
                                            <span class="font-weight-bold text-dark font-size-sm">{{ $c->name }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light border text-dark font-weight-semibold py-1 px-2" style="border-radius: 6px;">
                                            {{ $c->class_type->name ?? '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if(isset($c->sections) && $c->sections->isNotEmpty())
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($c->sections as $sec)
                                                    <span class="badge badge-primary py-1 px-2 mr-1 mb-1 font-weight-semibold" style="border-radius: 4px;" title="{{ __('Section :name', ['name' => $sec->name]) }}">
                                                        {{ $sec->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted font-italic font-size-xs">{{ __('No sections') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('students.list', $c->id) }}" class="badge badge-light border py-1 px-2 font-weight-semibold text-dark" title="{{ __('View Enrolled Students') }}">
                                            <i class="icon-user mr-1 text-primary"></i>
                                            {{ number_format($c->student_record_count ?? ($c->student_record ? $c->student_record->count() : 0)) }} {{ __('Students') }}
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <div class="list-icons">
                                            <div class="dropdown">
                                                <a href="#" class="list-icons-item" data-toggle="dropdown" aria-expanded="false">
                                                    <i class="icon-menu9"></i>
                                                </a>

                                                <div class="dropdown-menu dropdown-menu-right shadow-sm">
                                                    @if(Qs::userIsTeamSA())
                                                        <a href="{{ route('classes.edit', $c->id) }}" class="dropdown-item">
                                                            <i class="icon-pencil text-primary"></i> {{ __('Edit Class') }}
                                                        </a>
                                                    @endif

                                                    <a href="{{ route('students.list', $c->id) }}" class="dropdown-item">
                                                        <i class="icon-users text-info"></i> {{ __('View Students') }}
                                                    </a>

                                                    <a href="{{ route('sections.index') }}" class="dropdown-item">
                                                        <i class="icon-grid6 text-success"></i> {{ __('Manage Sections') }}
                                                    </a>

                                                    @if(Qs::userIsSuperAdmin())
                                                        <div class="dropdown-divider"></div>
                                                        <a id="{{ $c->id }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item text-danger">
                                                            <i class="icon-trash"></i> {{ __('Delete Class') }}
                                                        </a>
                                                        <form method="post" id="item-delete-{{ $c->id }}" action="{{ route('classes.destroy', $c->id) }}" class="hidden">
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
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="icon-windows2 icon-3x text-muted mb-2"></i>
                                            <h6 class="font-weight-bold text-dark">{{ __('No Classes Found') }}</h6>
                                            <p class="font-size-sm mb-3 text-muted">{{ __('Create your first academic class to get started.') }}</p>
                                            <button type="button" class="btn btn-primary btn-sm" onclick="$('#tab-new-class-link').tab('show');">
                                                <i class="icon-plus2 mr-1"></i>{{ __('Create New Class') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB 2: Create New Class Form --}}
                <div class="tab-pane fade" id="new-class">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="alert alert-info border-0 shadow-sm mb-4" style="border-radius: 12px;">
                                <div class="d-flex align-items-center">
                                    <i class="icon-info22 icon-2x text-info mr-3"></i>
                                    <div>
                                        <h6 class="font-weight-bold mb-1 text-dark">{{ __('Automatic Section Provisioning') }}</h6>
                                        <p class="mb-0 font-size-sm text-dark">
                                            {{ __('When a class is created, default Section "A" will be provisioned automatically. You can add more sections or assign class teachers at') }}
                                            <a href="{{ route('sections.index') }}" class="font-weight-bold text-primary">{{ __('Manage Sections') }}</a>.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="card border shadow-none" style="border-radius: 14px;">
                                <div class="card-header bg-light py-3" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                                    <h5 class="font-weight-bold mb-0 text-dark">
                                        <i class="icon-plus2 mr-2" style="color: #FF5700;"></i>{{ __('Register New Academic Class') }}
                                    </h5>
                                    <span class="text-muted font-size-xs">{{ __('Provide class grade title and assign appropriate educational level.') }}</span>
                                </div>

                                <div class="card-body p-4">
                                    <form class="ajax-store" method="post" action="{{ route('classes.store') }}">
                                        @csrf
                                        <div class="form-group mb-3">
                                            <label class="font-weight-semibold">{{ __('Class Name') }}: <span class="text-danger">*</span></label>
                                            <input name="name" value="{{ old('name') }}" required type="text" class="form-control" placeholder="{{ __('e.g. Class 1, Grade 10, Nursery') }}">
                                            <span class="form-text text-muted font-size-xs">{{ __('The official academic label displayed across marksheets, report cards, and fee receipts.') }}</span>
                                        </div>

                                        <div class="form-group mb-4">
                                            <label for="class_type_id" class="font-weight-semibold">{{ __('Class Level / Type') }}: <span class="text-danger">*</span></label>
                                            <select required data-placeholder="{{ __('Select Class Type') }}" class="form-control select" name="class_type_id" id="class_type_id">
                                                @foreach($class_types as $ct)
                                                    <option {{ old('class_type_id') == $ct->id ? 'selected' : '' }} value="{{ $ct->id }}">{{ $ct->name }}</option>
                                                @endforeach
                                            </select>
                                            <span class="form-text text-muted font-size-xs">{{ __('Associates the class with an education tier (e.g. Primary, Secondary).') }}</span>
                                        </div>

                                        <div class="text-right">
                                            <button id="ajax-btn" type="submit" class="btn btn-primary font-weight-semibold px-4 py-2 shadow-sm" style="border-radius: 8px;">
                                                <i class="icon-paperplane mr-1"></i>{{ __('Create Class') }}
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
