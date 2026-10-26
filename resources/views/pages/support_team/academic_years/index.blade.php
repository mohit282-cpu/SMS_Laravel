@extends('layouts.master')
@section('page_title', __('Academic Years (BS)'))
@section('content')

    <div class="card">
        <div class="card-header header-elements-inline">
            <h6 class="card-title font-weight-bold">{{ __('Manage Nepali Academic Sessions (BS)') }}</h6>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            @if($current_session)
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="p-3 bg-light rounded border border-success d-flex align-items-center justify-content-between flex-wrap" style="background: rgba(16, 185, 129, 0.05) !important;">
                            <div class="d-flex align-items-center mb-2 mb-md-0">
                                <div class="mr-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: #10B981; color: #FFFFFF; font-size: 1.5rem;">
                                    <i class="icon-calendar52"></i>
                                </div>
                                <div>
                                    <div class="text-uppercase font-size-xs text-muted font-weight-bold">{{ __('Current Active Academic Session') }}</div>
                                    <h5 class="font-weight-bold mb-0 text-dark">{{ $current_session->name }} <span class="badge badge-success ml-2">{{ __('Active') }}</span></h5>
                                    <div class="text-muted font-size-xs mt-1">
                                        <strong>BS Range:</strong> {{ $current_session->start_date_bs }} to {{ $current_session->end_date_bs }} BS
                                        &bull; <strong>AD Equivalent:</strong> {{ $current_session->start_date ? $current_session->start_date->format('d M Y') : '-' }} to {{ $current_session->end_date ? $current_session->end_date->format('d M Y') : '-' }}
                                    </div>
                                </div>
                            </div>
                            <div>
                                <a href="{{ route('academic_years.edit', $current_session->id) }}" class="btn btn-sm btn-outline-success font-weight-semibold">
                                    <i class="icon-pencil mr-1"></i> {{ __('Edit Active Session') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <ul class="nav nav-tabs nav-tabs-highlight">
                <li class="nav-item"><a href="#all-academic-years" class="nav-link active" data-toggle="tab">{{ __('All Academic Years') }}</a></li>
                <li class="nav-item"><a href="#new-academic-year" class="nav-link" data-toggle="tab"><i class="icon-plus2"></i> {{ __('Create Academic Year') }}</a></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="all-academic-years">
                    <table class="table datatable-button-html5-columns">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Session Name') }}</th>
                            <th>{{ __('Year (BS)') }}</th>
                            <th>{{ __('Start Date') }}</th>
                            <th>{{ __('End Date') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($academic_years as $ay)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="font-weight-semibold">{{ $ay->name }}</td>
                                <td>{{ App\Helpers\NepaliHelper::formatNumber($ay->year_bs) }} BS</td>
                                <td>{{ $ay->start_date_bs }} BS <span class="text-muted">({{ $ay->start_date ? $ay->start_date->format('Y-m-d') : '' }})</span></td>
                                <td>{{ $ay->end_date_bs }} BS <span class="text-muted">({{ $ay->end_date ? $ay->end_date->format('Y-m-d') : '' }})</span></td>
                                <td>
                                    @if($ay->is_active)
                                        <span class="badge badge-success p-2">{{ __('Active Session') }}</span>
                                    @elseif($ay->is_closed)
                                        <span class="badge badge-secondary p-2">{{ __('Closed / Archived') }}</span>
                                    @else
                                        <span class="badge badge-info p-2">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="list-icons">
                                        <div class="dropdown">
                                            <a href="#" class="list-icons-item" data-toggle="dropdown">
                                                <i class="icon-menu9"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-left">
                                                <a href="{{ route('academic_years.edit', $ay->id) }}" class="dropdown-item"><i class="icon-pencil"></i> {{ __('Edit') }}</a>
                                                @if(!$ay->is_active)
                                                    <a href="#" onclick="event.preventDefault(); document.getElementById('activate-form-{{ $ay->id }}').submit();" class="dropdown-item text-success"><i class="icon-check"></i> {{ __('Set as Active Session') }}</a>
                                                    <form id="activate-form-{{ $ay->id }}" method="post" action="{{ route('academic_years.activate', $ay->id) }}" style="display: none;">@csrf @method('PUT')</form>
                                                @endif
                                                @if($ay->is_closed)
                                                    <a href="#" onclick="event.preventDefault(); document.getElementById('reopen-form-{{ $ay->id }}').submit();" class="dropdown-item text-info"><i class="icon-unlocked2"></i> {{ __('Reopen Session') }}</a>
                                                    <form id="reopen-form-{{ $ay->id }}" method="post" action="{{ route('academic_years.reopen', $ay->id) }}" style="display: none;">@csrf @method('PUT')</form>
                                                @else
                                                    <a href="#" onclick="event.preventDefault(); document.getElementById('close-form-{{ $ay->id }}').submit();" class="dropdown-item text-warning"><i class="icon-lock"></i> {{ __('Close / Archive Session') }}</a>
                                                    <form id="close-form-{{ $ay->id }}" method="post" action="{{ route('academic_years.close', $ay->id) }}" style="display: none;">@csrf @method('PUT')</form>
                                                @endif
                                                @if(!$ay->is_active)
                                                    <a id="{{ $ay->id }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item text-danger"><i class="icon-trash"></i> {{ __('Delete') }}</a>
                                                    <form method="post" id="item-delete-{{ $ay->id }}" action="{{ route('academic_years.destroy', $ay->id) }}" style="display: none;">@csrf @method('DELETE')</form>
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

                <div class="tab-pane fade" id="new-academic-year">
                    <div class="row">
                        <div class="col-md-8">
                            <form method="post" action="{{ route('academic_years.store') }}">
                                @csrf
                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Session Name') }}: <span class="text-danger">*</span></label>
                                    <div class="col-lg-9">
                                        <input name="name" value="{{ old('name', '2083/84 BS') }}" required type="text" class="form-control" placeholder="e.g. 2083/84 BS">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Academic Year (BS)') }}: <span class="text-danger">*</span></label>
                                    <div class="col-lg-9">
                                        <input name="year_bs" value="{{ old('year_bs', 2083) }}" required type="number" min="2000" max="2090" class="form-control" placeholder="2083">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Start Date (BS)') }}: <span class="text-danger">*</span></label>
                                    <div class="col-lg-9">
                                        <input name="start_date_bs" value="{{ old('start_date_bs', '2083-01-01') }}" required type="text" class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                                        <span class="form-text text-muted">{{ __('First day of Baishakh') }}</span>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('End Date (BS)') }}: <span class="text-danger">*</span></label>
                                    <div class="col-lg-9">
                                        <input name="end_date_bs" value="{{ old('end_date_bs', '2083-12-30') }}" required type="text" class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                                        <span class="form-text text-muted">{{ __('Last day of Chaitra') }}</span>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Description') }}:</label>
                                    <div class="col-lg-9">
                                        <textarea name="description" class="form-control" rows="3" placeholder="{{ __('Optional notes on session') }}">{{ old('description') }}</textarea>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Set as Active Session') }}:</label>
                                    <div class="col-lg-9">
                                        <div class="form-check">
                                            <label class="form-check-label">
                                                <input type="checkbox" name="is_active" value="1" class="form-check-input-styled" checked data-fouc>
                                                {{ __('Make this session active for student admissions, marks, and fees') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <button type="submit" class="btn btn-primary">{{ __('Create Session') }} <i class="icon-paperplane ml-2"></i></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
