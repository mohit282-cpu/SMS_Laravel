@extends('layouts.master')
@section('page_title', __('Admit Student'))
@section('content')

    {{-- Executive Header with Dual Date & Academic Session --}}
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-3 border-bottom">
        <div>
            <div class="d-flex flex-wrap align-items-center mb-1 gap-2">
                <span class="badge badge-primary mr-2">
                    <i class="icon-user-plus mr-1" aria-hidden="true"></i> {{ __('Student Admission') }}
                </span>
                <span class="badge badge-light border text-dark mr-2">
                    <i class="icon-calendar22 mr-1 text-primary" aria-hidden="true"></i> {{ \App\Helpers\NepaliHelper::formatDualDate(date('Y-m-d')) }}
                </span>
                <span class="badge badge-light border text-muted">
                    <i class="icon-graduation2 mr-1 text-secondary" aria-hidden="true"></i> {{ __('Session') }}: <strong>{{ Qs::getCurrentSession() }}</strong>
                </span>
            </div>
            <h2 class="font-weight-bold text-dark mb-0" style="font-size: 1.35rem;">{{ __('Admit New Student') }}</h2>
        </div>
        <div class="d-flex align-items-center flex-wrap mt-3 mt-md-0" style="gap: 0.5rem;">
            <a href="{{ route('students.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="icon-users4 mr-1" aria-hidden="true"></i> {{ __('Student Directory') }}
            </a>
            <a href="{{ route('students.promotion') }}" class="btn btn-sm btn-outline-secondary">
                <i class="icon-stairs-up mr-1" aria-hidden="true"></i> {{ __('Promotions') }}
            </a>
            <a href="{{ route('students.graduated') }}" class="btn btn-sm btn-outline-secondary">
                <i class="icon-graduation mr-1" aria-hidden="true"></i> {{ __('Graduates') }}
            </a>
        </div>
    </div>

    {{-- Main Admission Card --}}
    <div class="card sms-card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between">
            <div>
                <h5 class="font-weight-bold mb-0 text-dark">
                    <i class="icon-user-plus mr-2 text-primary" aria-hidden="true"></i>{{ __('Student Enrollment & Registration Form') }}
                </h5>
                <span class="text-muted font-size-xs">{{ __('Complete the 4 enrollment sections below. Required fields are marked with (*).') }}</span>
            </div>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            {{-- Step Navigation Pills --}}
            <ul class="nav nav-pills nav-pills-bordered mb-4" id="admission-steps" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active font-weight-semibold py-2 px-3" id="tab-personal" data-toggle="pill" href="#step-personal" role="tab">
                        <span class="badge badge-light border mr-2 font-weight-bold">1</span>
                        <i class="icon-user mr-1 text-primary" aria-hidden="true"></i> {{ __('Personal Data') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link font-weight-semibold py-2 px-3" id="tab-academic" data-toggle="pill" href="#step-academic" role="tab">
                        <span class="badge badge-light border mr-2 font-weight-bold">2</span>
                        <i class="icon-graduation2 mr-1 text-success" aria-hidden="true"></i> {{ __('Academic & Class') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link font-weight-semibold py-2 px-3" id="tab-address" data-toggle="pill" href="#step-address" role="tab">
                        <span class="badge badge-light border mr-2 font-weight-bold">3</span>
                        <i class="icon-location4 mr-1 text-warning" aria-hidden="true"></i> {{ __('Nepal Address') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link font-weight-semibold py-2 px-3" id="tab-parent" data-toggle="pill" href="#step-parent" role="tab">
                        <span class="badge badge-light border mr-2 font-weight-bold">4</span>
                        <i class="icon-users4 mr-1 text-info" aria-hidden="true"></i> {{ __('Parent / Guardian') }}
                    </a>
                </li>
            </ul>

            <form id="ajax-reg" method="post" enctype="multipart/form-data" class="ajax-store" action="{{ route('students.store') }}">
                @csrf

                <div class="tab-content" id="admission-steps-content">
                    {{-- =========================================================
                         STEP 1: PERSONAL DATA
                         ========================================================= --}}
                    <div class="tab-pane fade show active" id="step-personal" role="tabpanel">
                        <div class="p-3 mb-3 rounded" style="background-color: var(--sms-surface-subtle, #f8fafc); border: 1px solid var(--sms-border-strong, #e2e8f0);">
                            <h6 class="font-weight-bold text-dark mb-0">
                                <i class="icon-user mr-2 text-primary" aria-hidden="true"></i>{{ __('Student Identity & Biodata') }}
                            </h6>
                            <span class="text-muted font-size-xs">{{ __('Basic identification credentials for student record.') }}</span>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="student_name" class="font-weight-semibold">{{ __('Full Name') }} <span class="text-danger">*</span></label>
                                    <input id="student_name" value="{{ old('name') }}" required minlength="6" type="text" name="name" placeholder="{{ __('e.g. Subin Shrestha') }}" class="form-control">
                                    <span class="form-text text-muted small">{{ __('Minimum 6 characters.') }}</span>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="gender" class="font-weight-semibold">{{ __('Gender') }} <span class="text-danger">*</span></label>
                                    <select class="select form-control" id="gender" name="gender" required data-placeholder="{{ __('Choose Gender...') }}">
                                        <option value=""></option>
                                        <option {{ (old('gender') == 'Male') ? 'selected' : '' }} value="Male">{{ __('Male') }}</option>
                                        <option {{ (old('gender') == 'Female') ? 'selected' : '' }} value="Female">{{ __('Female') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="student_dob" class="font-weight-semibold">{{ __('Date of Birth (BS)') }}</label>
                                    <input id="student_dob" name="dob" value="{{ old('dob') }}" type="text" class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                                    <span class="form-text text-muted small">{{ __('Bikram Sambat (e.g. 2065-04-12)') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="student_email" class="font-weight-semibold">{{ __('Email Address') }}</label>
                                    <input id="student_email" type="email" value="{{ old('email') }}" name="email" class="form-control" placeholder="student@example.com">
                                    <span class="form-text text-muted small">{{ __('Optional. Used for portal login if provided.') }}</span>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="student_phone" class="font-weight-semibold">{{ __('Mobile Phone') }}</label>
                                    <input id="student_phone" value="{{ old('phone') }}" type="text" name="phone" class="form-control" placeholder="{{ __('e.g. 98XXXXXXXX') }}">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="nal_id" class="font-weight-semibold">{{ __('Nationality') }} <span class="text-danger">*</span></label>
                                    @php
                                        $defaultNalId = old('nal_id');
                                        if (!$defaultNalId && isset($nationals)) {
                                            $nepalNal = $nationals->first(function ($n) {
                                                return stripos($n->name, 'nepal') !== false;
                                            });
                                            $defaultNalId = $nepalNal ? $nepalNal->id : null;
                                        }
                                    @endphp
                                    <select data-placeholder="{{ __('Select Nationality...') }}" required name="nal_id" id="nal_id" class="select-search form-control">
                                        <option value=""></option>
                                        @foreach($nationals as $nal)
                                            <option {{ ((string) $defaultNalId === (string) $nal->id ? 'selected' : '') }} value="{{ $nal->id }}">{{ $nal->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="bg_id" class="font-weight-semibold">{{ __('Blood Group') }}</label>
                                    <select class="select form-control" id="bg_id" name="bg_id" data-placeholder="{{ __('Choose Blood Group...') }}">
                                        <option value=""></option>
                                        @foreach(($blood_groups ?? App\Models\BloodGroup::all()) as $bg)
                                            <option {{ (old('bg_id') == $bg->id ? 'selected' : '') }} value="{{ $bg->id }}">{{ $bg->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="student_photo" class="font-weight-semibold d-block">{{ __('Passport Photograph') }}</label>
                                    <input id="student_photo" accept="image/jpeg,image/png,image/gif,image/jpg" type="file" name="photo" class="form-control-file border p-1 rounded">
                                    <span class="form-text text-muted small">{{ __('Accepted: JPG, PNG, GIF. Maximum size 2MB.') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="text-right pt-3 border-top mt-3">
                            <button type="button" class="btn btn-primary px-4 btn-next-step" data-target="#tab-academic">
                                {{ __('Next: Academic Details') }} <i class="icon-arrow-right14 ml-2" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    {{-- =========================================================
                         STEP 2: ACADEMIC & CLASS DETAILS
                         ========================================================= --}}
                    <div class="tab-pane fade" id="step-academic" role="tabpanel">
                        <div class="p-3 mb-3 rounded" style="background-color: var(--sms-surface-subtle, #f8fafc); border: 1px solid var(--sms-border-strong, #e2e8f0);">
                            <h6 class="font-weight-bold text-dark mb-0">
                                <i class="icon-graduation2 mr-2 text-success" aria-hidden="true"></i>{{ __('Class Enrollment & Academic Placement') }}
                            </h6>
                            <span class="text-muted font-size-xs">{{ __('Assign grade, section, admission year, and institutional identifiers.') }}</span>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="my_class_id" class="font-weight-semibold">{{ __('Class / Grade') }} <span class="text-danger">*</span></label>
                                    <select onchange="getClassSections(this.value)" data-placeholder="{{ __('Select Class...') }}" required name="my_class_id" id="my_class_id" class="select-search form-control">
                                        <option value=""></option>
                                        @foreach($my_classes as $c)
                                            <option {{ (old('my_class_id') == $c->id ? 'selected' : '') }} value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="section_id" class="font-weight-semibold">{{ __('Section') }} <span class="text-danger">*</span></label>
                                    <select data-placeholder="{{ __('Select Class First') }}" required name="section_id" id="section_id" class="select-search form-control">
                                        @if(old('section_id'))
                                            <option selected value="{{ old('section_id') }}">{{ __('Selected') }}</option>
                                        @else
                                            <option value=""></option>
                                        @endif
                                    </select>
                                    <span class="form-text text-muted small">{{ __('Sections load automatically based on selected class.') }}</span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="year_admitted" class="font-weight-semibold">{{ __('Year Admitted') }} <span class="text-danger">*</span></label>
                                    <select data-placeholder="{{ __('Select Year...') }}" required name="year_admitted" id="year_admitted" class="select-search form-control">
                                        <option value=""></option>
                                        @php $currentYear = (int)date('Y'); @endphp
                                        @for($y = $currentYear - 10; $y <= $currentYear + 1; $y++)
                                            <option {{ (old('year_admitted', $currentYear) == $y) ? 'selected' : '' }} value="{{ $y }}">{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="adm_no" class="font-weight-semibold">{{ __('Custom Admission Number') }}</label>
                                    <input id="adm_no" type="text" name="adm_no" placeholder="{{ __('e.g. ADM-2026-001') }}" class="form-control" value="{{ old('adm_no') }}">
                                    <span class="form-text text-muted small">{{ __('Optional. Leave blank to auto-generate official student ID.') }}</span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="student_house" class="font-weight-semibold">{{ __('Sport House / Club') }}</label>
                                    <input id="student_house" type="text" name="house" placeholder="{{ __('e.g. Red House, Blue House') }}" class="form-control" value="{{ old('house') }}">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="dorm_id" class="font-weight-semibold">{{ __('Dormitory (Optional)') }}</label>
                                    <select data-placeholder="{{ __('Select Dormitory...') }}" name="dorm_id" id="dorm_id" class="select-search form-control">
                                        <option value=""></option>
                                        @foreach($dorms as $d)
                                            <option {{ (old('dorm_id') == $d->id) ? 'selected' : '' }} value="{{ $d->id }}">{{ $d->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between pt-3 border-top mt-3">
                            <button type="button" class="btn btn-secondary px-3 btn-prev-step" data-target="#tab-personal">
                                <i class="icon-arrow-left13 mr-2" aria-hidden="true"></i> {{ __('Previous') }}
                            </button>
                            <button type="button" class="btn btn-primary px-4 btn-next-step" data-target="#tab-address">
                                {{ __('Next: Nepal Address') }} <i class="icon-arrow-right14 ml-2" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    {{-- =========================================================
                         STEP 3: NEPAL LOCATION & ADDRESS
                         ========================================================= --}}
                    <div class="tab-pane fade" id="step-address" role="tabpanel">
                        <div class="p-3 mb-3 rounded" style="background-color: var(--sms-surface-subtle, #f8fafc); border: 1px solid var(--sms-border-strong, #e2e8f0);">
                            <h6 class="font-weight-bold text-dark mb-0">
                                <i class="icon-location4 mr-2 text-warning" aria-hidden="true"></i>{{ __('Nepal Federal Location & Address') }}
                            </h6>
                            <span class="text-muted font-size-xs">{{ __('Province, District, Local Government, Ward, and Tole.') }}</span>
                        </div>

                        <div class="form-group mb-3">
                            <label for="student_address" class="font-weight-semibold">{{ __('Street Address / Tole') }} <span class="text-danger">*</span></label>
                            <input id="student_address" value="{{ old('address') }}" class="form-control" placeholder="{{ __('e.g. Shanti Chowk, Ward 4, Itahari') }}" name="address" type="text" required>
                            <span class="form-text text-muted small">{{ __('Full residential location details.') }}</span>
                        </div>

                        {{-- Nepal Federal Address Component --}}
                        <x-nepal-address-selector :provinces="$provinces ?? null" :required="true" />

                        <div class="d-flex justify-content-between pt-3 border-top mt-3">
                            <button type="button" class="btn btn-secondary px-3 btn-prev-step" data-target="#tab-academic">
                                <i class="icon-arrow-left13 mr-2" aria-hidden="true"></i> {{ __('Previous') }}
                            </button>
                            <button type="button" class="btn btn-primary px-4 btn-next-step" data-target="#tab-parent">
                                {{ __('Next: Parent / Guardian') }} <i class="icon-arrow-right14 ml-2" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    {{-- =========================================================
                         STEP 4: PARENT / GUARDIAN LINKING
                         ========================================================= --}}
                    <div class="tab-pane fade" id="step-parent" role="tabpanel">
                        <div class="p-3 mb-3 rounded" style="background-color: var(--sms-surface-subtle, #f8fafc); border: 1px solid var(--sms-border-strong, #e2e8f0);">
                            <h6 class="font-weight-bold text-dark mb-0">
                                <i class="icon-users4 mr-2 text-info" aria-hidden="true"></i>{{ __('Parent / Guardian Account Linking') }}
                            </h6>
                            <span class="text-muted font-size-xs">{{ __('Connect this student to an existing parent account or automatically provision a new Parent Portal profile.') }}</span>
                        </div>

                        <div class="alert alert-info border-0 mb-4">
                            <i class="icon-info22 mr-2"></i>
                            <strong>{{ __('Automatic Parent Portal Provisioning') }}:</strong>
                            {{ __('If you enter a parent phone or email, our system automatically links or creates a dedicated Parent Portal user account with SMS notification capability.') }}
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="my_parent_id" class="font-weight-semibold">{{ __('Link Existing Registered Parent (Optional)') }}</label>
                                    <select data-placeholder="{{ __('Choose Existing Parent...') }}" name="my_parent_id" id="my_parent_id" class="select-search form-control">
                                        <option value=""></option>
                                        @foreach($parents as $p)
                                            <option {{ (old('my_parent_id') == Qs::hash($p->id)) ? 'selected' : '' }} value="{{ Qs::hash($p->id) }}">
                                                {{ $p->name }} ({{ $p->phone ?: $p->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="form-text text-muted small">{{ __('Select only if the parent is already registered in the system.') }}</span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="parent_relationship" class="font-weight-semibold">{{ __('Relationship to Student') }}</label>
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

                        <div class="border-top pt-3 mt-2 mb-3">
                            <h6 class="font-weight-bold text-dark mb-1">{{ __('Or Register New Parent Details') }}</h6>
                            <span class="text-muted font-size-xs d-block mb-3">{{ __('Fill in parent details below to automatically generate their credentials.') }}</span>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="parent_name" class="font-weight-semibold">{{ __('Parent Full Name') }}</label>
                                        <input id="parent_name" type="text" name="parent_name" class="form-control" placeholder="{{ __('e.g. Ramesh Sharma') }}" value="{{ old('parent_name') }}">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="parent_phone" class="font-weight-semibold">{{ __('Parent Mobile Number') }}</label>
                                        <input id="parent_phone" type="text" name="parent_phone" class="form-control" placeholder="{{ __('e.g. 9841234567') }}" value="{{ old('parent_phone') }}">
                                        <span class="form-text text-muted small">{{ __('Used for SMS notifications & login.') }}</span>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="parent_email" class="font-weight-semibold">{{ __('Parent Email Address') }}</label>
                                        <input id="parent_email" type="email" name="parent_email" class="form-control" placeholder="parent@example.com" value="{{ old('parent_email') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="parent_address" class="font-weight-semibold">{{ __('Parent Permanent Address') }}</label>
                                <input id="parent_address" type="text" name="parent_address" class="form-control" placeholder="{{ __('Leave blank to inherit student address') }}" value="{{ old('parent_address') }}">
                            </div>
                        </div>

                        <div class="d-flex flex-wrap align-items-center justify-content-between pt-3 border-top mt-4">
                            <button type="button" class="btn btn-secondary px-3 btn-prev-step" data-target="#tab-address">
                                <i class="icon-arrow-left13 mr-2" aria-hidden="true"></i> {{ __('Previous') }}
                            </button>
                            <button id="ajax-btn" type="submit" class="btn text-white px-5 py-2 font-weight-bold" style="background: #FF5700; border-radius: 8px; font-size: 1.05rem;">
                                <i class="icon-check mr-2" aria-hidden="true"></i> {{ __('Complete Student Admission') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Step navigation button handlers
        $('.btn-next-step, .btn-prev-step').on('click', function(e) {
            e.preventDefault();
            var targetTab = $(this).data('target');
            if (targetTab) {
                $(targetTab).tab('show');
                $('html, body').animate({
                    scrollTop: $('.sms-card').offset().top - 20
                }, 300);
            }
        });

        // Trigger class sections if class already selected
        var selectedClassId = $('#my_class_id').val();
        if (selectedClassId) {
            getClassSections(selectedClassId);
        }

        $('#my_class_id').on('change', function() {
            var cid = $(this).val();
            if (cid) {
                getClassSections(cid);
            }
        });
    });
</script>
@endsection
