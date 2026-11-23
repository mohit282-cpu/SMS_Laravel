@extends('layouts.master')
@section('page_title', __('My Dashboard'))
@section('content')

    {{-- Executive Dashboard Header with Dual Date & Academic Session --}}
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-3 border-bottom">
        <div>
            <div class="d-flex flex-wrap align-items-center mb-1 gap-2">
                <span class="badge badge-primary mr-2">
                    <i class="icon-shield-check mr-1" aria-hidden="true"></i> {{ __(ucwords(str_replace('_', ' ', Auth::user()->user_type))) }}
                </span>
                <span class="badge badge-light border text-dark mr-2">
                    <i class="icon-calendar22 mr-1 text-primary" aria-hidden="true"></i> {{ \App\Helpers\NepaliHelper::formatDualDate(date('Y-m-d')) }}
                </span>
                <span class="badge badge-light border text-muted">
                    <i class="icon-graduation2 mr-1 text-secondary" aria-hidden="true"></i> {{ __('Session') }}: <strong>{{ Qs::getCurrentSession() }}</strong>
                </span>
            </div>
            <h2 class="font-weight-bold text-dark mb-0" style="font-size: 1.35rem;">{{ __('Welcome back') }}, {{ Auth::user()->name }}</h2>
        </div>
        <div class="d-flex align-items-center mt-3 mt-md-0" style="gap: 0.5rem;">
            <a href="{{ route('notices.index') }}" class="btn btn-light border"><i class="icon-bell2 mr-1 text-primary" aria-hidden="true"></i> {{ __('Notices') }}</a>
            <a href="{{ route('my_account') }}" class="btn btn-outline-primary"><i class="icon-cog5 mr-1" aria-hidden="true"></i> {{ __('Account Settings') }}</a>
            @if(Qs::userIsTeamSA())
                <a href="{{ route('settings') }}" class="btn btn-light border d-none d-md-inline-block"><i class="icon-gear mr-1 text-muted" aria-hidden="true"></i> {{ __('System Settings') }}</a>
            @endif
        </div>
    </div>

    {{-- =========================================================================
         SUPER ADMIN & ADMIN DASHBOARD
         ========================================================================= --}}
    @if(Qs::userIsTeamSA())
        {{-- Primary Metric Cards (People & Roles) --}}
        <div class="row">
            <div class="col-sm-6 col-xl-3">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number">{{ Qs::formatNumber($total_students ?? (isset($users) ? $users->where('user_type', 'student')->count() : 0)) }}</div>
                        <div class="stat-label">{{ __('Total Students') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-orange">
                        <i class="icon-users4" aria-hidden="true"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number">{{ Qs::formatNumber(isset($users) ? $users->where('user_type', 'teacher')->count() : 0) }}</div>
                        <div class="stat-label">{{ __('Total Teachers') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-navy">
                        <i class="icon-user" aria-hidden="true"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number">{{ Qs::formatNumber(isset($users) ? ($users->where('user_type', 'admin')->count() + $users->where('user_type', 'super_admin')->count()) : 0) }}</div>
                        <div class="stat-label">{{ __('Administrators') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-blue">
                        <i class="icon-user-tie" aria-hidden="true"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number">{{ Qs::formatNumber(isset($users) ? $users->where('user_type', 'parent')->count() : 0) }}</div>
                        <div class="stat-label">{{ __('Total Parents') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-indigo">
                        <i class="icon-users" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Secondary Metric Cards (Academic & Infrastructure) --}}
        <div class="row mt-1">
            <div class="col-sm-6 col-xl-3">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number">{{ Qs::formatNumber($total_classes ?? 0) }}</div>
                        <div class="stat-label">{{ __('Classes') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-emerald">
                        <i class="icon-windows2" aria-hidden="true"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number">{{ Qs::formatNumber($total_sections ?? 0) }}</div>
                        <div class="stat-label">{{ __('Sections') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-blue">
                        <i class="icon-grid5" aria-hidden="true"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number">{{ Qs::formatNumber($total_books ?? 0) }}</div>
                        <div class="stat-label">{{ __('Library Books') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-orange">
                        <i class="icon-books" aria-hidden="true"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number">{{ Qs::formatNumber($total_exams ?? 0) }}</div>
                        <div class="stat-label">{{ __('Examinations') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-rose">
                        <i class="icon-file-text" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Operations Hub --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="font-weight-bold mb-0 text-dark">
                    <i class="icon-rocket mr-2 text-primary" aria-hidden="true"></i>{{ __('Quick Operations Hub') }}
                </h5>
                <span class="text-muted font-size-xs text-uppercase font-weight-semibold">{{ __('Administrative Shortcuts') }}</span>
            </div>
            <div class="card-body p-3">
                <div class="row row-cols-2 row-cols-sm-4 row-cols-lg-8 text-center" style="gap: 0.5rem 0;">
                    <div class="col-6 col-sm-3 col-md-3 col-lg mb-2">
                        <a href="{{ route('students.create') }}" class="btn btn-light border w-100 py-2 d-flex flex-column align-items-center justify-content-center h-100">
                            <i class="icon-user-plus icon-2x text-primary mb-1" aria-hidden="true"></i>
                            <span class="font-size-xs font-weight-semibold text-dark">{{ __('Add Student') }}</span>
                        </a>
                    </div>
                    <div class="col-6 col-sm-3 col-md-3 col-lg mb-2">
                        <a href="{{ route('classes.index') }}" class="btn btn-light border w-100 py-2 d-flex flex-column align-items-center justify-content-center h-100">
                            <i class="icon-windows2 icon-2x text-success mb-1" aria-hidden="true"></i>
                            <span class="font-size-xs font-weight-semibold text-dark">{{ __('Classes') }}</span>
                        </a>
                    </div>
                    <div class="col-6 col-sm-3 col-md-3 col-lg mb-2">
                        <a href="{{ route('attendance.index') }}" class="btn btn-light border w-100 py-2 d-flex flex-column align-items-center justify-content-center h-100">
                            <i class="icon-calendar2 icon-2x text-warning mb-1" aria-hidden="true"></i>
                            <span class="font-size-xs font-weight-semibold text-dark">{{ __('Attendance') }}</span>
                        </a>
                    </div>
                    <div class="col-6 col-sm-3 col-md-3 col-lg mb-2">
                        <a href="{{ route('payments.index') }}" class="btn btn-light border w-100 py-2 d-flex flex-column align-items-center justify-content-center h-100">
                            <i class="icon-cash3 icon-2x text-emerald mb-1" aria-hidden="true"></i>
                            <span class="font-size-xs font-weight-semibold text-dark">{{ __('Fees & Bills') }}</span>
                        </a>
                    </div>
                    <div class="col-6 col-sm-3 col-md-3 col-lg mb-2">
                        <a href="{{ route('exams.index') }}" class="btn btn-light border w-100 py-2 d-flex flex-column align-items-center justify-content-center h-100">
                            <i class="icon-file-text icon-2x text-danger mb-1" aria-hidden="true"></i>
                            <span class="font-size-xs font-weight-semibold text-dark">{{ __('Exams') }}</span>
                        </a>
                    </div>
                    <div class="col-6 col-sm-3 col-md-3 col-lg mb-2">
                        <a href="{{ route('tt.index') }}" class="btn btn-light border w-100 py-2 d-flex flex-column align-items-center justify-content-center h-100">
                            <i class="icon-alarm icon-2x text-info mb-1" aria-hidden="true"></i>
                            <span class="font-size-xs font-weight-semibold text-dark">{{ __('Timetable') }}</span>
                        </a>
                    </div>
                    <div class="col-6 col-sm-3 col-md-3 col-lg mb-2">
                        <a href="{{ route('notices.create') }}" class="btn btn-light border w-100 py-2 d-flex flex-column align-items-center justify-content-center h-100">
                            <i class="icon-newspaper icon-2x text-purple mb-1" aria-hidden="true"></i>
                            <span class="font-size-xs font-weight-semibold text-dark">{{ __('Post Notice') }}</span>
                        </a>
                    </div>
                    <div class="col-6 col-sm-3 col-md-3 col-lg mb-2">
                        <a href="{{ route('events.create') }}" class="btn btn-light border w-100 py-2 d-flex flex-column align-items-center justify-content-center h-100">
                            <i class="icon-calendar3 icon-2x text-orange mb-1" aria-hidden="true"></i>
                            <span class="font-size-xs font-weight-semibold text-dark">{{ __('Add Event') }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- =========================================================================
         TEACHER DASHBOARD
         ========================================================================= --}}
    @if(Qs::userIsTeacher())
        <div class="row">
            <div class="col-sm-6 col-xl-4">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number">{{ count($my_sections ?? []) }}</div>
                        <div class="stat-label">{{ __('Assigned Classes') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-orange">
                        <i class="icon-windows2" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number">{{ count($my_subjects ?? []) }}</div>
                        <div class="stat-label">{{ __('Assigned Subjects') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-navy">
                        <i class="icon-books" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number">{{ $my_materials_count ?? 0 }}</div>
                        <div class="stat-label">{{ __('Study Materials') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-indigo">
                        <i class="icon-file-text2" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Teacher Quick Actions --}}
        <div class="d-flex flex-wrap align-items-center p-3 mb-4 rounded bg-white border shadow-sm">
            <span class="font-weight-bold text-dark mr-3 mb-2 mb-md-0 font-size-sm">
                <i class="icon-grid mr-1 text-primary" aria-hidden="true"></i>{{ __('Direct Actions') }}:
            </span>
            <a href="{{ route('attendance.index') }}" class="btn btn-outline-primary btn-sm mr-2 mb-2">
                <i class="icon-calendar2 mr-1" aria-hidden="true"></i>{{ __('Take Class Attendance') }}
            </a>
            <a href="{{ route('study_materials.index') }}" class="btn btn-outline-success btn-sm mr-2 mb-2">
                <i class="icon-upload mr-1" aria-hidden="true"></i>{{ __('Study Materials') }}
            </a>
            <a href="{{ route('tt.index') }}" class="btn btn-outline-info btn-sm mr-2 mb-2">
                <i class="icon-alarm mr-1" aria-hidden="true"></i>{{ __('Class Timetable') }}
            </a>
            <a href="{{ route('marks.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="icon-check mr-1" aria-hidden="true"></i>{{ __('Enter Marks') }}
            </a>
        </div>
    @endif

    {{-- =========================================================================
         ACCOUNTANT DASHBOARD
         ========================================================================= --}}
    @if(Qs::userIsAccountant())
        <div class="row">
            <div class="col-sm-6 col-xl-3">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number" style="font-size: 1.6rem;">{{ Qs::formatMoney($total_receipts_amount ?? 0) }}</div>
                        <div class="stat-label">{{ __('Total Revenue') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-emerald">
                        <i class="icon-cash3" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number">{{ $total_payments ?? 0 }}</div>
                        <div class="stat-label">{{ __('Fee Structures') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-orange">
                        <i class="icon-coins" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number">{{ $cleared_records ?? 0 }}</div>
                        <div class="stat-label">{{ __('Cleared Invoices') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-navy">
                        <i class="icon-checkmark4" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number">{{ $uncleared_records ?? 0 }}</div>
                        <div class="stat-label">{{ __('Pending Invoices') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-rose">
                        <i class="icon-hour-glass2" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Accountant Quick Actions --}}
        <div class="d-flex flex-wrap align-items-center p-3 mb-4 rounded bg-white border shadow-sm">
            <span class="font-weight-bold text-dark mr-3 mb-2 mb-md-0 font-size-sm">
                <i class="icon-cash3 mr-1 text-emerald" aria-hidden="true"></i>{{ __('Financial Actions') }}:
            </span>
            <a href="{{ route('payments.index') }}" class="btn btn-outline-primary btn-sm mr-2 mb-2">
                <i class="icon-coins mr-1" aria-hidden="true"></i>{{ __('Manage Fee Structures') }}
            </a>
            <a href="{{ route('payments.manage') }}" class="btn btn-outline-success btn-sm mb-2">
                <i class="icon-wallet mr-1" aria-hidden="true"></i>{{ __('Collect Student Fees') }}
            </a>
        </div>
    @endif

    {{-- =========================================================================
         LIBRARIAN DASHBOARD
         ========================================================================= --}}
    @if(Qs::userIsLibrarian())
        <div class="row">
            <div class="col-sm-6 col-xl-3">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number">{{ $total_books ?? 0 }}</div>
                        <div class="stat-label">{{ __('Books Catalog') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-orange">
                        <i class="icon-books" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number">{{ $issued_books ?? 0 }}</div>
                        <div class="stat-label">{{ __('Active Loans') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-navy">
                        <i class="icon-book2" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number">{{ $pending_requests ?? 0 }}</div>
                        <div class="stat-label">{{ __('Pending Requests') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-rose">
                        <i class="icon-notification2" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number">{{ $total_categories ?? 0 }}</div>
                        <div class="stat-label">{{ __('Book Categories') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-emerald">
                        <i class="icon-folder-open" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Librarian Quick Actions --}}
        <div class="d-flex flex-wrap align-items-center p-3 mb-4 rounded bg-white border shadow-sm">
            <span class="font-weight-bold text-dark mr-3 mb-2 mb-md-0 font-size-sm">
                <i class="icon-books mr-1 text-primary" aria-hidden="true"></i>{{ __('Library Actions') }}:
            </span>
            <a href="{{ route('books.create') }}" class="btn btn-outline-primary btn-sm mr-2 mb-2">
                <i class="icon-plus2 mr-1" aria-hidden="true"></i>{{ __('Add New Book') }}
            </a>
            <a href="{{ route('books.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="icon-list mr-1" aria-hidden="true"></i>{{ __('Manage Book Catalog') }}
            </a>
        </div>
    @endif

    {{-- =========================================================================
         STUDENT DASHBOARD
         ========================================================================= --}}
    @if(Qs::userIsStudent())
        <div class="row">
            <div class="col-sm-6 col-xl-3">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number" style="font-size: 1.35rem;">{{ $student_record->my_class->name ?? '-' }} ({{ $student_record->section->name ?? '-' }})</div>
                        <div class="stat-label">{{ __('My Class & Section') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-orange">
                        <i class="icon-graduation2" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number">{{ count($my_books ?? []) }}</div>
                        <div class="stat-label">{{ __('Active Book Loans') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-navy">
                        <i class="icon-books" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number">{{ $my_materials_count ?? 0 }}</div>
                        <div class="stat-label">{{ __('Study Materials') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-indigo">
                        <i class="icon-file-text2" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number">{{ count($my_payments ?? []) }}</div>
                        <div class="stat-label">{{ __('Invoices / Fees') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-emerald">
                        <i class="icon-cash" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Student Quick Actions --}}
        <div class="d-flex flex-wrap align-items-center p-3 mb-4 rounded bg-white border shadow-sm">
            <span class="font-weight-bold text-dark mr-3 mb-2 mb-md-0 font-size-sm">
                <i class="icon-grid mr-1 text-primary" aria-hidden="true"></i>{{ __('Quick Student Links') }}:
            </span>
            <a href="{{ route('tt.student_timetable', Qs::hash(Auth::user()->id)) }}" class="btn btn-outline-primary btn-sm mr-2 mb-2">
                <i class="icon-calendar mr-1" aria-hidden="true"></i>{{ __('Class Timetable') }}
            </a>
            <a href="{{ route('study_materials.index') }}" class="btn btn-outline-info btn-sm mr-2 mb-2">
                <i class="icon-download mr-1" aria-hidden="true"></i>{{ __('Study Materials') }}
            </a>
            <a href="{{ route('attendance.student_history', Auth::user()->id) }}" class="btn btn-outline-warning btn-sm mr-2 mb-2">
                <i class="icon-alarm mr-1" aria-hidden="true"></i>{{ __('My Attendance') }}
            </a>
            <a href="{{ route('marks.year_selector', Qs::hash(Auth::user()->id)) }}" class="btn btn-outline-success btn-sm mb-2">
                <i class="icon-file-text mr-1" aria-hidden="true"></i>{{ __('View Marksheets') }}
            </a>
        </div>
    @endif

    {{-- =========================================================================
         PARENT DASHBOARD (MULTI-CHILD PORTAL)
         ========================================================================= --}}
    @if(Qs::userIsParent())
        <div class="card sms-card mb-4">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-wrapper stat-icon-orange mr-3" aria-hidden="true">
                        <i class="icon-users4"></i>
                    </div>
                    <div>
                        <h2 class="card-title mb-0" style="font-size: 1.15rem;">{{ __('Parent Portal — My Children') }}</h2>
                        <span class="text-muted font-size-sm">{{ __('Overview of academic progress, attendance, and fee status for your linked students') }}</span>
                    </div>
                </div>

                @if(!empty($children) && $children->count() > 1)
                    <div class="mt-2 mt-md-0">
                        <span class="badge badge-primary py-1 px-2" style="font-size: 0.82rem;">
                            <i class="icon-user-check mr-1" aria-hidden="true"></i>{{ __(':count Children Linked', ['count' => $children->count()]) }}
                        </span>
                    </div>
                @endif
            </div>

            <div class="card-body">
                @if(empty($children) || $children->isEmpty())
                    <div class="text-center py-5">
                        <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px; border-radius: 50%; background: rgba(108, 117, 125, 0.1);">
                            <i class="icon-user-cancel icon-2x text-muted" aria-hidden="true"></i>
                        </div>
                        <h5 class="font-weight-bold text-dark mb-1">{{ __('No Linked Students Found') }}</h5>
                        <p class="text-muted max-w-500 mx-auto mb-3" style="max-width: 480px;">
                            {{ __('Your parent account currently has no students associated with it. If your child is enrolled at SMS Academy, please contact the administration office with your registered mobile number or email to link your account.') }}
                        </p>
                        <a href="mailto:admin@smsacademy.edu.np" class="btn btn-outline-primary btn-sm">
                            <i class="icon-envelop mr-1" aria-hidden="true"></i>{{ __('Contact School Administration') }}
                        </a>
                    </div>
                @else
                    {{-- Multi-Student Selector Pills if more than 1 student --}}
                    @if($children->count() > 1)
                        <div class="mb-4">
                            <label class="font-weight-bold text-uppercase text-muted font-size-xs mb-2 d-block">{{ __('Select Student') }}:</label>
                            <ul class="nav nav-pills nav-pills-bordered" id="parent-student-tabs" role="tablist">
                                @foreach($children as $idx => $ch)
                                    <li class="nav-item">
                                        <a class="nav-link {{ $loop->first ? 'active' : '' }} font-weight-semibold" id="tab-student-{{ $ch->id }}" data-toggle="pill" href="#pane-student-{{ $ch->id }}" role="tab">
                                            <i class="icon-user mr-1" aria-hidden="true"></i>{{ $ch->user->name }}
                                            <span class="badge badge-light ml-1">{{ $ch->my_class->name ?? '' }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="tab-content" id="parent-student-content">
                        @foreach($children as $ch)
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="pane-student-{{ $ch->id }}" role="tabpanel">
                                {{-- Student Header Card --}}
                                <div class="p-3 mb-4 rounded border" style="background-color: var(--sms-surface-subtle, #f8fafc);">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <img src="{{ $ch->user->photo }}" class="rounded-circle mr-3" style="width: 56px; height: 56px; object-fit: cover; border: 2px solid var(--sms-border-strong, #cbd5e1);" alt="{{ $ch->user->name }}">
                                            <div>
                                                <h4 class="font-weight-bold text-dark mb-0">{{ $ch->user->name }}</h4>
                                                <div class="d-flex flex-wrap align-items-center mt-1 gap-2 font-size-sm">
                                                    <span class="badge badge-primary py-1 px-2 mr-2">
                                                        <i class="icon-graduation2 mr-1" aria-hidden="true"></i>{{ $ch->my_class->name ?? 'Class' }} - {{ $ch->section->name ?? 'Section' }}
                                                    </span>
                                                    <span class="text-muted mr-3"><strong>{{ __('ADM') }}:</strong> {{ $ch->adm_no }}</span>
                                                    @if($ch->parent_relationship)
                                                        <span class="badge badge-light border text-dark">
                                                            <i class="icon-heart6 text-danger mr-1" aria-hidden="true"></i>{{ $ch->parent_relationship }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-3 mt-md-0">
                                            <a href="{{ route('students.show', Qs::hash($ch->id)) }}" class="btn btn-sm btn-outline-secondary">
                                                <i class="icon-eye mr-1" aria-hidden="true"></i>{{ __('Full Student Profile') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                {{-- Three Key Performance Tiles --}}
                                <div class="row mb-4">
                                    {{-- Attendance Summary Tile --}}
                                    <div class="col-md-4 mb-3 mb-md-0">
                                        <div class="card border h-100 shadow-none mb-0" style="border-radius: 12px; background: #FAFAFA;">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <span class="text-uppercase text-muted font-weight-bold font-size-xs">{{ __('Attendance (BS)') }}</span>
                                                    <span class="badge {{ ($ch->attendance_pct ?? 0) >= 75 ? 'badge-success' : 'badge-warning' }} py-1 px-2">
                                                        {{ $ch->attendance_pct !== null ? $ch->attendance_pct . '%' : __('No records') }}
                                                    </span>
                                                </div>
                                                <h3 class="font-weight-bold text-dark mb-1">
                                                    {{ $ch->attendance_present ?? 0 }} <span class="font-size-sm text-muted">/ {{ $ch->attendance_total ?? 0 }} {{ __('Days Present') }}</span>
                                                </h3>
                                                <div class="progress mt-2" style="height: 6px;">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ min(100, $ch->attendance_pct ?? 0) }}%"></div>
                                                </div>
                                                <div class="mt-2 text-right">
                                                    <a href="{{ route('attendance.student_history', $ch->user_id) }}" class="font-size-xs font-weight-semibold text-primary">
                                                        {{ __('Detailed History') }} <i class="icon-arrow-right5 ml-1" aria-hidden="true"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Fee Balance Tile --}}
                                    <div class="col-md-4 mb-3 mb-md-0">
                                        <div class="card border h-100 shadow-none mb-0" style="border-radius: 12px; background: #FAFAFA;">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <span class="text-uppercase text-muted font-weight-bold font-size-xs">{{ __('Outstanding Fees') }}</span>
                                                    @if(($ch->outstanding_fees ?? 0) <= 0)
                                                        <span class="badge badge-success py-1 px-2"><i class="icon-checkmark-circle mr-1" aria-hidden="true"></i>{{ __('Cleared') }}</span>
                                                    @else
                                                        <span class="badge badge-danger py-1 px-2">{{ __('Due') }}</span>
                                                    @endif
                                                </div>
                                                <h3 class="font-weight-bold {{ ($ch->outstanding_fees ?? 0) > 0 ? 'text-danger' : 'text-success' }} mb-1">
                                                    {{ Qs::formatMoney($ch->outstanding_fees ?? 0) }}
                                                </h3>
                                                <p class="font-size-xs text-muted mb-2">{{ __('Current Academic Session Fees') }}</p>
                                                <div class="mt-2 text-right">
                                                    <a href="{{ route('payments.invoice', [Qs::hash($ch->user->id), Qs::getCurrentSession()]) }}" class="font-size-xs font-weight-semibold text-primary">
                                                        {{ __('View Invoices & Receipts') }} <i class="icon-arrow-right5 ml-1" aria-hidden="true"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Academic Performance Tile --}}
                                    <div class="col-md-4">
                                        <div class="card border h-100 shadow-none mb-0" style="border-radius: 12px; background: #FAFAFA;">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <span class="text-uppercase text-muted font-weight-bold font-size-xs">{{ __('Academic Results') }}</span>
                                                    @if($ch->latest_grade)
                                                        <span class="badge badge-primary py-1 px-2">{{ __('Grade :g', ['g' => $ch->latest_grade]) }}</span>
                                                    @endif
                                                </div>
                                                <h3 class="font-weight-bold text-dark mb-1">
                                                    @if($ch->latest_gpa !== null)
                                                        {{ number_format((float)$ch->latest_gpa, 2) }} <span class="font-size-sm text-muted">GPA</span>
                                                    @else
                                                        <span class="text-muted font-size-base">{{ __('Pending Exams') }}</span>
                                                    @endif
                                                </h3>
                                                <p class="font-size-xs text-muted mb-2">{{ __('Latest Published Examination') }}</p>
                                                <div class="mt-2 text-right">
                                                    <a href="{{ route('marks.year_selector', Qs::hash($ch->user->id)) }}" class="font-size-xs font-weight-semibold text-primary">
                                                        {{ __('View Official Marksheets') }} <i class="icon-arrow-right5 ml-1" aria-hidden="true"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Quick Action Shortcuts Bar --}}
                                <div class="d-flex flex-wrap align-items-center p-3 rounded" style="background: rgba(0,0,0,0.02); border: 1px dashed rgba(0,0,0,0.1);">
                                    <span class="font-weight-bold text-dark mr-3 mb-2 mb-md-0 font-size-sm"><i class="icon-grid mr-1" aria-hidden="true"></i>{{ __('Direct Student Actions') }}:</span>
                                    <a href="{{ route('marks.year_selector', Qs::hash($ch->user->id)) }}" class="btn btn-outline-primary btn-sm mr-2 mb-2">
                                        <i class="icon-file-text mr-1" aria-hidden="true"></i>{{ __('Marksheets') }}
                                    </a>
                                    <a href="{{ route('payments.invoice', [Qs::hash($ch->user->id), Qs::getCurrentSession()]) }}" class="btn btn-outline-success btn-sm mr-2 mb-2">
                                        <i class="icon-cash3 mr-1" aria-hidden="true"></i>{{ __('Fees & Receipts') }}
                                    </a>
                                    <a href="{{ route('tt.student_timetable', Qs::hash($ch->user->id)) }}" class="btn btn-outline-info btn-sm mr-2 mb-2">
                                        <i class="icon-calendar mr-1" aria-hidden="true"></i>{{ __('Class Timetable') }}
                                    </a>
                                    <a href="{{ route('attendance.student_history', $ch->user_id) }}" class="btn btn-outline-secondary btn-sm mb-2">
                                        <i class="icon-alarm mr-1" aria-hidden="true"></i>{{ __('Attendance Record') }}
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- =========================================================================
         LOWER DASHBOARD GRID: 8-COL BS CALENDAR & 4-COL EVENTS/NOTICES SIDEBAR
         ========================================================================= --}}
    <div class="row">
        {{-- Left Column: Interactive Bikram Sambat BS Calendar (8 cols) --}}
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm h-100 mb-0">
                <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between py-3 border-bottom">
                    <h5 class="font-weight-bold mb-0 text-dark">
                        <i class="icon-calendar3 mr-2" style="color: #FF5700;" aria-hidden="true"></i>{{ __('School Calendar (Bikram Sambat BS)') }}
                    </h5>
                    <div class="d-flex align-items-center mt-2 mt-sm-0">
                        @if(Qs::userIsSuperAdmin() || Qs::userIsAdmin())
                            <a href="{{ route('events.create') }}" class="btn btn-sm text-white mr-2" style="background: #FF5700; border-radius: 8px;">
                                <i class="icon-plus2 mr-1" aria-hidden="true"></i>{{ __('Add Event') }}
                            </a>
                            <a href="{{ route('events.index') }}" class="btn btn-sm btn-outline-secondary mr-2" style="border-radius: 8px;">
                                <i class="icon-calendar52 mr-1" aria-hidden="true"></i>{{ __('Manage') }}
                            </a>
                        @endif
                        {!! Qs::getPanelOptions() !!}
                    </div>
                </div>

                <div class="card-body p-3">
                    <div id="dashboard-bs-calendar"></div>
                </div>
            </div>
        </div>

        {{-- Right Column: Upcoming Events & Recent Notices (4 cols) --}}
        <div class="col-lg-4 mb-4">
            {{-- Upcoming Events & Holidays Widget --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex align-items-center justify-content-between py-3 border-bottom">
                    <h6 class="font-weight-bold mb-0 text-dark">
                        <i class="icon-calendar52 mr-2 text-warning" aria-hidden="true"></i>{{ __('Upcoming Events & Holidays') }}
                    </h6>
                    <a href="{{ route('events.index') }}" class="btn btn-sm btn-link text-primary p-0 font-size-xs font-weight-semibold">
                        {{ __('View All') }} <i class="icon-arrow-right5 ml-1" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    @if(!empty($upcoming_events) && $upcoming_events->isNotEmpty())
                        <div class="list-group list-group-flush">
                            @foreach($upcoming_events as $ev)
                                <div class="list-group-item py-3 px-3 border-bottom">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div class="pr-2">
                                            <span class="badge {{ $ev->category_badge ?? 'badge-primary' }} mb-1 font-size-xs text-uppercase">
                                                {{ $ev->category_name ?? 'Event' }}
                                            </span>
                                            <h6 class="font-weight-semibold text-dark mb-1 font-size-sm">
                                                <a href="javascript:void(0)" class="text-dark event-preview-link"
                                                   data-title="{{ $ev->title }}"
                                                   data-type="{{ $ev->category_name }}"
                                                   data-date="{{ $ev->start_date_bs ?: $ev->start_date }}"
                                                   data-desc="{{ $ev->description }}">
                                                    {{ $ev->title }}
                                                </a>
                                            </h6>
                                            <span class="text-muted font-size-xs d-block">
                                                <i class="icon-calendar2 mr-1" aria-hidden="true"></i>{{ $ev->start_date_bs ?: $ev->start_date }}
                                                @if($ev->start_time)
                                                    <span class="ml-1"><i class="icon-alarm mr-1" aria-hidden="true"></i>{{ $ev->start_time }}</span>
                                                @endif
                                            </span>
                                        </div>
                                        @if($ev->is_closure)
                                            <span class="badge badge-danger font-size-xs flex-shrink-0">{{ __('Closure') }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 px-3">
                            <i class="icon-calendar3 icon-2x text-muted mb-2 d-block" aria-hidden="true"></i>
                            <p class="text-muted font-size-sm mb-0">{{ __('No upcoming events or holidays scheduled.') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Recent School Notices Widget --}}
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex align-items-center justify-content-between py-3 border-bottom">
                    <h6 class="font-weight-bold mb-0 text-dark">
                        <i class="icon-newspaper mr-2" style="color: #FF5700;" aria-hidden="true"></i>{{ __('Recent Notices & Circulars') }}
                    </h6>
                    <a href="{{ route('notices.index') }}" class="btn btn-sm btn-link text-primary p-0 font-size-xs font-weight-semibold">
                        {{ __('All Notices') }} <i class="icon-arrow-right5 ml-1" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    @if(!empty($recent_notices) && $recent_notices->isNotEmpty())
                        <div class="list-group list-group-flush">
                            @foreach($recent_notices as $nt)
                                <div class="list-group-item py-3 px-3 border-bottom">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="pr-2">
                                            <span class="badge badge-light border font-size-xs text-uppercase font-weight-bold mb-1">
                                                {{ $nt->notice_type ?? 'Notice' }}
                                            </span>
                                            <h6 class="font-weight-semibold text-dark mb-1 font-size-sm">
                                                <a href="{{ route('notices.show', $nt->id) }}" class="text-dark">
                                                    {{ $nt->title }}
                                                </a>
                                            </h6>
                                            <span class="text-muted font-size-xs d-block">
                                                <i class="icon-calendar2 mr-1" aria-hidden="true"></i>{{ $nt->notice_date_bs ?: $nt->notice_date }}
                                            </span>
                                        </div>
                                        <a href="{{ route('notices.show', $nt->id) }}" class="btn btn-outline-light btn-sm text-dark flex-shrink-0">
                                            {{ __('Read') }}
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 px-3">
                            <i class="icon-bell2 icon-2x text-muted mb-2 d-block" aria-hidden="true"></i>
                            <p class="text-muted font-size-sm mb-0">{{ __('No published notices at this time.') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Event / Notice Details Modal --}}
    <div class="modal fade" id="eventDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header d-flex align-items-center justify-content-between" style="background: #111827; color: #FFFFFF; border-bottom: 1px solid rgba(255,255,255,0.08);">
                    <h3 class="modal-title font-weight-bold mb-0 text-white" id="eventModalTitle" style="font-size: 1.15rem;">{{ __('Event Details') }}</h3>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.8;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3 d-flex align-items-center">
                        <span class="badge badge-primary" id="eventModalType">EVENT</span>
                        <span class="text-muted font-size-sm ml-2 font-weight-medium" id="eventModalDate"></span>
                    </div>
                    <p class="text-dark mb-0" id="eventModalDesc" style="white-space: pre-wrap; font-size: 0.95rem; line-height: 1.6;"></p>
                </div>
                <div class="modal-footer bg-light p-3">
                    <a href="#" id="eventModalLink" class="btn btn-outline-primary btn-sm font-weight-semibold" style="display: none;">{{ __('View Full Details') }}</a>
                    <button type="button" class="btn btn-secondary btn-sm font-weight-semibold" data-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        if ($('#dashboard-bs-calendar').length) {
            new NepaliBsCalendar('#dashboard-bs-calendar', {
                feedUrl: '{{ route("calendar.events") }}',
                createEventUrl: '{{ route("events.create") }}',
                canCreateEvent: {{ (Qs::userIsSuperAdmin() || Qs::userIsAdmin()) ? 'true' : 'false' }},
                initialView: 'month'
            });
        }

        // Preview link click handler for upcoming events sidebar
        $('.event-preview-link').on('click', function(e) {
            e.preventDefault();
            var title = $(this).data('title');
            var type = $(this).data('type') || 'EVENT';
            var date = $(this).data('date') || '';
            var desc = $(this).data('desc') || '{{ __("No additional description provided.") }}';

            $('#eventModalTitle').text(title);
            $('#eventModalType').text(type);
            $('#eventModalDate').text(date);
            $('#eventModalDesc').text(desc);
            $('#eventDetailsModal').modal('show');
        });
    });
</script>
@endsection
