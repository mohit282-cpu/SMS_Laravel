@extends('layouts.master')
@section('page_title', __('Landing Page & Admissions Management'))

@section('content')

    {{-- Executive Header --}}
    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center">
                    <div class="d-inline-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px; border-radius: 14px; background: rgba(255, 87, 0, 0.12);">
                        <i class="icon-browser icon-2x" style="color: #FF5700;"></i>
                    </div>
                    <div>
                        <h3 class="font-weight-bold mb-1 text-dark" style="font-size: 1.35rem; letter-spacing: -0.01em;">
                            {{ __('Landing Page & Admissions Management') }}
                        </h3>
                        <p class="text-muted mb-0 font-size-sm">
                            {{ __('Public school portal configuration, admissions banner toggles, leadership bios & entrance portal') }}
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
                    <a href="{{ route('landing.home') }}" target="_blank" class="btn btn-outline-dark btn-sm font-weight-semibold shadow-sm ml-md-2" style="border-radius: 8px;">
                        <i class="icon-eye mr-1"></i> {{ __('View Public Website') }} ↗
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- 4 Metric KPI Stat Cards --}}
    <div class="row mb-4">
        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #f59e0b !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Pending Inquiries') }}</span>
                            <h3 class="font-weight-bold text-warning mb-0 mt-1">{{ number_format($inquiryCount) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Awaiting follow-up') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1);">
                            <i class="icon-enquiry icon-2x" style="color: #f59e0b;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #06b6d4 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Total Inquiries') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($totalInquiries) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Admissions applications') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(6, 182, 212, 0.1);">
                            <i class="icon-users4 icon-2x" style="color: #06b6d4;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #4f46e5 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Entrance Results') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($totalEntranceResults) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Across :count batches', ['count' => $batchesCount]) }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(79, 70, 229, 0.1);">
                            <i class="icon-file-check icon-2x" style="color: #4f46e5;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            @php $isOpen = ($settings['admissions_open'] ?? '1') === '1'; @endphp
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid {{ $isOpen ? '#10b981' : '#ef4444' }} !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Admissions Status') }}</span>
                            <h3 class="font-weight-bold {{ $isOpen ? 'text-success' : 'text-danger' }} mb-0 mt-1">{{ $isOpen ? __('OPEN') : __('CLOSED') }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Public homepage banner') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: {{ $isOpen ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)' }};">
                            <i class="icon-megaphone icon-2x" style="color: {{ $isOpen ? '#10b981' : '#ef4444' }};"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Navigation Bar --}}
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
        <div class="card-body p-2 p-md-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ route('landing_settings.index') }}" class="btn text-white btn-sm font-weight-semibold mr-2" style="background: #FF5700; border-radius: 8px;">
                    <i class="icon-gear mr-1"></i> {{ __('General & Admissions Controls') }}
                </a>
                <a href="{{ route('landing_settings.inquiries') }}" class="btn btn-light border btn-sm mr-2 font-weight-semibold" style="border-radius: 8px;">
                    <i class="icon-bubbles4 mr-1 text-info"></i> {{ __('View Inquiries') }}
                    <span class="badge badge-info ml-1">{{ $totalInquiries }}</span>
                </a>
                <a href="{{ route('landing_settings.entrance_results') }}" class="btn btn-light border btn-sm font-weight-semibold" style="border-radius: 8px;">
                    <i class="icon-file-text2 mr-1 text-primary"></i> {{ __('Manage Entrance Exam Results') }}
                    <span class="badge badge-primary ml-1">{{ $totalEntranceResults }}</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Settings Editor Form --}}
    <div class="card shadow-sm border-0" style="border-radius: 14px;">
        <div class="card-header d-flex align-items-center justify-content-between bg-white border-bottom py-3">
            <h5 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.05rem;">
                <i class="icon-browser mr-2" style="color: #FF5700;"></i> {{ __('Public School Website & Admissions Settings') }}
            </h5>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body p-3 p-md-4">
            <form method="post" action="{{ route('landing_settings.update') }}">
                @csrf

                {{-- Section 1: Admissions Banner Status --}}
                <div class="border-bottom pb-4 mb-4">
                    <h6 class="font-weight-bold text-dark mb-2" style="font-size: 1rem;">
                        <i class="icon-bell2 mr-1 text-warning"></i> {{ __('Public Admissions Banner Status') }}
                    </h6>
                    <p class="text-muted font-size-sm mb-3">
                        {{ __('Control whether the "Admissions Open for Academic Session 2083/84 B.S." announcement banner and CTA buttons are displayed on the public homepage.') }}
                    </p>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="custom-control custom-radio custom-control-inline p-3 border rounded w-100 mb-2 {{ ($settings['admissions_open'] ?? '1') === '1' ? 'border-success bg-light' : '' }}">
                                <input type="radio" id="adm_open" name="admissions_open" value="1" class="custom-control-input" {{ ($settings['admissions_open'] ?? '1') === '1' ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold text-success cursor-pointer w-100" for="adm_open">
                                    {{ __('Admissions OPEN (Show Banner on Homepage & Enable Inquiries)') }}
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="custom-control custom-radio custom-control-inline p-3 border rounded w-100 mb-2 {{ ($settings['admissions_open'] ?? '1') === '0' ? 'border-danger bg-light' : '' }}">
                                <input type="radio" id="adm_closed" name="admissions_open" value="0" class="custom-control-input" {{ ($settings['admissions_open'] ?? '1') === '0' ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold text-danger cursor-pointer w-100" for="adm_closed">
                                    {{ __('Admissions CLOSED (Hide Announcement Banner)') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section 2: Entrance Results Navigation & Portal Visibility Toggle --}}
                <div class="border-bottom pb-4 mb-4">
                    <h6 class="font-weight-bold text-dark mb-2" style="font-size: 1rem;">
                        <i class="icon-file-check mr-1 text-primary"></i> {{ __('Public Entrance Results Visibility Toggle') }}
                    </h6>
                    <p class="text-muted font-size-sm mb-3">
                        {{ __('Toggle whether "Entrance Results" is displayed in the public header navigation, mobile dock, and footer links. Keep this OFF when results are not being published.') }}
                    </p>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="custom-control custom-radio custom-control-inline p-3 border rounded w-100 mb-2 {{ ($settings['entrance_results_visible'] ?? '0') === '1' ? 'border-success bg-light' : '' }}">
                                <input type="radio" id="res_visible_on" name="entrance_results_visible" value="1" class="custom-control-input" {{ ($settings['entrance_results_visible'] ?? '0') === '1' ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold text-success cursor-pointer w-100" for="res_visible_on">
                                    {{ __('Entrance Results PUBLISHED (Show in Header Navigation, Mobile Dock & Footer)') }}
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="custom-control custom-radio custom-control-inline p-3 border rounded w-100 mb-2 {{ ($settings['entrance_results_visible'] ?? '0') === '0' ? 'border-secondary bg-light' : '' }}">
                                <input type="radio" id="res_visible_off" name="entrance_results_visible" value="0" class="custom-control-input" {{ ($settings['entrance_results_visible'] ?? '0') === '0' ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold text-secondary cursor-pointer w-100" for="res_visible_off">
                                    {{ __('Entrance Results HIDDEN (Hide from Navigation & Mobile Dock — Inactive)') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section 3: Hero Section Text --}}
                <div class="border-bottom pb-4 mb-4">
                    <h6 class="font-weight-bold text-dark mb-3" style="font-size: 1rem;">
                        <i class="icon-pencil mr-1 text-primary"></i> {{ __('Hero Banner & School Identity') }}
                    </h6>

                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label font-weight-semibold text-dark">{{ __('Hero Main Heading') }}:</label>
                        <div class="col-lg-9">
                            <input name="hero_title" value="{{ $settings['hero_title'] ?? 'Inspiring Academic Excellence, Leadership & Character.' }}" type="text" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label font-weight-semibold text-dark">{{ __('Hero Subtitle / Description') }}:</label>
                        <div class="col-lg-9">
                            <textarea name="hero_subtitle" rows="3" class="form-control" required>{{ $settings['hero_subtitle'] ?? 'Nurturing young minds from Early Childhood through Grade 12 with forward-thinking STEM laboratories, comprehensive humanities, and dedicated character discipline in Koshi Province.' }}</textarea>
                        </div>
                    </div>

                    <div class="form-group row mb-0">
                        <label class="col-lg-3 col-form-label font-weight-semibold text-dark">{{ __('Established Heritage (BS & AD)') }}:</label>
                        <div class="col-lg-9">
                            <input name="established_year" value="{{ $settings['established_year'] ?? '2052 B.S. (1995 A.D.)' }}" type="text" class="form-control">
                        </div>
                    </div>
                </div>

                {{-- Section 4: Statistics Counters --}}
                <div class="border-bottom pb-4 mb-4">
                    <h6 class="font-weight-bold text-dark mb-3" style="font-size: 1rem;">
                        <i class="icon-stats-growth mr-1 text-success"></i> {{ __('Homepage Key Statistics Counters') }}
                    </h6>

                    <div class="row">
                        <div class="col-md-3 mb-2 mb-md-0">
                            <label class="font-weight-semibold text-dark">{{ __('Enrolled Scholars') }}:</label>
                            <input name="stat_students" value="{{ $settings['stat_students'] ?? '1,850+' }}" type="text" class="form-control">
                        </div>

                        <div class="col-md-3 mb-2 mb-md-0">
                            <label class="font-weight-semibold text-dark">{{ __('Dedicated Educators') }}:</label>
                            <input name="stat_teachers" value="{{ $settings['stat_teachers'] ?? '95+' }}" type="text" class="form-control">
                        </div>

                        <div class="col-md-3 mb-2 mb-md-0">
                            <label class="font-weight-semibold text-dark">{{ __('National Board Pass Rate') }}:</label>
                            <input name="stat_pass_rate" value="{{ $settings['stat_pass_rate'] ?? '99.4%' }}" type="text" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label class="font-weight-semibold text-dark">{{ __('Institutional Legacy') }}:</label>
                            <input name="stat_experience" value="{{ $settings['stat_experience'] ?? '30+ Years' }}" type="text" class="form-control">
                        </div>
                    </div>
                </div>

                {{-- Section 5: Leadership Details --}}
                <div class="border-bottom pb-4 mb-4">
                    <h6 class="font-weight-bold text-dark mb-3" style="font-size: 1rem;">
                        <i class="icon-users mr-1 text-info"></i> {{ __('Institutional Leadership') }}
                    </h6>

                    <div class="row">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="p-3 bg-light rounded border h-100">
                                <div class="form-group">
                                    <label class="font-weight-semibold text-dark">{{ __('Executive Director Name') }}:</label>
                                    <input name="director_name" value="{{ $settings['director_name'] ?? '' }}" type="text" class="form-control" placeholder="e.g. Executive Director">
                                </div>
                                <div class="form-group mb-0">
                                    <label class="font-weight-semibold text-dark">{{ __('Director Title') }}:</label>
                                    <input name="director_title" value="{{ $settings['director_title'] ?? 'Executive Director & Founder' }}" type="text" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="p-3 bg-light rounded border h-100">
                                <div class="form-group">
                                    <label class="font-weight-semibold text-dark">{{ __('Principal Name') }}:</label>
                                    <input name="principal_name" value="{{ $settings['principal_name'] ?? '' }}" type="text" class="form-control" placeholder="e.g. Principal">
                                </div>
                                <div class="form-group mb-0">
                                    <label class="font-weight-semibold text-dark">{{ __('Principal Title') }}:</label>
                                    <input name="principal_title" value="{{ $settings['principal_title'] ?? 'Principal & Academic Head' }}" type="text" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded border h-100">
                                <div class="form-group">
                                    <label class="font-weight-semibold text-dark">{{ __('Vice Principal Name') }}:</label>
                                    <input name="vice_principal_name" value="{{ $settings['vice_principal_name'] ?? '' }}" type="text" class="form-control" placeholder="e.g. Vice Principal">
                                </div>
                                <div class="form-group mb-0">
                                    <label class="font-weight-semibold text-dark">{{ __('Vice Principal Title') }}:</label>
                                    <input name="vice_principal_title" value="{{ $settings['vice_principal_title'] ?? 'Vice Principal (Student Welfare)' }}" type="text" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section 6: Campus Contacts --}}
                <div class="mb-4">
                    <h6 class="font-weight-bold text-dark mb-3" style="font-size: 1rem;">
                        <i class="icon-phone mr-1 text-primary"></i> {{ __('Campus Address & Communications') }}
                    </h6>

                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label font-weight-semibold text-dark">{{ __('Campus Address') }}:</label>
                        <div class="col-lg-9">
                            <input name="address" value="{{ $settings['address'] ?? 'Ward No. 3, Main Highway Corridor, Itahari-5, Sunsari, Koshi Province, Nepal' }}" type="text" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label font-weight-semibold text-dark">{{ __('Telephone / Mobile') }}:</label>
                        <div class="col-lg-9">
                            <input name="phone" value="{{ $settings['phone'] ?? '+977-25-560000' }}" type="text" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group row mb-0">
                        <label class="col-lg-3 col-form-label font-weight-semibold text-dark">{{ __('Official Email') }}:</label>
                        <div class="col-lg-9">
                            <input name="system_email" value="{{ $settings['system_email'] ?? 'info@apexschool.edu.np' }}" type="email" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="text-right border-top pt-3">
                    <button type="submit" class="btn text-white px-4 font-weight-semibold shadow-sm" style="background: #FF5700; border-radius: 8px;">
                        <i class="icon-checkmark mr-1"></i> {{ __('Save Landing Page Settings') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection
