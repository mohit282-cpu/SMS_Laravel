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
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="card-title font-weight-bold mb-0" style="font-size: 1.1rem;">
                    <i class="icon-users4 mr-2" style="color: #FF5700;"></i>{{ __('My Enrolled Children') }}
                </h2>
            </div>
            <div class="card-body">
                <div class="row">
                    @forelse($children ?? [] as $ch)
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card border h-100 shadow-sm" style="border-radius: 16px;">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <img src="{{ $ch->user->photo }}" class="rounded-circle mr-3" style="width: 52px; height: 52px; object-fit: cover; border: 2px solid #FF5700;" alt="{{ $ch->user->name }}">
                                        <div>
                                            <h6 class="font-weight-bold mb-0 text-dark">{{ $ch->user->name }}</h6>
                                            <span class="badge badge-primary">{{ $ch->my_class->name ?? '' }} ({{ $ch->section->name ?? '' }})</span>
                                            <div class="text-muted font-size-xs mt-1">{{ __('ADM') }}: <strong>{{ $ch->adm_no }}</strong></div>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap pt-2 border-top gap-1">
                                        <a href="{{ route('marks.year_selector', Qs::hash($ch->user->id)) }}" class="btn btn-outline-primary btn-sm mr-1 mb-1"><i class="icon-check mr-1"></i>{{ __('Marksheet') }}</a>
                                        <a href="{{ route('payments.invoice', [Qs::hash($ch->user->id), Qs::getCurrentSession()]) }}" class="btn btn-outline-success btn-sm mr-1 mb-1"><i class="icon-cash mr-1"></i>{{ __('Fees') }}</a>
                                        <a href="{{ route('tt.student_timetable', Qs::hash($ch->user->id)) }}" class="btn btn-light btn-sm mr-1 mb-1"><i class="icon-calendar mr-1"></i>{{ __('Timetable') }}</a>
                                        <a href="{{ route('attendance.student_history', $ch->user_id) }}" class="btn btn-light btn-sm mb-1"><i class="icon-alarm mr-1"></i>{{ __('Attendance') }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center text-muted py-4">
                            <i class="icon-user-cancel icon-2x mb-2 d-block text-muted"></i>
                            {{ __('No children currently linked to this parent account.') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
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
