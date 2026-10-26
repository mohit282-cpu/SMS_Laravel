@extends('layouts.master')
@section('page_title', __('Edit Academic Year (BS)') . ' - ' . $ay->name)
@section('content')

    <div class="card">
        <div class="card-header header-elements-inline">
            <h6 class="card-title font-weight-bold">{{ __('Edit Nepali Academic Session (BS)') }} - {{ $ay->name }}</h6>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <form method="post" action="{{ route('academic_years.update', $ay->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Session Name') }}: <span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <input name="name" value="{{ old('name', $ay->name) }}" required type="text" class="form-control" placeholder="e.g. 2083/84 BS">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Academic Year (BS)') }}: <span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <input name="year_bs" value="{{ old('year_bs', $ay->year_bs) }}" required type="number" min="2000" max="2090" class="form-control" placeholder="2083">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Start Date (BS)') }}: <span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <input name="start_date_bs" value="{{ old('start_date_bs', $ay->start_date_bs) }}" required type="text" class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                                <span class="form-text text-muted">{{ __('First day of Baishakh') }} ({{ $ay->start_date ? $ay->start_date->format('Y-m-d') : '' }} AD)</span>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('End Date (BS)') }}: <span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <input name="end_date_bs" value="{{ old('end_date_bs', $ay->end_date_bs) }}" required type="text" class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                                <span class="form-text text-muted">{{ __('Last day of Chaitra') }} ({{ $ay->end_date ? $ay->end_date->format('Y-m-d') : '' }} AD)</span>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Description') }}:</label>
                            <div class="col-lg-9">
                                <textarea name="description" class="form-control" rows="3" placeholder="{{ __('Optional notes on session') }}">{{ old('description', $ay->description) }}</textarea>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Set as Active Session') }}:</label>
                            <div class="col-lg-9">
                                <div class="form-check">
                                    <label class="form-check-label">
                                        <input type="checkbox" name="is_active" value="1" class="form-check-input-styled" {{ old('is_active', $ay->is_active) ? 'checked' : '' }} data-fouc>
                                        {{ __('Make this session active for student admissions, marks, and fees') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="text-right">
                            <a href="{{ route('academic_years.index') }}" class="btn btn-secondary mr-2">{{ __('Cancel') }}</a>
                            <button type="submit" class="btn btn-primary">{{ __('Update Session') }} <i class="icon-paperplane ml-2"></i></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
