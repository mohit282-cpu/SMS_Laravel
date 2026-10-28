@extends('layouts.master')
@section('page_title', __('My Account'))
@section('content')

    <div class="card">
        <div class="card-header header-elements-inline">
            <h2 class="card-title font-weight-bold mb-0" style="font-size: 1.1rem;">{{ __('My Account Settings') }}</h2>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <ul class="nav nav-tabs nav-tabs-highlight">
                <li class="nav-item"><a href="#change-pass" class="nav-link active" data-toggle="tab">{{ __('Password') }} {{ __('Update') }}</a></li>
                @if(Qs::userIsPTA())
                    <li class="nav-item"><a href="#edit-profile" class="nav-link" data-toggle="tab"><i class="icon-plus2"></i> {{ __('My Profile') }}</a></li>
                @endif
                <li class="nav-item"><a href="#pwa-notifications" class="nav-link" data-toggle="tab"><i class="icon-bell2 mr-1"></i> {{ __('Notifications') }}</a></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="change-pass">
                    <div class="row">
                        <div class="col-md-8">
                            <form method="post" action="{{ route('my_account.change_pass') }}">
                                @csrf @method('put')

                                <div class="form-group row">
                                    <label for="current_password" class="col-lg-3 col-form-label font-weight-semibold">{{ __('Current Password') }} <span class="text-danger">*</span></label>
                                    <div class="col-lg-9">
                                        <input id="current_password" name="current_password" required type="password" class="form-control" >
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="password" class="col-lg-3 col-form-label font-weight-semibold">{{ __('New Password') }} <span class="text-danger">*</span></label>
                                    <div class="col-lg-9">
                                        <input id="password" name="password" required type="password" class="form-control" >
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="password_confirmation" class="col-lg-3 col-form-label font-weight-semibold">{{ __('Confirm Password') }} <span class="text-danger">*</span></label>
                                    <div class="col-lg-9">
                                        <input id="password_confirmation" name="password_confirmation" required type="password" class="form-control" >
                                    </div>
                                </div>

                                <div class="text-right">
                                    <button type="submit" class="btn btn-danger">{{ __('Submit') }} <i class="icon-paperplane ml-2"></i></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @if(Qs::userIsPTA())
                    <div class="tab-pane fade" id="edit-profile">
                        <div class="row">
                            <div class="col-md-6">
                                <form enctype="multipart/form-data" method="post" action="{{ route('my_account.update') }}">
                                    @csrf @method('put')

                                    <div class="form-group row">
                                        <label for="name" class="col-lg-3 col-form-label font-weight-semibold">{{ __('Name') }}</label>
                                        <div class="col-lg-9">
                                            <input disabled="disabled" id="name" class="form-control" type="text" value="{{ $my->name }}">
                                        </div>
                                    </div>

                                    @if($my->username)
                                        <div class="form-group row">
                                            <label for="username" class="col-lg-3 col-form-label font-weight-semibold">{{ __('Username') }}</label>
                                            <div class="col-lg-9">
                                                <input disabled="disabled" id="username" class="form-control" type="text" value="{{ $my->username }}">
                                            </div>
                                        </div>

                                    @else

                                        <div class="form-group row">
                                            <label for="username" class="col-lg-3 col-form-label font-weight-semibold">{{ __('Username') }} </label>
                                            <div class="col-lg-9">
                                                <input id="username" name="username" type="text" class="form-control" >
                                            </div>
                                        </div>
                                    @endif

                                    <div class="form-group row">
                                        <label for="email" class="col-lg-3 col-form-label font-weight-semibold">{{ __('Email') }} </label>
                                        <div class="col-lg-9">
                                            <input id="email" value="{{ $my->email }}" name="email" type="email" class="form-control" >
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="phone" class="col-lg-3 col-form-label font-weight-semibold">{{ __('Phone') }} </label>
                                        <div class="col-lg-9">
                                            <input id="phone" value="{{ $my->phone }}" name="phone" type="text" class="form-control" >
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="phone2" class="col-lg-3 col-form-label font-weight-semibold">{{ __('Telephone') }} </label>
                                        <div class="col-lg-9">
                                            <input id="phone2" value="{{ $my->phone2 }}" name="phone2" type="text" class="form-control" >
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="address" class="col-lg-3 col-form-label font-weight-semibold">{{ __('Address') }} </label>
                                        <div class="col-lg-9">
                                            <input id="address" value="{{ $my->address }}" name="address" type="text" class="form-control" >
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="locale" class="col-lg-3 col-form-label font-weight-semibold">{{ __('Language') }} </label>
                                        <div class="col-lg-9">
                                            <select id="locale" name="locale" class="form-control select">
                                                <option value="ne" {{ (old('locale', $my->locale ?? app()->getLocale()) == 'ne') ? 'selected' : '' }}>{{ __('Nepali') }}</option>
                                                <option value="en" {{ (old('locale', $my->locale ?? app()->getLocale()) == 'en') ? 'selected' : '' }}>{{ __('English') }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Photo') }} </label>
                                        <div class="col-lg-9">
                                            <input accept="image/*" type="file" name="photo" class="form-input-styled" data-fouc>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <button type="submit" class="btn btn-danger">{{ __('Submit') }} <i class="icon-paperplane ml-2"></i></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="tab-pane fade" id="pwa-notifications">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card border-left-3 border-left-primary shadow-sm" id="pwa-push-card">
                                <div class="card-header header-elements-inline">
                                    <h3 class="card-title font-weight-semibold mb-0" style="font-size: 1rem;">{{ __('PWA Web Push Notifications') }}</h3>
                                    <div>
                                        <span class="badge badge-secondary mr-1" id="pwa-push-permission-badge">{{ __('Checking...') }}</span>
                                        <span class="badge badge-secondary" id="pwa-push-status-badge">{{ __('Checking...') }}</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p id="pwa-push-msg" class="text-muted">
                                        {{ __('Web push notifications allow you to receive timely school notices, calendar announcements, and library updates on this device.') }}
                                    </p>
                                    <div class="alert alert-info border-0 p-2 font-size-sm">
                                        <i class="icon-info22 mr-1"></i>
                                        {{ __('Notifications are device-specific. Enabling alerts here registers only this current browser/device.') }}
                                    </div>
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-primary" id="pwa-btn-enable-push">
                                            <i class="icon-bell mr-1"></i> {{ __('Enable Notifications') }}
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" id="pwa-btn-disable-push" style="display: none;">
                                            <i class="icon-bell-cross mr-1"></i> {{ __('Disable Notifications') }}
                                        </button>
                                        <button type="button" class="btn btn-outline-primary ml-2" id="pwa-btn-test-push" style="display: none;">
                                            <i class="icon-paperplane mr-1"></i> {{ __('Send Test Notification') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
