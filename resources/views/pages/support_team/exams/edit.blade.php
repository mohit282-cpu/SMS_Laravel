@extends('layouts.master')
@section('page_title', __('Edit Examination — ') . $ex->name . ' (' . $ex->year . ')')
@section('content')

    {{-- Executive Header --}}
    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center">
                    <a href="{{ route('exams.index') }}" class="btn btn-light btn-icon mr-3 shadow-sm" style="border-radius: 10px;" title="{{ __('Back to Examinations List') }}">
                        <i class="icon-arrow-left7"></i>
                    </a>
                    <div>
                        <h3 class="font-weight-bold mb-1 text-dark" style="font-size: 1.35rem; letter-spacing: -0.01em;">
                            {{ __('Edit Examination') }}: {{ $ex->name }}
                        </h3>
                        <p class="text-muted mb-0 font-size-sm">
                            <span class="badge badge-primary mr-1">{{ __('Term') }} {{ $ex->term }}</span>
                            <span class="badge badge-light border text-muted mr-1">{{ $ex->year }}</span>
                            <span>{{ __('Update evaluation schedule, status, and details') }}</span>
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
                        <i class="icon-pencil5 mr-2 text-primary"></i>{{ __('Examination Details') }}
                    </h5>
                </div>

                <div class="card-body p-4">
                    <form method="post" action="{{ route('exams.update', $ex->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="form-group mb-3">
                            <label class="font-weight-semibold">{{ __('Examination Title') }}: <span class="text-danger">*</span></label>
                            <input name="name" value="{{ old('name', $ex->name) }}" required type="text" class="form-control" placeholder="{{ __('Name of Examination') }}">
                            <span class="form-text text-muted font-size-xs">{{ __('Official title displayed across report cards.') }}</span>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group mb-3">
                                <label class="font-weight-semibold">{{ __('Evaluation Type') }}:</label>
                                <select class="form-control select" name="exam_type">
                                    <option {{ old('exam_type', $ex->exam_type) == 'first_terminal' ? 'selected' : '' }} value="first_terminal">{{ __('First Terminal Examination (प्रथम त्रैमासिक)') }}</option>
                                    <option {{ old('exam_type', $ex->exam_type) == 'second_terminal' ? 'selected' : '' }} value="second_terminal">{{ __('Second Terminal Examination (दोस्रो त्रैमासिक)') }}</option>
                                    <option {{ old('exam_type', $ex->exam_type) == 'third_terminal' ? 'selected' : '' }} value="third_terminal">{{ __('Third Terminal Examination (तेस्रो त्रैमासिक)') }}</option>
                                    <option {{ old('exam_type', $ex->exam_type) == 'mid_term' ? 'selected' : '' }} value="mid_term">{{ __('Mid-Term Examination (मध्यसत्र)') }}</option>
                                    <option {{ old('exam_type', $ex->exam_type) == 'annual' ? 'selected' : '' }} value="annual">{{ __('Annual Examination (वार्षिक परीक्षा)') }}</option>
                                    <option {{ old('exam_type', $ex->exam_type) == 'unit_test' ? 'selected' : '' }} value="unit_test">{{ __('Unit Test / Continuous Assessment (एकाइ परीक्षा)') }}</option>
                                    <option {{ old('exam_type', $ex->exam_type) == 'practical' ? 'selected' : '' }} value="practical">{{ __('Practical / Project Evaluation (प्रयोगात्मक परीक्षा)') }}</option>
                                </select>
                            </div>

                            <div class="col-md-6 form-group mb-3">
                                <label for="term" class="font-weight-semibold">{{ __('Academic Term') }}: <span class="text-danger">*</span></label>
                                <select class="form-control select" name="term" id="term" required>
                                    <option {{ old('term', $ex->term) == 1 ? 'selected' : '' }} value="1">{{ __('Term 1 (प्रथम सत्र)') }}</option>
                                    <option {{ old('term', $ex->term) == 2 ? 'selected' : '' }} value="2">{{ __('Term 2 (दोस्रो सत्र)') }}</option>
                                    <option {{ old('term', $ex->term) == 3 ? 'selected' : '' }} value="3">{{ __('Term 3 (तेस्रो सत्र)') }}</option>
                                    <option {{ old('term', $ex->term) == 4 ? 'selected' : '' }} value="4">{{ __('Term 4 (चौथो सत्र)') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group mb-3">
                                <label class="font-weight-semibold">{{ __('Start Date (Bikram Sambat)') }}:</label>
                                <input name="start_date_bs" value="{{ old('start_date_bs', $ex->start_date_bs) }}" type="text" class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                            </div>

                            <div class="col-md-6 form-group mb-3">
                                <label class="font-weight-semibold">{{ __('End Date (Bikram Sambat)') }}:</label>
                                <input name="end_date_bs" value="{{ old('end_date_bs', $ex->end_date_bs) }}" type="text" class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-semibold">{{ __('Result Publication Status') }}:</label>
                            <select class="form-control select" name="status">
                                <option {{ old('status', $ex->status) == 'draft' ? 'selected' : '' }} value="draft">{{ __('Draft (मस्यौदा) — Hidden from students') }}</option>
                                <option {{ old('status', $ex->status) == 'scheduled' ? 'selected' : '' }} value="scheduled">{{ __('Scheduled (तालिकाबद्ध) — Calendar visible') }}</option>
                                <option {{ old('status', $ex->status) == 'ongoing' ? 'selected' : '' }} value="ongoing">{{ __('Ongoing (सञ्चालनमा) — Exams in progress') }}</option>
                                <option {{ old('status', $ex->status) == 'completed' ? 'selected' : '' }} value="completed">{{ __('Completed (सम्पन्न) — Marks entry active') }}</option>
                                <option {{ old('status', $ex->status) == 'published' ? 'selected' : '' }} value="published">{{ __('Published (प्रकाशित) — Results visible to parents & pupils') }}</option>
                            </select>
                        </div>

                        <div class="form-group mb-4">
                            <label class="font-weight-semibold">{{ __('Description / Notes') }}:</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="{{ __('Optional guidelines or remarks') }}">{{ old('description', $ex->description) }}</textarea>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-2">
                            <a href="{{ route('exams.index') }}" class="btn btn-light font-weight-semibold shadow-sm" style="border-radius: 8px;">
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
