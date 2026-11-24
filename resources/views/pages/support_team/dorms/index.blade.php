@extends('layouts.master')
@section('page_title', 'Manage Dorms')
@section('content')

    {{-- Executive Header --}}
    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center">
                    <div class="d-inline-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px; border-radius: 14px; background: rgba(255, 87, 0, 0.12);">
                        <i class="icon-home4 icon-2x" style="color: #FF5700;"></i>
                    </div>
                    <div>
                        <h3 class="font-weight-bold mb-1 text-dark" style="font-size: 1.35rem; letter-spacing: -0.01em;">
                            {{ __('Manage Dormitories & Hostels') }}
                        </h3>
                        <p class="text-muted mb-0 font-size-sm">
                            {{ __('Residential hostel facilities, boarding assignments & student housing records') }}
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
                    <button type="button" class="btn btn-primary btn-sm font-weight-semibold shadow-sm ml-md-2" onclick="$('#tab-new-dorm-link').tab('show');" style="border-radius: 8px;">
                        <i class="icon-plus2 mr-1"></i>{{ __('Create New Dorm') }}
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Total Hostels') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($total_dorms ?? $dorms->count()) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Residential facilities') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(79, 70, 229, 0.1);">
                            <i class="icon-home4 icon-2x" style="color: #4f46e5;"></i>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Boarding Students') }}</span>
                            <h3 class="font-weight-bold text-success mb-0 mt-1">{{ number_format($total_boarders ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Residing in hostels') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16, 185, 129, 0.1);">
                            <i class="icon-users4 icon-2x text-success"></i>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Day Scholars') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($total_day_scholars ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Non-residential pupils') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(6, 182, 212, 0.1);">
                            <i class="icon-user-check icon-2x" style="color: #06b6d4;"></i>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Total Enrolled') }}</span>
                            <h3 class="font-weight-bold text-warning mb-0 mt-1">{{ number_format($total_students ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Overall student body') }}</span>
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
                    <a href="#all-dorms" id="tab-all-dorms-link" class="nav-link active font-weight-semibold" data-toggle="tab">
                        <i class="icon-home4 mr-1"></i> {{ __('Manage Dormitories') }}
                        <span class="badge badge-primary badge-pill ml-1">{{ $dorms->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#new-dorm" id="tab-new-dorm-link" class="nav-link font-weight-semibold" data-toggle="tab">
                        <i class="icon-plus2 mr-1"></i> {{ __('Create New Dorm') }}
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body p-3 p-md-4">
            <div class="tab-content">
                {{-- TAB 1: Dormitory List Table --}}
                <div class="tab-pane fade show active" id="all-dorms">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped datatable-button-html5-columns">
                            <thead class="thead-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>{{ __('Dormitory Name') }}</th>
                                <th>{{ __('Description / Location') }}</th>
                                <th>{{ __('Boarding Students') }}</th>
                                <th class="text-center" style="width: 120px;">{{ __('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($dorms as $d)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="d-inline-flex align-items-center justify-content-center mr-2 text-primary bg-light rounded" style="width: 34px; height: 34px;">
                                                <i class="icon-home4"></i>
                                            </div>
                                            <div>
                                                <span class="font-weight-bold text-dark font-size-sm">{{ $d->name }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($d->description)
                                            <span class="text-dark">{{ $d->description }}</span>
                                        @else
                                            <span class="text-muted font-italic font-size-xs">{{ __('No description provided') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-light border py-1 px-2 font-weight-semibold text-dark">
                                            <i class="icon-user mr-1 text-primary"></i>
                                            {{ number_format($d->students_count ?? 0) }} {{ __('Boarders') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="list-icons">
                                            <div class="dropdown">
                                                <a href="#" class="list-icons-item" data-toggle="dropdown" aria-expanded="false">
                                                    <i class="icon-menu9"></i>
                                                </a>

                                                <div class="dropdown-menu dropdown-menu-right shadow-sm">
                                                    @if(Qs::userIsTeamSA())
                                                        <a href="{{ route('dorms.edit', $d->id) }}" class="dropdown-item">
                                                            <i class="icon-pencil text-primary"></i> {{ __('Edit Dorm') }}
                                                        </a>
                                                    @endif

                                                    @if(Qs::userIsSuperAdmin())
                                                        <div class="dropdown-divider"></div>
                                                        <a id="{{ $d->id }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item text-danger">
                                                            <i class="icon-trash"></i> {{ __('Delete Dorm') }}
                                                        </a>
                                                        <form method="post" id="item-delete-{{ $d->id }}" action="{{ route('dorms.destroy', $d->id) }}" class="hidden">
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
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="icon-home4 icon-3x text-muted mb-2"></i>
                                            <h6 class="font-weight-bold text-dark">{{ __('No Dormitories Found') }}</h6>
                                            <p class="font-size-sm mb-3 text-muted">{{ __('Create a hostel record to manage student boarding assignments.') }}</p>
                                            <button type="button" class="btn btn-primary btn-sm" onclick="$('#tab-new-dorm-link').tab('show');">
                                                <i class="icon-plus2 mr-1"></i>{{ __('Create New Dorm') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB 2: Create New Dorm Form --}}
                <div class="tab-pane fade" id="new-dorm">
                    <div class="row justify-content-center">
                        <div class="col-lg-7">
                            <div class="card border shadow-none" style="border-radius: 14px;">
                                <div class="card-header bg-light py-3" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                                    <h5 class="font-weight-bold mb-0 text-dark">
                                        <i class="icon-plus2 mr-2" style="color: #FF5700;"></i>{{ __('Register New Dormitory') }}
                                    </h5>
                                    <span class="text-muted font-size-xs">{{ __('Provide hostel building title and optional location or capacity details.') }}</span>
                                </div>

                                <div class="card-body p-4">
                                    <form class="ajax-store" method="post" action="{{ route('dorms.store') }}">
                                        @csrf
                                        <div class="form-group mb-3">
                                            <label class="font-weight-semibold">{{ __('Dormitory Name') }}: <span class="text-danger">*</span></label>
                                            <input name="name" value="{{ old('name') }}" required type="text" class="form-control" placeholder="{{ __('e.g. Sagarmatha Hostel, Everest Hall') }}">
                                            <span class="form-text text-muted font-size-xs">{{ __('Unique identifier or official hostel house name.') }}</span>
                                        </div>

                                        <div class="form-group mb-4">
                                            <label class="font-weight-semibold">{{ __('Description / Notes') }}:</label>
                                            <textarea name="description" rows="3" class="form-control" placeholder="{{ __('e.g. Senior Boys Hostel, Block B, 40-bed capacity with warden quarters') }}">{{ old('description') }}</textarea>
                                            <span class="form-text text-muted font-size-xs">{{ __('Optional notes regarding location, gender allocation, room counts, or capacity.') }}</span>
                                        </div>

                                        <div class="text-right">
                                            <button id="ajax-btn" type="submit" class="btn btn-primary font-weight-semibold px-4 py-2 shadow-sm" style="border-radius: 8px;">
                                                <i class="icon-paperplane mr-1"></i>{{ __('Create Dormitory') }}
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
