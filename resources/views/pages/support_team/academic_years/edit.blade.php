@extends('layouts.master')
@section('page_title', __('Edit Academic Year (BS)') . ' — ' . $ay->name)
@section('content')

    {{-- Executive Header --}}
    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center">
                    <a href="{{ route('academic_years.index') }}" class="btn btn-light btn-icon mr-3 shadow-sm" style="border-radius: 10px;" title="{{ __('Back to Academic Years List') }}">
                        <i class="icon-arrow-left7"></i>
                    </a>
                    <div>
                        <h3 class="font-weight-bold mb-1 text-dark" style="font-size: 1.35rem; letter-spacing: -0.01em;">
                            {{ __('Edit Academic Session') }}: {{ $ay->name }}
                        </h3>
                        <p class="text-muted mb-0 font-size-sm">
                            <span class="badge badge-light border text-muted mr-1">{{ $ay->year_bs }} BS</span>
                            @if($ay->is_active)
                                <span class="badge badge-success py-0 px-2 font-weight-semibold mr-1">{{ __('Active Session') }}</span>
                            @elseif($ay->is_closed)
                                <span class="badge badge-secondary py-0 px-2 font-weight-semibold mr-1">{{ __('Closed / Archived') }}</span>
                            @endif
                            <span>{{ __('Update session title, Bikram Sambat date range, or notes') }}</span>
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
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card sms-card border-0 shadow-sm" style="border-radius: 14px;">
                <div class="card-header bg-white py-3 border-bottom" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                    <h5 class="font-weight-bold mb-0 text-dark">
                        <i class="icon-pencil5 mr-2 text-primary"></i>{{ __('Session Configuration') }}
                    </h5>
                </div>

                <div class="card-body p-4">
                    <form method="post" action="{{ route('academic_years.update', $ay->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="form-group mb-3">
                            <label class="font-weight-semibold">{{ __('Session Name') }}: <span class="text-danger">*</span></label>
                            <input name="name" value="{{ old('name', $ay->name) }}" required type="text" class="form-control" placeholder="{{ __('e.g. 2083/84 BS') }}">
                            <span class="form-text text-muted font-size-xs">{{ __('The institutional identifier used across reports and billing.') }}</span>
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-semibold">{{ __('Academic Year (BS)') }}: <span class="text-danger">*</span></label>
                            <input name="year_bs" value="{{ old('year_bs', $ay->year_bs) }}" required type="number" min="2000" max="2090" class="form-control" placeholder="2083">
                            <span class="form-text text-muted font-size-xs">{{ __('Four-digit Bikram Sambat year.') }}</span>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group mb-3">
                                <label class="font-weight-semibold">{{ __('Start Date (BS)') }}: <span class="text-danger">*</span></label>
                                <input name="start_date_bs" value="{{ old('start_date_bs', $ay->start_date_bs) }}" required type="text" class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                                <span class="form-text text-muted font-size-xs">{{ __('First day of Baishakh') }} ({{ $ay->start_date ? $ay->start_date->format('Y-m-d') : '' }} AD)</span>
                            </div>

                            <div class="col-md-6 form-group mb-3">
                                <label class="font-weight-semibold">{{ __('End Date (BS)') }}: <span class="text-danger">*</span></label>
                                <input name="end_date_bs" value="{{ old('end_date_bs', $ay->end_date_bs) }}" required type="text" class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                                <span class="form-text text-muted font-size-xs">{{ __('Last day of Chaitra') }} ({{ $ay->end_date ? $ay->end_date->format('Y-m-d') : '' }} AD)</span>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-semibold">{{ __('Description / Notes') }}:</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="{{ __('Optional notes on session') }}">{{ old('description', $ay->description) }}</textarea>
                        </div>

                        <div class="form-group mb-4">
                            @if($ay->is_closed)
                                <div class="badge badge-secondary py-1 px-2 mb-2 font-size-xs">{{ __('Closed / Archived Session') }}</div>
                                <p class="text-muted font-size-xs mb-0">{{ __('This session is currently closed and cannot be activated directly. Please reopen the session first to enable activation.') }}</p>
                            @else
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="check_is_active" {{ old('is_active', $ay->is_active) ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-semibold text-dark" for="check_is_active">
                                        {{ __('Make this session active for student admissions, marks, and fees') }}
                                    </label>
                                </div>
                            @endif
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-2">
                            <a href="{{ route('academic_years.index') }}" class="btn btn-light font-weight-semibold shadow-sm" style="border-radius: 8px;">
                                <i class="icon-arrow-left7 mr-1"></i>{{ __('Cancel') }}
                            </a>
                            <button type="submit" class="btn btn-primary font-weight-semibold px-4 shadow-sm" style="border-radius: 8px;">
                                <i class="icon-checkmark4 mr-1"></i>{{ __('Save Changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
