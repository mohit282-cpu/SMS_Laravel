@extends('layouts.master')
@section('page_title', __('Edit User'))
@section('content')

    <div class="card">
        <div class="card-header header-elements-inline">
            <h2 class="card-title font-weight-bold mb-0" style="font-size: 1.1rem;">
                <i class="icon-user-check mr-2" style="color: #FF5700;"></i>{{ __('Edit User Details') }} &bull; {{ $user->name }}
            </h2>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <form method="post" enctype="multipart/form-data" class="ajax-update" action="{{ route('users.update', Qs::hash($user->id)) }}">
                @csrf @method('PUT')
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="user_type" class="font-weight-semibold">{{ __('User Role') }}:</label>
                            <input disabled="disabled" class="form-control" id="user_type" value="{{ strtoupper(str_replace('_', ' ', $user->user_type)) }}">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Full Name') }}: <span class="text-danger">*</span></label>
                            <input value="{{ $user->name }}" required type="text" name="name" placeholder="{{ __('Full Name') }}" class="form-control">
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Address') }}: <span class="text-danger">*</span></label>
                            <input value="{{ $user->address }}" class="form-control" placeholder="{{ __('Address') }}" name="address" type="text" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Email Address') }}: </label>
                            <input value="{{ $user->email }}" type="email" name="email" class="form-control" placeholder="user@school.edu.np">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Mobile Phone') }}:</label>
                            <input value="{{ $user->phone }}" type="text" name="phone" class="form-control" placeholder="98XXXXXXXX">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Telephone / Alt Phone') }}:</label>
                            <input value="{{ $user->phone2 }}" type="text" name="phone2" class="form-control" placeholder="01-XXXXXXX">
                        </div>
                    </div>
                </div>

                <div class="row">
                    @if(in_array($user->user_type, Qs::getStaff()))
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-semibold">{{ __('Date of Employment') }}:</label>
                                <input autocomplete="off" name="emp_date" value="{{ $user->staff->first()->emp_date ?? '' }}" type="text" class="form-control date-pick" placeholder="{{ __('Select Date...') }}">
                            </div>
                        </div>
                    @endif

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="gender" class="font-weight-semibold">{{ __('Gender') }}: <span class="text-danger">*</span></label>
                            <select class="select form-control" id="gender" name="gender" required data-placeholder="{{ __('Choose...') }}">
                                <option value=""></option>
                                <option {{ ($user->gender == 'Male') ? 'selected' : '' }} value="Male">{{ __('Male') }}</option>
                                <option {{ ($user->gender == 'Female') ? 'selected' : '' }} value="Female">{{ __('Female') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="nal_id" class="font-weight-semibold">{{ __('Nationality') }}: <span class="text-danger">*</span></label>
                            <select data-placeholder="{{ __('Choose...') }}" required name="nal_id" id="nal_id" class="select-search form-control">
                                <option value=""></option>
                                @foreach($nationals as $nal)
                                    <option {{ ($user->nal_id == $nal->id) ? 'selected' : '' }} value="{{ $nal->id }}">{{ $nal->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Nepal Address Selector --}}
                <x-nepal-address-selector
                    :provinces="$provinces ?? null"
                    :provinceId="$user->province_id"
                    :districtId="$user->district_id"
                    :localLevelId="$user->local_level_id"
                    :wardNo="$user->ward_no"
                    :tole="$user->tole"
                    :street="$user->street"
                    :required="true"
                />

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="bg_id" class="font-weight-semibold">{{ __('Blood Group') }}: </label>
                            <select class="select form-control" id="bg_id" name="bg_id" data-placeholder="{{ __('Choose...') }}">
                                <option value=""></option>
                                @foreach($blood_groups as $bg)
                                    <option {{ ($user->bg_id == $bg->id ? 'selected' : '') }} value="{{ $bg->id }}">{{ $bg->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Passport Photo --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="d-block font-weight-semibold">{{ __('Upload New Photo') }}:</label>
                            <input value="{{ old('photo') }}" accept="image/*" type="file" name="photo" class="form-input-styled">
                            <span class="form-text text-muted">{{ __('Accepted: jpeg, png. Max file size: 2MB') }}</span>
                        </div>
                    </div>
                </div>

                <div class="text-right mt-3">
                    <button type="submit" class="btn btn-primary font-weight-semibold">
                        {{ __('Update User Details') }} <i class="icon-paperplane ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
