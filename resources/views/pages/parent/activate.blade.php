@extends('layouts.login_master')
@section('content')
    <div class="page-content">
        <div class="content-wrapper">
            <div class="content d-flex justify-content-center align-items-center">
                <form class="login-form" method="POST" action="{{ route('parent.activate.submit', $token) }}" style="width: 100%; max-width: 480px;">
                    @csrf
                    <div class="card mb-0 shadow-lg" style="border-radius: 18px; border: 1px solid rgba(255, 87, 0, 0.2);">
                        <div class="card-body p-4">
                            <div class="text-center mb-3">
                                <div class="d-inline-flex align-items-center justify-content-center mb-2" style="width: 64px; height: 64px; border-radius: 50%; background: rgba(255, 87, 0, 0.1);">
                                    <i class="icon-shield-check icon-2x" style="color: #FF5700;"></i>
                                </div>
                                <h3 class="font-weight-bold mb-1" style="color: #1A1A1A;">{{ __('Parent Account Activation') }}</h3>
                                <span class="d-block text-muted">{{ __('Welcome to SMS Academy Parent Portal') }}</span>
                            </div>

                            <div class="alert alert-info border-0 py-2 px-3 mb-3" style="background: rgba(255, 87, 0, 0.08); border-left: 4px solid #FF5700 !important;">
                                <div class="font-weight-semibold text-dark">{{ $parent->name }}</div>
                                <div class="font-size-sm text-muted">
                                    @if($parent->phone) <span><i class="icon-phone mr-1"></i>{{ $parent->phone }}</span> @endif
                                    @if($parent->email) <span class="ml-2"><i class="icon-envelop mr-1"></i>{{ $parent->email }}</span> @endif
                                </div>
                            </div>

                            @if(isset($children) && $children->isNotEmpty())
                                <div class="mb-3">
                                    <label class="font-weight-bold text-dark font-size-xs text-uppercase mb-1">{{ __('Linked Student(s)') }}:</label>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($children as $ch)
                                            <span class="badge badge-light border py-1 px-2 mr-1 mb-1" style="font-size: 0.8rem;">
                                                <i class="icon-user text-primary mr-1"></i>{{ $ch->user->name }}
                                                <span class="text-muted">({{ $ch->my_class->name ?? '' }})</span>
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger py-2 px-3 mb-3">
                                    <ul class="mb-0 pl-3">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="form-group form-group-feedback form-group-feedback-left">
                                <label class="font-weight-semibold">{{ __('Create New Password') }}: <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="password" required placeholder="{{ __('Minimum 8 characters') }}" minlength="8" autocomplete="new-password">
                                <div class="form-control-feedback">
                                    <i class="icon-lock2 text-muted"></i>
                                </div>
                            </div>

                            <div class="form-group form-group-feedback form-group-feedback-left">
                                <label class="font-weight-semibold">{{ __('Confirm New Password') }}: <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="password_confirmation" required placeholder="{{ __('Confirm password') }}" minlength="8" autocomplete="new-password">
                                <div class="form-control-feedback">
                                    <i class="icon-lock2 text-muted"></i>
                                </div>
                            </div>

                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-block text-white font-weight-bold py-2" style="background: linear-gradient(135deg, #FF5700 0%, #E04800 100%); border: none; border-radius: 8px;">
                                    <i class="icon-checkmark4 mr-2"></i>{{ __('Activate Account & Sign In') }}
                                </button>
                            </div>

                            <div class="text-center font-size-sm">
                                <a href="{{ route('login') }}" class="text-muted"><i class="icon-arrow-left7 mr-1"></i>{{ __('Back to Login') }}</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
