@extends('layouts.master')
@section('page_title', __('Administrator Dashboard'))

@section('content')
<div class="row">
    <div class="col-12 mb-4">
        <div class="card shadow-sm border-0" style="border-radius: 20px; background: linear-gradient(135deg, #111827 0%, #1F2937 100%); color: #FFFFFF;">
            <div class="card-body p-4 p-md-5">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <span class="badge mb-2" style="background: rgba(255, 87, 0, 0.2); color: #FF5700; border: 1px solid rgba(255, 87, 0, 0.4);">
                            <i class="icon-shield-check mr-1"></i> {{ __('System Administrator') }}
                        </span>
                        <h2 class="font-weight-bold mb-2 text-white" style="letter-spacing: -0.03em;">
                            {{ __('Welcome,') }} {{ Auth::user()->name }}
                        </h2>
                        <p class="text-white-50 mb-4" style="max-width: 540px; font-size: 1rem; line-height: 1.6;">
                            {{ __('Manage your school operations, student admissions, academic workflows, and staff records with high performance.') }}
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('users.index') }}" class="btn btn-primary mr-2 mb-2 font-weight-bold" style="border-radius: 12px; padding: 10px 20px;">
                                <i class="icon-users4 mr-2"></i> {{ __('Manage Users') }}
                            </a>
                            <a href="{{ route('students.create') }}" class="btn btn-light mb-2 font-weight-bold" style="border-radius: 12px; padding: 10px 20px; background: rgba(255,255,255,0.12); color: #fff; border: 1px solid rgba(255,255,255,0.2);">
                                <i class="icon-user-plus mr-2" style="color: #FF5700;"></i> {{ __('Admit Student') }}
                            </a>
                        </div>
                    </div>
                    <div class="col-md-4 text-center d-none d-md-block">
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 120px; height: 120px; border-radius: 30px; background: rgba(255, 87, 0, 0.15); border: 2px solid rgba(255, 87, 0, 0.3); box-shadow: 0 0 30px rgba(255, 87, 0, 0.25);">
                            <i class="icon-graduation2" style="font-size: 3.5rem; color: #FF5700;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('classes.index') }}" class="text-decoration-none">
            <div class="apple-stat-card">
                <div>
                    <div class="stat-number">{{ \App\Models\MyClass::count() }}</div>
                    <div class="stat-label">{{ __('Total Classes') }}</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-orange">
                    <i class="icon-windows2"></i>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('sections.index') }}" class="text-decoration-none">
            <div class="apple-stat-card">
                <div>
                    <div class="stat-number">{{ \App\Models\Section::count() }}</div>
                    <div class="stat-label">{{ __('Total Sections') }}</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-navy">
                    <i class="icon-fence"></i>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('subjects.index') }}" class="text-decoration-none">
            <div class="apple-stat-card">
                <div>
                    <div class="stat-number">{{ \App\Models\Subject::count() }}</div>
                    <div class="stat-label">{{ __('Total Subjects') }}</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-emerald">
                    <i class="icon-pin"></i>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('dorms.index') }}" class="text-decoration-none">
            <div class="apple-stat-card">
                <div>
                    <div class="stat-number">{{ \App\Models\Dorm::count() }}</div>
                    <div class="stat-label">{{ __('Dormitories') }}</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-indigo">
                    <i class="icon-home9"></i>
                </div>
            </div>
        </a>
    </div>
</div>
@endsection