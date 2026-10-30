@extends('layouts.master')
@section('page_title', __('My Dashboard'))
@section('content')

    {{-- Clean Apple Executive Header --}}
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center mb-1">
                <span class="badge badge-primary mr-2"><i class="icon-shield-check mr-1"></i> {{ __(ucwords(str_replace('_', ' ', Auth::user()->user_type))) }}</span>
                <span class="text-muted font-size-sm font-weight-medium">{{ date('l, F j, Y') }}</span>
            </div>
            <h2 class="font-weight-bold text-dark mb-0" style="font-size: 1.4rem; letter-spacing: -0.02em;">{{ __('Welcome back') }}, {{ Auth::user()->name }}</h2>
        </div>
        <div class="d-none d-md-flex align-items-center mt-3 mt-md-0">
            <a href="{{ route('notices.index') }}" class="btn btn-light mr-2 shadow-sm"><i class="icon-bell2 mr-1"></i> {{ __('Notices') }}</a>
            <a href="{{ route('my_account') }}" class="btn btn-outline-primary shadow-sm"><i class="icon-cog5 mr-1"></i> {{ __('Account Settings') }}</a>
        </div>
    </div>

    {{-- Super Admin & Admin Dashboard Cards --}}
    @if(Qs::userIsTeamSA() && isset($users))
       <div class="row">
           <div class="col-sm-6 col-xl-3">
               <div class="apple-stat-card">
                   <div>
                       <div class="stat-number">{{ Qs::formatNumber($users->where('user_type', 'student')->count()) }}</div>
                       <div class="stat-label">{{ __('Total Students') }}</div>
                   </div>
                   <div class="stat-icon-wrapper stat-icon-orange">
                       <i class="icon-users4"></i>
                   </div>
               </div>
           </div>

           <div class="col-sm-6 col-xl-3">
               <div class="apple-stat-card">
                   <div>
                       <div class="stat-number">{{ Qs::formatNumber($users->where('user_type', 'teacher')->count()) }}</div>
                       <div class="stat-label">{{ __('Total Teachers') }}</div>
                   </div>
                   <div class="stat-icon-wrapper stat-icon-navy">
                       <i class="icon-user"></i>
                   </div>
               </div>
           </div>

           <div class="col-sm-6 col-xl-3">
               <div class="apple-stat-card">
                   <div>
                       <div class="stat-number">{{ Qs::formatNumber($users->where('user_type', 'admin')->count()) }}</div>
                       <div class="stat-label">{{ __('Administrators') }}</div>
                   </div>
                   <div class="stat-icon-wrapper stat-icon-blue">
                       <i class="icon-user-tie"></i>
                   </div>
               </div>
           </div>

           <div class="col-sm-6 col-xl-3">
               <div class="apple-stat-card">
                   <div>
                       <div class="stat-number">{{ Qs::formatNumber($users->where('user_type', 'parent')->count()) }}</div>
                       <div class="stat-label">{{ __('Total Parents') }}</div>
                   </div>
                   <div class="stat-icon-wrapper stat-icon-indigo">
                       <i class="icon-user"></i>
                   </div>
               </div>
           </div>
       </div>
    @endif

    {{-- Teacher Dashboard Cards --}}
    @if(Qs::userIsTeacher())
        <div class="row">
            <div class="col-sm-6 col-xl-4">
                <div class="apple-stat-card">
                    <div>
                        <div class="stat-number">{{ count($my_sections ?? []) }}</div>
                        <div class="stat-label">{{ __('Assigned Classes') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-orange">
                        <i class="icon-windows2"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="apple-stat-card">
                    <div>
                        <div class="stat-number">{{ count($my_subjects ?? []) }}</div>
                        <div class="stat-label">{{ __('Assigned Subjects') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-navy">
                        <i class="icon-books"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="apple-stat-card">
                    <div>
                        <div class="stat-number">{{ $my_materials_count ?? 0 }}</div>
                        <div class="stat-label">{{ __('Study Materials') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-indigo">
                        <i class="icon-file-text2"></i>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Accountant Dashboard Cards --}}
    @if(Qs::userIsAccountant())
        <div class="row">
            <div class="col-sm-6 col-xl-3">
                <div class="apple-stat-card">
                    <div>
                        <div class="stat-number" style="font-size: 1.6rem;">{{ Qs::formatMoney($total_receipts_amount ?? 0) }}</div>
                        <div class="stat-label">{{ __('Total Revenue') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-emerald">
                        <i class="icon-cash3"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="apple-stat-card">
                    <div>
                        <div class="stat-number">{{ $total_payments ?? 0 }}</div>
                        <div class="stat-label">{{ __('Fee Structures') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-orange">
                        <i class="icon-coins"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="apple-stat-card">
                    <div>
                        <div class="stat-number">{{ $cleared_records ?? 0 }}</div>
                        <div class="stat-label">{{ __('Cleared Invoices') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-navy">
                        <i class="icon-checkmark4"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="apple-stat-card">
                    <div>
                        <div class="stat-number">{{ $uncleared_records ?? 0 }}</div>
                        <div class="stat-label">{{ __('Pending Invoices') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-orange" style="background: rgba(239,68,68,0.12); color: #EF4444;">
                        <i class="icon-hour-glass2"></i>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Librarian Dashboard Cards --}}
    @if(Qs::userIsLibrarian())
        <div class="row">
            <div class="col-sm-6 col-xl-3">
                <div class="apple-stat-card">
                    <div>
                        <div class="stat-number">{{ $total_books ?? 0 }}</div>
                        <div class="stat-label">{{ __('Books Catalog') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-orange">
                        <i class="icon-books"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="apple-stat-card">
                    <div>
                        <div class="stat-number">{{ $issued_books ?? 0 }}</div>
                        <div class="stat-label">{{ __('Active Loans') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-navy">
                        <i class="icon-book2"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="apple-stat-card">
                    <div>
                        <div class="stat-number">{{ $pending_requests ?? 0 }}</div>
                        <div class="stat-label">{{ __('Pending Requests') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-orange" style="background: rgba(239,68,68,0.12); color: #EF4444;">
                        <i class="icon-notification2"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="apple-stat-card">
                    <div>
                        <div class="stat-number">{{ $total_categories ?? 0 }}</div>
                        <div class="stat-label">{{ __('Book Categories') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-emerald">
                        <i class="icon-folder-open"></i>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Student Dashboard Cards --}}
    @if(Qs::userIsStudent())
        <div class="row">
            <div class="col-sm-6 col-xl-3">
                <div class="apple-stat-card">
                    <div>
                        <div class="stat-number" style="font-size: 1.4rem;">{{ $student_record->my_class->name ?? '-' }} ({{ $student_record->section->name ?? '-' }})</div>
                        <div class="stat-label">{{ __('My Class & Section') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-orange">
                        <i class="icon-graduation2"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="apple-stat-card">
                    <div>
                        <div class="stat-number">{{ count($my_books ?? []) }}</div>
                        <div class="stat-label">{{ __('Active Book Loans') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-navy">
                        <i class="icon-books"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="apple-stat-card">
                    <div>
                        <div class="stat-number">{{ $my_materials_count ?? 0 }}</div>
                        <div class="stat-label">{{ __('Study Materials') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-indigo">
                        <i class="icon-file-text2"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="apple-stat-card">
                    <div>
                        <div class="stat-number">{{ count($my_payments ?? []) }}</div>
                        <div class="stat-label">{{ __('Invoices / Fees') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-emerald">
                        <i class="icon-cash"></i>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Parent Dashboard Cards --}}
    @if(Qs::userIsParent())
        <div class="card shadow-sm mb-4" style="border-radius: 16px; border: 1px solid rgba(255, 87, 0, 0.15);">
            <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <div class="d-flex align-items-center">
                    <div class="d-inline-flex align-items-center justify-content-center mr-3" style="width: 44px; height: 44px; border-radius: 12px; background: rgba(255, 87, 0, 0.1);">
                        <i class="icon-users4 icon-2x" style="color: #FF5700;"></i>
                    </div>
                    <div>
                        <h2 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.15rem;">{{ __('Parent Portal — My Children') }}</h2>
                        <span class="text-muted font-size-sm">{{ __('Overview of academic progress, attendance, and fee status for your linked students') }}</span>
                    </div>
                </div>

                @if(!empty($children) && $children->count() > 1)
                    <div class="mt-2 mt-md-0">
                        <span class="badge badge-primary py-1 px-2" style="font-size: 0.82rem;">
                            <i class="icon-user-check mr-1"></i>{{ __(':count Children Linked', ['count' => $children->count()]) }}
                        </span>
                    </div>
                @endif
            </div>

            <div class="card-body">
                @if(empty($children) || $children->isEmpty())
                    <div class="text-center py-5">
                        <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px; border-radius: 50%; background: rgba(108, 117, 125, 0.1);">
                            <i class="icon-user-cancel icon-2x text-muted"></i>
                        </div>
                        <h5 class="font-weight-bold text-dark mb-1">{{ __('No Linked Students Found') }}</h5>
                        <p class="text-muted max-w-500 mx-auto mb-3" style="max-width: 480px;">
                            {{ __('Your parent account currently has no students associated with it. If your child is enrolled at SMS Academy, please contact the administration office with your registered mobile number or email to link your account.') }}
                        </p>
                        <a href="mailto:admin@smsacademy.edu.np" class="btn btn-outline-primary btn-sm">
                            <i class="icon-envelop mr-1"></i>{{ __('Contact School Administration') }}
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
                                            <i class="icon-user mr-1"></i>{{ $ch->user->name }}
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
                                <div class="p-3 mb-4 rounded" style="background: linear-gradient(135deg, rgba(255, 87, 0, 0.05) 0%, rgba(255, 87, 0, 0.01) 100%); border: 1px solid rgba(255, 87, 0, 0.12);">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <img src="{{ $ch->user->photo }}" class="rounded-circle mr-3" style="width: 64px; height: 64px; object-fit: cover; border: 3px solid #FF5700; box-shadow: 0 2px 8px rgba(255,87,0,0.2);" alt="{{ $ch->user->name }}">
                                            <div>
                                                <h4 class="font-weight-bold text-dark mb-0">{{ $ch->user->name }}</h4>
                                                <div class="d-flex flex-wrap align-items-center mt-1 gap-2 font-size-sm">
                                                    <span class="badge badge-primary py-1 px-2 mr-2">
                                                        <i class="icon-graduation2 mr-1"></i>{{ $ch->my_class->name ?? 'Class' }} - {{ $ch->section->name ?? 'Section' }}
                                                    </span>
                                                    <span class="text-muted mr-3"><strong>{{ __('ADM') }}:</strong> {{ $ch->adm_no }}</span>
                                                    @if($ch->parent_relationship)
                                                        <span class="badge badge-light border text-dark">
                                                            <i class="icon-heart6 text-danger mr-1"></i>{{ $ch->parent_relationship }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-3 mt-md-0">
                                            <a href="{{ route('students.show', Qs::hash($ch->id)) }}" class="btn btn-sm btn-outline-secondary">
                                                <i class="icon-eye mr-1"></i>{{ __('Full Student Profile') }}
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
                                                        {{ __('Detailed History') }} <i class="icon-arrow-right5 ml-1"></i>
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
                                                        <span class="badge badge-success py-1 px-2"><i class="icon-checkmark-circle mr-1"></i>{{ __('Cleared') }}</span>
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
                                                        {{ __('View Invoices & Receipts') }} <i class="icon-arrow-right5 ml-1"></i>
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
                                                        {{ __('View Official Marksheets') }} <i class="icon-arrow-right5 ml-1"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Quick Action Shortcuts Bar --}}
                                <div class="d-flex flex-wrap align-items-center p-3 rounded" style="background: rgba(0,0,0,0.02); border: 1px dashed rgba(0,0,0,0.1);">
                                    <span class="font-weight-bold text-dark mr-3 mb-2 mb-md-0 font-size-sm"><i class="icon-grid mr-1"></i>{{ __('Direct Student Actions') }}:</span>
                                    <a href="{{ route('marks.year_selector', Qs::hash($ch->user->id)) }}" class="btn btn-outline-primary btn-sm mr-2 mb-2">
                                        <i class="icon-file-text mr-1"></i>{{ __('Marksheets') }}
                                    </a>
                                    <a href="{{ route('payments.invoice', [Qs::hash($ch->user->id), Qs::getCurrentSession()]) }}" class="btn btn-outline-success btn-sm mr-2 mb-2">
                                        <i class="icon-cash3 mr-1"></i>{{ __('Fees & Receipts') }}
                                    </a>
                                    <a href="{{ route('tt.student_timetable', Qs::hash($ch->user->id)) }}" class="btn btn-outline-info btn-sm mr-2 mb-2">
                                        <i class="icon-calendar mr-1"></i>{{ __('Class Timetable') }}
                                    </a>
                                    <a href="{{ route('attendance.student_history', $ch->user_id) }}" class="btn btn-outline-secondary btn-sm mb-2">
                                        <i class="icon-alarm mr-1"></i>{{ __('Attendance Record') }}
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Recent School Notices for Parents --}}
        @if(!empty($recent_notices) && $recent_notices->isNotEmpty())
            <div class="card shadow-sm mb-4" style="border-radius: 16px;">
                <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                    <h5 class="font-weight-bold mb-0 text-dark"><i class="icon-newspaper mr-2" style="color: #FF5700;"></i>{{ __('Recent School Notices & Circulars') }}</h5>
                    <a href="{{ route('notices.index') }}" class="btn btn-sm btn-link text-primary font-weight-semibold">{{ __('View All Notices') }} <i class="icon-arrow-right5 ml-1"></i></a>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($recent_notices as $nt)
                            <div class="list-group-item d-flex align-items-center justify-content-between py-3">
                                <div class="d-flex align-items-center">
                                    <div class="mr-3 text-center" style="min-width: 50px;">
                                        <span class="badge badge-light border d-block py-1 px-2 font-size-xs text-uppercase font-weight-bold">{{ $nt->notice_type ?? 'General' }}</span>
                                    </div>
                                    <div>
                                        <h6 class="font-weight-semibold text-dark mb-0">{{ $nt->title }}</h6>
                                        <span class="text-muted font-size-xs"><i class="icon-calendar2 mr-1"></i>{{ $nt->publish_date_bs ?: $nt->publish_date }}</span>
                                    </div>
                                </div>
                                <a href="{{ route('notices.show', $nt->id) }}" class="btn btn-outline-light btn-sm text-dark">{{ __('Read') }}</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- Events Calendar Card (Bikram Sambat BS) --}}
    <div class="card shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h2 class="card-title font-weight-bold mb-0" style="font-size: 1.1rem;">
                <i class="icon-calendar3 mr-2" style="color: #FF5700;"></i>{{ __('School Calendar & Notices (Bikram Sambat BS)') }}
            </h2>
            <div class="d-flex align-items-center">
                @if(Qs::userIsSuperAdmin() || Qs::userIsAdmin())
                    <a href="{{ route('events.create') }}" class="btn btn-sm text-white mr-2" style="background: #FF5700; border-radius: 8px;">
                        <i class="icon-plus2 mr-1"></i>{{ __('Add Event') }}
                    </a>
                    <a href="{{ route('events.index') }}" class="btn btn-sm btn-outline-secondary mr-2" style="border-radius: 8px;">
                        <i class="icon-calendar52 mr-1"></i>{{ __('Manage Events') }}
                    </a>
                @endif
                {!! Qs::getPanelOptions() !!}
            </div>
        </div>

        <div class="card-body p-3">
            <div id="dashboard-bs-calendar"></div>
        </div>
    </div>

    {{-- Event / Notice Details Modal --}}
    <div class="modal fade" id="eventDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow-lg border-0" style="border-radius: 20px;">
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
    });
</script>
@endsection
