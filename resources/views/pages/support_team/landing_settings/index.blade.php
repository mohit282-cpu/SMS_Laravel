@extends('layouts.master')
@section('page_title', __('Landing Page & Admissions Management'))

@section('content')

    <div class="row">
        <!-- Quick Stats Cards -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-body bg-blue-400 has-bg-image">
                <div class="media">
                    <div class="media-body">
                        <h3 class="mb-0 font-weight-bold">{{ $inquiryCount }}</h3>
                        <span class="text-uppercase font-size-xs font-weight-bold">{{ __('Pending Inquiries') }}</span>
                    </div>
                    <div class="ml-3 align-self-center">
                        <i class="icon-enquiry icon-3x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card card-body bg-teal-400 has-bg-image">
                <div class="media">
                    <div class="media-body">
                        <h3 class="mb-0 font-weight-bold">{{ $totalInquiries }}</h3>
                        <span class="text-uppercase font-size-xs font-weight-bold">{{ __('Total Admissions Inquiries') }}</span>
                    </div>
                    <div class="ml-3 align-self-center">
                        <i class="icon-users4 icon-3x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card card-body bg-purple-400 has-bg-image">
                <div class="media">
                    <div class="media-body">
                        <h3 class="mb-0 font-weight-bold">{{ $totalEntranceResults }}</h3>
                        <span class="text-uppercase font-size-xs font-weight-bold">{{ __('Entrance Exam Results') }}</span>
                    </div>
                    <div class="ml-3 align-self-center">
                        <i class="icon-file-check icon-3x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card card-body {{ ($settings['admissions_open'] ?? '1') === '1' ? 'bg-success-400' : 'bg-danger-400' }} has-bg-image">
                <div class="media">
                    <div class="media-body">
                        <h3 class="mb-0 font-weight-bold">{{ ($settings['admissions_open'] ?? '1') === '1' ? __('OPEN') : __('CLOSED') }}</h3>
                        <span class="text-uppercase font-size-xs font-weight-bold">{{ __('Admissions Banner Status') }}</span>
                    </div>
                    <div class="ml-3 align-self-center">
                        <i class="icon-megaphone icon-3x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Navigation Bar -->
    <div class="card">
        <div class="card-body p-2 d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('landing_settings.index') }}" class="btn btn-primary btn-sm mr-2 font-weight-semibold">
                    <i class="icon-gear mr-1"></i> {{ __('General & Admissions Controls') }}
                </a>
                <a href="{{ route('landing_settings.inquiries') }}" class="btn btn-light btn-sm mr-2 font-weight-semibold">
                    <i class="icon-bubbles4 mr-1"></i> {{ __('View Inquiries') }} ({{ $totalInquiries }})
                </a>
                <a href="{{ route('landing_settings.entrance_results') }}" class="btn btn-light btn-sm font-weight-semibold">
                    <i class="icon-file-text2 mr-1"></i> {{ __('Manage Entrance Exam Results') }} ({{ $totalEntranceResults }})
                </a>
            </div>
            <div>
                <a href="{{ route('landing.home') }}" target="_blank" class="btn btn-outline-dark btn-sm font-weight-semibold">
                    <i class="icon-eye mr-1"></i> {{ __('View Public Website') }} ↗
                </a>
            </div>
        </div>
    </div>

    <!-- Settings Editor Form -->
    <div class="card">
        <div class="card-header header-elements-inline">
            <h2 class="card-title font-weight-bold mb-0" style="font-size: 1.15rem;">
                <i class="icon-browser mr-2 text-primary"></i> {{ __('Public School Website & Admissions Settings') }}
            </h2>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <form method="post" action="{{ route('landing_settings.update') }}">
                @csrf

                <!-- Section 1: Admissions Banner Status -->
                <div class="border-bottom pb-4 mb-4">
                    <h3 class="font-weight-bold text-dark mb-2" style="font-size: 1rem;">
                        <i class="icon-bell2 mr-1 text-warning"></i> {{ __('Public Admissions Banner Status') }}
                    </h3>
                    <p class="text-muted font-size-sm mb-3">
                        {{ __('Control whether the "Admissions Open for Academic Session 2083/84 B.S." announcement banner and CTA buttons are displayed on the public homepage.') }}
                    </p>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="custom-control custom-radio custom-control-inline p-3 border rounded w-100 mb-2 {{ ($settings['admissions_open'] ?? '1') === '1' ? 'border-success bg-light' : '' }}">
                                <input type="radio" id="adm_open" name="admissions_open" value="1" class="custom-control-input" {{ ($settings['admissions_open'] ?? '1') === '1' ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold text-success cursor-pointer w-100" for="adm_open">
                                    🟢 {{ __('Admissions OPEN (Show Banner on Homepage & Enable Inquiries)') }}
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="custom-control custom-radio custom-control-inline p-3 border rounded w-100 mb-2 {{ ($settings['admissions_open'] ?? '1') === '0' ? 'border-danger bg-light' : '' }}">
                                <input type="radio" id="adm_closed" name="admissions_open" value="0" class="custom-control-input" {{ ($settings['admissions_open'] ?? '1') === '0' ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold text-danger cursor-pointer w-100" for="adm_closed">
                                    🔴 {{ __('Admissions CLOSED (Hide Announcement Banner)') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Entrance Results Navigation & Portal Visibility Toggle -->
                <div class="border-bottom pb-4 mb-4">
                    <h3 class="font-weight-bold text-dark mb-2" style="font-size: 1rem;">
                        <i class="icon-file-check mr-1 text-purple"></i> {{ __('Public Entrance Results Visibility Toggle') }}
                    </h3>
                    <p class="text-muted font-size-sm mb-3">
                        {{ __('Toggle whether "Entrance Results" is displayed in the public header navigation, mobile dock, and footer links. Keep this OFF when results are not being published.') }}
                    </p>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="custom-control custom-radio custom-control-inline p-3 border rounded w-100 mb-2 {{ ($settings['entrance_results_visible'] ?? '0') === '1' ? 'border-success bg-light' : '' }}">
                                <input type="radio" id="res_visible_on" name="entrance_results_visible" value="1" class="custom-control-input" {{ ($settings['entrance_results_visible'] ?? '0') === '1' ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold text-success cursor-pointer w-100" for="res_visible_on">
                                    🟢 {{ __('Entrance Results PUBLISHED (Show in Header Navigation, Mobile Dock & Footer)') }}
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="custom-control custom-radio custom-control-inline p-3 border rounded w-100 mb-2 {{ ($settings['entrance_results_visible'] ?? '0') === '0' ? 'border-secondary bg-light' : '' }}">
                                <input type="radio" id="res_visible_off" name="entrance_results_visible" value="0" class="custom-control-input" {{ ($settings['entrance_results_visible'] ?? '0') === '0' ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold text-secondary cursor-pointer w-100" for="res_visible_off">
                                    🔴 {{ __('Entrance Results HIDDEN (Hide from Navigation & Mobile Dock — Inactive)') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Hero Section Text -->
                <div class="border-bottom pb-4 mb-4">
                    <h3 class="font-weight-bold text-dark mb-2" style="font-size: 1rem;">
                        <i class="icon-pencil mr-1 text-primary"></i> {{ __('Hero Banner & School Identity') }}
                    </h3>

                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Hero Main Heading') }}:</label>
                        <div class="col-lg-9">
                            <input name="hero_title" value="{{ $settings['hero_title'] ?? 'Inspiring Academic Excellence, Leadership & Character.' }}" type="text" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Hero Subtitle / Description') }}:</label>
                        <div class="col-lg-9">
                            <textarea name="hero_subtitle" rows="3" class="form-control" required>{{ $settings['hero_subtitle'] ?? 'Nurturing young minds from Early Childhood through Grade 12 with forward-thinking STEM laboratories, comprehensive humanities, and dedicated character discipline in Koshi Province.' }}</textarea>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Established Heritage (BS & AD)') }}:</label>
                        <div class="col-lg-9">
                            <input name="established_year" value="{{ $settings['established_year'] ?? '2052 B.S. (1995 A.D.)' }}" type="text" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Section 3: Statistics Counters -->
                <div class="border-bottom pb-4 mb-4">
                    <h3 class="font-weight-bold text-dark mb-2" style="font-size: 1rem;">
                        <i class="icon-stats-growth mr-1 text-success"></i> {{ __('Homepage Key Statistics Counters') }}
                    </h3>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="font-weight-semibold">{{ __('Enrolled Scholars') }}:</label>
                                <input name="stat_students" value="{{ $settings['stat_students'] ?? '1,850+' }}" type="text" class="form-control">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="font-weight-semibold">{{ __('Dedicated Educators') }}:</label>
                                <input name="stat_teachers" value="{{ $settings['stat_teachers'] ?? '95+' }}" type="text" class="form-control">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="font-weight-semibold">{{ __('National Board Pass Rate') }}:</label>
                                <input name="stat_pass_rate" value="{{ $settings['stat_pass_rate'] ?? '99.4%' }}" type="text" class="form-control">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="font-weight-semibold">{{ __('Institutional Legacy') }}:</label>
                                <input name="stat_experience" value="{{ $settings['stat_experience'] ?? '30+ Years' }}" type="text" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Leadership Details -->
                <div class="border-bottom pb-4 mb-4">
                    <h3 class="font-weight-bold text-dark mb-2" style="font-size: 1rem;">
                        <i class="icon-users mr-1 text-info"></i> {{ __('Institutional Leadership') }}
                    </h3>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-semibold">{{ __('Executive Director Name') }}:</label>
                                <input name="director_name" value="{{ $settings['director_name'] ?? '' }}" type="text" class="form-control" placeholder="e.g. Executive Director">
                            </div>
                            <div class="form-group">
                                <label class="font-weight-semibold">{{ __('Director Title') }}:</label>
                                <input name="director_title" value="{{ $settings['director_title'] ?? 'Executive Director & Founder' }}" type="text" class="form-control">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-semibold">{{ __('Principal Name') }}:</label>
                                <input name="principal_name" value="{{ $settings['principal_name'] ?? '' }}" type="text" class="form-control" placeholder="e.g. Principal">
                            </div>
                            <div class="form-group">
                                <label class="font-weight-semibold">{{ __('Principal Title') }}:</label>
                                <input name="principal_title" value="{{ $settings['principal_title'] ?? 'Principal & Academic Head' }}" type="text" class="form-control">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-semibold">{{ __('Vice Principal Name') }}:</label>
                                <input name="vice_principal_name" value="{{ $settings['vice_principal_name'] ?? '' }}" type="text" class="form-control" placeholder="e.g. Vice Principal">
                            </div>
                            <div class="form-group">
                                <label class="font-weight-semibold">{{ __('Vice Principal Title') }}:</label>
                                <input name="vice_principal_title" value="{{ $settings['vice_principal_title'] ?? 'Vice Principal (Student Welfare)' }}" type="text" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 5: Campus Contacts -->
                <div class="mb-4">
                    <h3 class="font-weight-bold text-dark mb-2" style="font-size: 1rem;">
                        <i class="icon-phone mr-1 text-primary"></i> {{ __('Campus Address & Communications') }}
                    </h3>

                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Campus Address') }}:</label>
                        <div class="col-lg-9">
                            <input name="address" value="{{ $settings['address'] ?? 'Ward No. 3, Main Highway Corridor, Itahari-5, Sunsari, Koshi Province, Nepal' }}" type="text" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Telephone / Mobile') }}:</label>
                        <div class="col-lg-9">
                            <input name="phone" value="{{ $settings['phone'] ?? '+977-25-560000' }}" type="text" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Official Email') }}:</label>
                        <div class="col-lg-9">
                            <input name="system_email" value="{{ $settings['system_email'] ?? 'info@apexschool.edu.np' }}" type="email" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="text-right">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="icon-check mr-1"></i> {{ __('Save Landing Page Settings') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection
