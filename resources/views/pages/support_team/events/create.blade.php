@extends('layouts.master')
@section('page_title', __('Create Event'))
@section('content')

    <div class="card shadow-sm border-0" style="border-radius: 16px;">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title font-weight-bold mb-0">
                <i class="icon-calendar-plus mr-2" style="color: #FF5700;"></i>{{ __('Create School Calendar Event (Bikram Sambat BS)') }}
            </h5>
            <a href="{{ route('events.index') }}" class="btn btn-outline-secondary btn-sm" style="border-radius: 8px;">
                <i class="icon-arrow-left52 mr-1"></i>{{ __('Back to Events') }}
            </a>
        </div>

        <div class="card-body p-4">
            <form method="POST" action="{{ route('events.store') }}">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-semibold text-dark">{{ __('Event Title (English)') }} <span class="text-danger">*</span></label>
                        <input type="text" name="title" required value="{{ old('title') }}" class="form-control" placeholder="e.g. First Term Terminal Examination">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-semibold text-dark">{{ __('Event Title (Nepali)') }}</label>
                        <input type="text" name="title_ne" value="{{ old('title_ne') }}" class="form-control" placeholder="उदा. प्रथम त्रैमासिक परीक्षा">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-semibold text-dark">{{ __('Event Category') }} <span class="text-danger">*</span></label>
                        <select name="category" required class="form-control">
                            @foreach($categories as $key => $c)
                                <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>
                                    {{ (app()->getLocale() === 'ne') ? $c['name_ne'] : $c['name_en'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-semibold text-dark">{{ __('Start Date (BS)') }} <span class="text-danger">*</span></label>
                        <input type="text" name="start_date_bs" required value="{{ old('start_date_bs', request('start_date_bs', $today_bs['bs_date'] ?? '')) }}" class="form-control nepali-date" placeholder="YYYY-MM-DD">
                        <small class="text-muted">{{ __('Select date using Bikram Sambat picker') }}</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-semibold text-dark">{{ __('End Date (BS)') }}</label>
                        <input type="text" name="end_date_bs" value="{{ old('end_date_bs', request('start_date_bs', $today_bs['bs_date'] ?? '')) }}" class="form-control nepali-date" placeholder="YYYY-MM-DD">
                        <small class="text-muted">{{ __('Leave blank or same for single day event') }}</small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-semibold text-dark">{{ __('Start Time') }}</label>
                        <input type="time" name="start_time" value="{{ old('start_time') }}" class="form-control">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-semibold text-dark">{{ __('End Time') }}</label>
                        <input type="time" name="end_time" value="{{ old('end_time') }}" class="form-control">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-semibold text-dark">{{ __('Academic Session') }}</label>
                        <select name="academic_year_id" class="form-control">
                            @foreach($academic_years as $ay)
                                <option value="{{ $ay->id }}" {{ ($active_year && $active_year->id === $ay->id) ? 'selected' : '' }}>
                                    {{ $ay->name }} ({{ $ay->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-semibold text-dark">{{ __('Target Audience') }} <span class="text-danger">*</span></label>
                        <select name="target_audience" id="target_audience" required class="form-control">
                            <option value="all" {{ old('target_audience') === 'all' ? 'selected' : '' }}>{{ __('Entire School') }}</option>
                            <option value="teachers" {{ old('target_audience') === 'teachers' ? 'selected' : '' }}>{{ __('Teachers Only') }}</option>
                            <option value="students" {{ old('target_audience') === 'students' ? 'selected' : '' }}>{{ __('Students Only') }}</option>
                            <option value="parents" {{ old('target_audience') === 'parents' ? 'selected' : '' }}>{{ __('Parents Only') }}</option>
                            <option value="classes" {{ old('target_audience') === 'classes' ? 'selected' : '' }}>{{ __('Specific Class / Section') }}</option>
                        </select>
                    </div>
                </div>

                <div class="row" id="class_target_row" style="{{ old('target_audience') === 'classes' ? '' : 'display: none;' }}">
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-semibold text-dark">{{ __('Target Class') }} <span class="text-danger">*</span></label>
                        <select name="my_class_id" id="my_class_id" class="form-control">
                            <option value="">{{ __('Select Class') }}</option>
                            @foreach($my_classes as $c)
                                <option value="{{ $c->id }}" {{ old('my_class_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-semibold text-dark">{{ __('Target Section (Optional)') }}</label>
                        <select name="section_id" id="section_id" class="form-control">
                            <option value="">{{ __('All Sections in Class') }}</option>
                            @foreach($sections as $s)
                                <option value="{{ $s->id }}" {{ old('section_id') == $s->id ? 'selected' : '' }}>{{ $s->name }} ({{ $s->my_class->name ?? '' }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-semibold text-dark">{{ __('Location / Venue') }}</label>
                        <input type="text" name="location" value="{{ old('location') }}" class="form-control" placeholder="e.g. Main Auditorium / Ground">
                    </div>
                    <div class="col-md-6 mb-3 d-flex align-items-center pt-3">
                        <div class="custom-control custom-checkbox mr-4">
                            <input type="checkbox" name="is_all_day" value="1" class="custom-control-input" id="is_all_day" {{ old('is_all_day', '1') ? 'checked' : '' }}>
                            <label class="custom-control-label font-weight-medium" for="is_all_day">{{ __('All-Day Event') }}</label>
                        </div>
                        <div class="custom-control custom-checkbox mr-4">
                            <input type="checkbox" name="is_published" value="1" class="custom-control-input" id="is_published" {{ old('is_published', '1') ? 'checked' : '' }}>
                            <label class="custom-control-label font-weight-semibold text-success" for="is_published">{{ __('Publish Immediately') }}</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="is_closure" value="1" class="custom-control-input" id="is_closure" {{ old('is_closure') ? 'checked' : '' }}>
                            <label class="custom-control-label font-weight-medium text-danger" for="is_closure">{{ __('School Closure (Holiday)') }}</label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-semibold text-dark">{{ __('Event Description') }}</label>
                        <textarea name="description" rows="4" class="form-control" placeholder="{{ __('Comprehensive description of the event...') }}">{{ old('description') }}</textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-semibold text-dark">{{ __('Instructions / Notes for Attendees') }}</label>
                        <textarea name="instructions" rows="4" class="form-control" placeholder="{{ __('Special instructions, dress code, items to bring, etc...') }}">{{ old('instructions') }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                    <a href="{{ route('events.index') }}" class="btn btn-light px-4">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn text-white px-5 font-weight-semibold" style="background: #FF5700; border-radius: 8px;">
                        <i class="icon-checkmark mr-1"></i> {{ __('Save & Publish Event') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#target_audience').on('change', function() {
            if ($(this).val() === 'classes') {
                $('#class_target_row').slideDown(150);
            } else {
                $('#class_target_row').slideUp(150);
            }
        });
    });
</script>
@endsection
