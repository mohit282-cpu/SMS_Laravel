@extends('layouts.master')
@section('page_title', 'Manage System Settings')
@section('content')

    <div class="card">
        <div class="card-header header-elements-inline">
            <h6 class="card-title font-weight-semibold"><i class="icon-gear mr-2" style="color: var(--sms-primary);"></i>Update System Settings</h6>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <form enctype="multipart/form-data" method="post" action="{{ route('settings.update') }}">
                @csrf @method('PUT')
            <div class="row">
                <div class="col-md-6 border-right-2 border-right-blue-400">
                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label font-weight-semibold">Name of School <span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <input name="system_name" value="{{ $s['system_name'] }}" required type="text" class="form-control" placeholder="Name of School">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="current_session" class="col-lg-3 col-form-label font-weight-semibold">Current Session <span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <select data-placeholder="Choose..." required name="current_session" id="current_session" class="select-search form-control">
                                    <option value=""></option>
                                    @php
                                        $currentSessionVal = $s['current_session'] ?? '2083/84 BS';
                                        $academicYearsList = class_exists(\App\Models\AcademicYear::class) ? \App\Models\AcademicYear::orderBy('year_bs', 'desc')->pluck('name')->toArray() : [];
                                        $presetSessions = array_unique(array_merge(
                                            $academicYearsList,
                                            ['2083/84 BS', '2082/83 BS', '2081/82 BS', '2080/81 BS'],
                                            [$currentSessionVal]
                                        ));
                                    @endphp
                                    @foreach($presetSessions as $sess)
                                        <option value="{{ $sess }}" {{ $currentSessionVal == $sess ? 'selected' : '' }}>{{ $sess }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label font-weight-semibold">School Acronym</label>
                            <div class="col-lg-9">
                                <input name="system_title" value="{{ $s['system_title'] }}" type="text" class="form-control" placeholder="School Acronym">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label font-weight-semibold">Phone</label>
                            <div class="col-lg-9">
                                <input name="phone" value="{{ $s['phone'] }}" type="text" class="form-control" placeholder="Phone">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label font-weight-semibold">School Email</label>
                            <div class="col-lg-9">
                                <input name="system_email" value="{{ $s['system_email'] }}" type="email" class="form-control" placeholder="School Email">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label font-weight-semibold">School Address <span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <input required name="address" value="{{ $s['address'] }}" type="text" class="form-control" placeholder="School Address">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label font-weight-semibold">This Term Ends</label>
                            <div class="col-lg-6">
                                <input name="term_ends" value="{{ $s['term_ends'] }}" type="text" class="form-control date-pick" placeholder="Date Term Ends">
                            </div>
                            <div class="col-lg-3 mt-2">
                                <span class="font-weight-bold font-italic">M-D-Y or M/D/Y </span>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label font-weight-semibold">Next Term Begins</label>
                            <div class="col-lg-6">
                                <input name="term_begins" value="{{ $s['term_begins'] }}" type="text" class="form-control date-pick" placeholder="Date Term Ends">
                            </div>
                            <div class="col-lg-3 mt-2">
                                <span class="font-weight-bold font-italic">M-D-Y or M/D/Y </span>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="lock_exam" class="col-lg-3 col-form-label font-weight-semibold">Lock Exam</label>
                            <div class="col-lg-3">
                                <select class="form-control select" name="lock_exam" id="lock_exam">
                                    <option {{ $s['lock_exam'] ? 'selected' : '' }} value="1">Yes</option>
                                    <option {{ $s['lock_exam'] ?: 'selected' }} value="0">No</option>
                                </select>
                            </div>
                            <div class="col-lg-6">
                                    <span class="font-weight-bold font-italic text-info-800">{{ __('msg.lock_exam') }}</span>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="calendar_display" class="col-lg-3 col-form-label font-weight-semibold">Calendar Display</label>
                            <div class="col-lg-5">
                                <select class="form-control select" name="calendar_display" id="calendar_display">
                                    <option {{ ($s['calendar_display'] ?? 'both') == 'both' ? 'selected' : '' }} value="both">Both (BS & AD Dual Display)</option>
                                    <option {{ ($s['calendar_display'] ?? '') == 'bs' ? 'selected' : '' }} value="bs">Bikram Sambat (BS Only)</option>
                                    <option {{ ($s['calendar_display'] ?? '') == 'ad' ? 'selected' : '' }} value="ad">Gregorian (AD Only)</option>
                                </select>
                            </div>
                            <div class="col-lg-4">
                                <span class="font-weight-bold font-italic text-info-800">System-wide default date display mode</span>
                            </div>
                        </div>
                </div>
                <div class="col-md-6">
                    {{--Fees--}}
               <fieldset>
                   <legend><strong>Next Term Fees</strong></legend>
                   @foreach($class_types as $ct)
                   <div class="form-group row">
                       <label class="col-lg-3 col-form-label font-weight-semibold">{{ $ct->name }}</label>
                       <div class="col-lg-9">
                           <input class="form-control" value="{{ $s['next_term_fees_'.strtolower($ct->code)] }}" name="next_term_fees_{{ strtolower($ct->code) }}" placeholder="{{ $ct->name }}" type="text">
                       </div>
                   </div>
                       @endforeach
               </fieldset>
                    <hr class="divider">

                    {{--Logo--}}
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label font-weight-semibold">Change Logo:</label>
                        <div class="col-lg-9">
                            <div class="mb-3">
                                <img style="width: 100px" height="100px" src="{{ $s['logo'] }}" alt="">
                            </div>
                            <input name="logo" accept="image/*" type="file" class="file-input" data-show-caption="false" data-show-upload="false" data-fouc>
                        </div>
                    </div>
                </div>
            </div>

                <hr class="divider">

                <div class="text-right">
                    <button type="submit" class="btn btn-primary font-weight-semibold px-4 py-2">Save Settings <i class="icon-checkmark4 ml-2"></i></button>
                </div>
            </form>
        </div>
    </div>

    {{--Settings Edit Ends--}}

@endsection
