@extends('layouts.master')
@section('page_title', __('Admit Student'))
@section('content')
<div class="card sms-card">
    <div class="card-header header-elements-inline">
        <h2 class="card-title mb-0">{{ __('Admit New Student') }}</h2>
        {!! Qs::getPanelOptions() !!}
    </div>

    <form id="ajax-reg" method="post" enctype="multipart/form-data" class="wizard-form steps-validation" action="{{ route('students.store') }}" data-fouc>
        @csrf
        <h6>{{ __('Personal Data') }}</h6>
        <fieldset>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="student_name">{{ __('Full Name') }} <span class="sms-required-mark" aria-hidden="true">*</span></label>
                        <input id="student_name" value="{{ old('name') }}" required type="text" name="name" placeholder="{{ __('Full Name') }}" class="form-control">
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="student_address">{{ __('Address') }} <span class="sms-required-mark" aria-hidden="true">*</span></label>
                        <input id="student_address" value="{{ old('address') }}" class="form-control" placeholder="{{ __('Street / Municipality / Ward') }}" name="address" type="text" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="student_email">{{ __('Email Address') }}</label>
                        <input id="student_email" type="email" value="{{ old('email') }}" name="email" class="form-control" placeholder="student@example.com">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label for="gender">{{ __('Gender') }} <span class="sms-required-mark" aria-hidden="true">*</span></label>
                        <select class="select form-control" id="gender" name="gender" required data-fouc data-placeholder="{{ __('Choose..') }}">
                            <option value=""></option>
                            <option {{ (old('gender') == 'Male') ? 'selected' : '' }} value="Male">{{ __('Male') }}</option>
                            <option {{ (old('gender') == 'Female') ? 'selected' : '' }} value="Female">{{ __('Female') }}</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label for="student_phone">{{ __('Phone') }}</label>
                        <input id="student_phone" value="{{ old('phone') }}" type="text" name="phone" class="form-control" placeholder="{{ __('e.g. 98XXXXXXXX') }}">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label for="student_phone2">{{ __('Telephone') }}</label>
                        <input id="student_phone2" value="{{ old('phone2') }}" type="text" name="phone2" class="form-control" placeholder="{{ __('Optional landline') }}">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="student_dob">{{ __('Date of Birth (BS)') }}</label>
                        <input id="student_dob" name="dob" value="{{ old('dob') }}" type="text" class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                        <span class="form-text text-muted">{{ __('Nepali Bikram Sambat Date') }}</span>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label for="nal_id">{{ __('Nationality') }} <span class="sms-required-mark" aria-hidden="true">*</span></label>
                        <select data-placeholder="{{ __('Choose...') }}" required name="nal_id" id="nal_id" class="select-search form-control">
                            <option value=""></option>
                            @foreach($nationals as $nal)
                                <option {{ (old('nal_id') == $nal->id ? 'selected' : '') }} value="{{ $nal->id }}">{{ $nal->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label for="state_id">{{ __('Province / State') }} <span class="sms-required-mark" aria-hidden="true">*</span></label>
                        <select onchange="getLGA(this.value)" required data-placeholder="{{ __('Choose..') }}" class="select-search form-control" name="state_id" id="state_id">
                            <option value=""></option>
                            @foreach($states as $st)
                                <option {{ (old('state_id') == $st->id ? 'selected' : '') }} value="{{ $st->id }}">{{ $st->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label for="lga_id">{{ __('District / Local Level') }} <span class="sms-required-mark" aria-hidden="true">*</span></label>
                        <select required data-placeholder="{{ __('Select Province / State First') }}" class="select-search form-control" name="lga_id" id="lga_id">
                            <option value=""></option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="bg_id">{{ __('Blood Group') }}</label>
                        <select class="select form-control" id="bg_id" name="bg_id" data-fouc data-placeholder="{{ __('Choose..') }}">
                            <option value=""></option>
                            @foreach(($blood_groups ?? App\Models\BloodGroup::all()) as $bg)
                                <option {{ (old('bg_id') == $bg->id ? 'selected' : '') }} value="{{ $bg->id }}">{{ $bg->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="student_photo" class="d-block">{{ __('Upload Passport Photo') }}</label>
                        <input id="student_photo" accept="image/jpeg,image/png,image/gif" type="file" name="photo" class="form-input-styled" data-fouc>
                        <span class="form-text text-muted">{{ __('Accepted Images: jpeg, png, gif. Max file size 2MB') }}</span>
                    </div>
                </div>
            </div>
        </fieldset>

        <h6>{{ __('Student Data') }}</h6>
        <fieldset>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="my_class_id">{{ __('Class') }} <span class="sms-required-mark" aria-hidden="true">*</span></label>
                        <select onchange="getClassSections(this.value)" data-placeholder="{{ __('Choose...') }}" required name="my_class_id" id="my_class_id" class="select-search form-control">
                            <option value=""></option>
                            @foreach($my_classes as $c)
                                <option {{ (old('my_class_id') == $c->id ? 'selected' : '') }} value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="section_id">{{ __('Section') }} <span class="sms-required-mark" aria-hidden="true">*</span></label>
                        <select data-placeholder="{{ __('Select Class First') }}" required name="section_id" id="section_id" class="select-search form-control">
                            @if(old('section_id'))
                                <option selected value="{{ old('section_id') }}">{{ __('Selected') }}</option>
                            @else
                                <option value=""></option>
                            @endif
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="year_admitted">{{ __('Year Admitted') }} <span class="sms-required-mark" aria-hidden="true">*</span></label>
                        <select data-placeholder="{{ __('Choose...') }}" required name="year_admitted" id="year_admitted" class="select-search form-control">
                            <option value=""></option>
                            @for($y=date('Y', strtotime('- 10 years')); $y<=date('Y'); $y++)
                                <option {{ (old('year_admitted') == $y) ? 'selected' : '' }} value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="dorm_id">{{ __('Dormitory') }}</label>
                        <select data-placeholder="{{ __('Choose...') }}" name="dorm_id" id="dorm_id" class="select-search form-control">
                            <option value=""></option>
                            @foreach($dorms as $d)
                                <option {{ (old('dorm_id') == $d->id) ? 'selected' : '' }} value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label for="dorm_room_no">{{ __('Dormitory Room No') }}</label>
                        <input id="dorm_room_no" type="text" name="dorm_room_no" placeholder="{{ __('Dormitory Room No') }}" class="form-control" value="{{ old('dorm_room_no') }}">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label for="student_house">{{ __('Sport House') }}</label>
                        <input id="student_house" type="text" name="house" placeholder="{{ __('Sport House') }}" class="form-control" value="{{ old('house') }}">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label for="adm_no">{{ __('Admission Number') }}</label>
                        <input id="adm_no" type="text" name="adm_no" placeholder="{{ __('Optional alphanumeric ID') }}" class="form-control" value="{{ old('adm_no') }}">
                    </div>
                </div>
            </div>
        </fieldset>

        <h6>{{ __('Parent / Guardian') }}</h6>
        <fieldset>
            <div class="sms-info-callout mb-3">
                <h6 class="font-weight-bold mb-1">
                    <i class="icon-info22 mr-2" style="color: var(--sms-primary);" aria-hidden="true"></i>{{ __('Automatic Parent Portal Account Creation') }}
                </h6>
                <p class="mb-0 text-muted font-size-sm">
                    {{ __('Enter parent contact information to automatically provision a secure Parent Portal account with a single-use activation link. If a parent with this phone number or email is already registered, the student will automatically be linked to their account without creating duplicate identities.') }}
                </p>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="my_parent_id">{{ __('Link Existing Registered Parent') }}</label>
                        <select data-placeholder="{{ __('Choose Existing Parent...') }}" name="my_parent_id" id="my_parent_id" class="select-search form-control">
                            <option value=""></option>
                            @foreach($parents as $p)
                                <option {{ (old('my_parent_id') == Qs::hash($p->id)) ? 'selected' : '' }} value="{{ Qs::hash($p->id) }}">{{ $p->name }} ({{ $p->phone ?: $p->email }})</option>
                            @endforeach
                        </select>
                        <span class="form-text text-muted">{{ __('Select only if the parent is already registered in the system.') }}</span>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="parent_relationship">{{ __('Relationship to Student') }}</label>
                        <select data-placeholder="{{ __('Select Relationship...') }}" name="parent_relationship" id="parent_relationship" class="select form-control">
                            <option value=""></option>
                            <option {{ old('parent_relationship') == 'Father' ? 'selected' : '' }} value="Father">{{ __('Father') }}</option>
                            <option {{ old('parent_relationship') == 'Mother' ? 'selected' : '' }} value="Mother">{{ __('Mother') }}</option>
                            <option {{ old('parent_relationship') == 'Guardian' ? 'selected' : '' }} value="Guardian">{{ __('Guardian') }}</option>
                            <option {{ old('parent_relationship') == 'Other' ? 'selected' : '' }} value="Other">{{ __('Other') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="parent_name">{{ __('Parent Full Name') }}</label>
                        <input id="parent_name" type="text" name="parent_name" class="form-control" placeholder="{{ __('e.g. Ramesh Sharma') }}" value="{{ old('parent_name') }}">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="parent_phone">{{ __('Parent Mobile Number') }}</label>
                        <input id="parent_phone" type="text" name="parent_phone" class="form-control" placeholder="{{ __('e.g. 9841234567') }}" value="{{ old('parent_phone') }}">
                        <span class="form-text text-muted">{{ __('Normalized automatically. Used for parent login and SMS notifications.') }}</span>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="parent_email">{{ __('Parent Email Address') }}</label>
                        <input id="parent_email" type="email" name="parent_email" class="form-control" placeholder="parent@example.com" value="{{ old('parent_email') }}">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="parent_address">{{ __('Parent Address') }}</label>
                        <input id="parent_address" type="text" name="parent_address" class="form-control" placeholder="{{ __('Leave blank to inherit student address') }}" value="{{ old('parent_address') }}">
                    </div>
                </div>
            </div>
        </fieldset>
    </form>
</div>
@endsection
