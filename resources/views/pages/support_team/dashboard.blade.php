@extends('layouts.master')
@section('page_title', __('My Dashboard'))
@section('content')

    {{-- Super Admin & Admin Dashboard Cards --}}
    @if(Qs::userIsTeamSA() && isset($users))
       <div class="row">
           <div class="col-sm-6 col-xl-3">
               <div class="card card-body bg-blue-400 has-bg-image">
                   <div class="media">
                       <div class="media-body">
                           <h3 class="mb-0">{{ Qs::formatNumber($users->where('user_type', 'student')->count()) }}</h3>
                           <span class="text-uppercase font-size-xs font-weight-bold">{{ __('Total Students') }}</span>
                       </div>
                       <div class="ml-3 align-self-center">
                           <i class="icon-users4 icon-3x opacity-75"></i>
                       </div>
                   </div>
               </div>
           </div>

           <div class="col-sm-6 col-xl-3">
               <div class="card card-body bg-danger-400 has-bg-image">
                   <div class="media">
                       <div class="media-body">
                           <h3 class="mb-0">{{ Qs::formatNumber($users->where('user_type', 'teacher')->count()) }}</h3>
                           <span class="text-uppercase font-size-xs font-weight-bold">{{ __('Total Teachers') }}</span>
                       </div>
                       <div class="ml-3 align-self-center">
                           <i class="icon-users2 icon-3x opacity-75"></i>
                       </div>
                   </div>
               </div>
           </div>

           <div class="col-sm-6 col-xl-3">
               <div class="card card-body bg-success-400 has-bg-image">
                   <div class="media">
                       <div class="media-body">
                           <h3 class="mb-0">{{ Qs::formatNumber($users->where('user_type', 'admin')->count()) }}</h3>
                           <span class="text-uppercase font-size-xs font-weight-bold">{{ __('Total Administrators') }}</span>
                       </div>
                       <div class="ml-3 align-self-center">
                           <i class="icon-user-tie icon-3x opacity-75"></i>
                       </div>
                   </div>
               </div>
           </div>

           <div class="col-sm-6 col-xl-3">
               <div class="card card-body bg-indigo-400 has-bg-image">
                   <div class="media">
                       <div class="media-body">
                           <h3 class="mb-0">{{ Qs::formatNumber($users->where('user_type', 'parent')->count()) }}</h3>
                           <span class="text-uppercase font-size-xs font-weight-bold">{{ __('Total Parents') }}</span>
                       </div>
                       <div class="ml-3 align-self-center">
                           <i class="icon-user icon-3x opacity-75"></i>
                       </div>
                   </div>
               </div>
           </div>
       </div>
    @endif

    {{-- Teacher Dashboard Cards --}}
    @if(Qs::userIsTeacher())
        <div class="row">
            <div class="col-sm-6 col-xl-4">
                <div class="card card-body bg-blue-600 has-bg-image text-white">
                    <div class="media">
                        <div class="media-body">
                            <h3 class="mb-0">{{ count($my_sections ?? []) }}</h3>
                            <span class="text-uppercase font-size-xs font-weight-bold">{{ __('Assigned Classes / Sections') }}</span>
                        </div>
                        <div class="ml-3 align-self-center">
                            <i class="icon-windows2 icon-3x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="card card-body bg-teal-600 has-bg-image text-white">
                    <div class="media">
                        <div class="media-body">
                            <h3 class="mb-0">{{ count($my_subjects ?? []) }}</h3>
                            <span class="text-uppercase font-size-xs font-weight-bold">{{ __('Assigned Subjects') }}</span>
                        </div>
                        <div class="ml-3 align-self-center">
                            <i class="icon-books icon-3x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="card card-body bg-purple-600 has-bg-image text-white">
                    <div class="media">
                        <div class="media-body">
                            <h3 class="mb-0">{{ $my_materials_count ?? 0 }}</h3>
                            <span class="text-uppercase font-size-xs font-weight-bold">{{ __('Uploaded Study Materials') }}</span>
                        </div>
                        <div class="ml-3 align-self-center">
                            <i class="icon-file-text2 icon-3x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Accountant Dashboard Cards --}}
    @if(Qs::userIsAccountant())
        <div class="row">
            <div class="col-sm-6 col-xl-3">
                <div class="card card-body bg-success-600 text-white">
                    <div class="media">
                        <div class="media-body">
                            <h3 class="mb-0">{{ Qs::formatCurrency($total_receipts_amount ?? 0) }}</h3>
                            <span class="text-uppercase font-size-xs font-weight-bold">{{ __('Total Revenue Collected') }}</span>
                        </div>
                        <div class="ml-3 align-self-center">
                            <i class="icon-cash3 icon-3x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card card-body bg-blue-600 text-white">
                    <div class="media">
                        <div class="media-body">
                            <h3 class="mb-0">{{ $total_payments ?? 0 }}</h3>
                            <span class="text-uppercase font-size-xs font-weight-bold">{{ __('Fee Structures') }}</span>
                        </div>
                        <div class="ml-3 align-self-center">
                            <i class="icon-coins icon-3x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card card-body bg-primary-600 text-white">
                    <div class="media">
                        <div class="media-body">
                            <h3 class="mb-0">{{ $cleared_records ?? 0 }}</h3>
                            <span class="text-uppercase font-size-xs font-weight-bold">{{ __('Cleared Invoices') }}</span>
                        </div>
                        <div class="ml-3 align-self-center">
                            <i class="icon-checkmark4 icon-3x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card card-body bg-danger-600 text-white">
                    <div class="media">
                        <div class="media-body">
                            <h3 class="mb-0">{{ $uncleared_records ?? 0 }}</h3>
                            <span class="text-uppercase font-size-xs font-weight-bold">{{ __('Pending Invoices') }}</span>
                        </div>
                        <div class="ml-3 align-self-center">
                            <i class="icon-hour-glass2 icon-3x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Librarian Dashboard Cards --}}
    @if(Qs::userIsLibrarian())
        <div class="row">
            <div class="col-sm-6 col-xl-3">
                <div class="card card-body bg-indigo-600 text-white">
                    <div class="media">
                        <div class="media-body">
                            <h3 class="mb-0">{{ $total_books ?? 0 }}</h3>
                            <span class="text-uppercase font-size-xs font-weight-bold">{{ __('Total Books in Catalog') }}</span>
                        </div>
                        <div class="ml-3 align-self-center">
                            <i class="icon-books icon-3x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card card-body bg-warning-600 text-white">
                    <div class="media">
                        <div class="media-body">
                            <h3 class="mb-0">{{ $issued_books ?? 0 }}</h3>
                            <span class="text-uppercase font-size-xs font-weight-bold">{{ __('Active Book Loans') }}</span>
                        </div>
                        <div class="ml-3 align-self-center">
                            <i class="icon-book2 icon-3x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card card-body bg-danger-600 text-white">
                    <div class="media">
                        <div class="media-body">
                            <h3 class="mb-0">{{ $pending_requests ?? 0 }}</h3>
                            <span class="text-uppercase font-size-xs font-weight-bold">{{ __('Pending Book Requests') }}</span>
                        </div>
                        <div class="ml-3 align-self-center">
                            <i class="icon-notification2 icon-3x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card card-body bg-teal-600 text-white">
                    <div class="media">
                        <div class="media-body">
                            <h3 class="mb-0">{{ $total_categories ?? 0 }}</h3>
                            <span class="text-uppercase font-size-xs font-weight-bold">{{ __('Book Categories') }}</span>
                        </div>
                        <div class="ml-3 align-self-center">
                            <i class="icon-folder-open icon-3x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Student Dashboard Cards --}}
    @if(Qs::userIsStudent())
        <div class="row">
            <div class="col-sm-6 col-xl-3">
                <div class="card card-body bg-primary-600 text-white">
                    <div class="media">
                        <div class="media-body">
                            <h4 class="mb-0 font-weight-bold">{{ $student_record->my_class->name ?? '-' }} ({{ $student_record->section->name ?? '-' }})</h4>
                            <span class="text-uppercase font-size-xs font-weight-bold">{{ __('My Class & Section') }}</span>
                        </div>
                        <div class="ml-3 align-self-center">
                            <i class="icon-graduation2 icon-3x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card card-body bg-warning-700 text-white">
                    <div class="media">
                        <div class="media-body">
                            <h3 class="mb-0">{{ count($my_books ?? []) }}</h3>
                            <span class="text-uppercase font-size-xs font-weight-bold">{{ __('Active Book Loans') }}</span>
                        </div>
                        <div class="ml-3 align-self-center">
                            <i class="icon-books icon-3x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card card-body bg-purple-600 text-white">
                    <div class="media">
                        <div class="media-body">
                            <h3 class="mb-0">{{ $my_materials_count ?? 0 }}</h3>
                            <span class="text-uppercase font-size-xs font-weight-bold">{{ __('Study Materials') }}</span>
                        </div>
                        <div class="ml-3 align-self-center">
                            <i class="icon-file-text2 icon-3x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card card-body bg-success-600 text-white">
                    <div class="media">
                        <div class="media-body">
                            <h3 class="mb-0">{{ count($my_payments ?? []) }}</h3>
                            <span class="text-uppercase font-size-xs font-weight-bold">{{ __('Invoices / Fees') }}</span>
                        </div>
                        <div class="ml-3 align-self-center">
                            <i class="icon-cash icon-3x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Parent Dashboard Cards --}}
    @if(Qs::userIsParent())
        <div class="card">
            <div class="card-header header-elements-inline">
                <h6 class="card-title font-weight-bold"><i class="icon-users4 mr-2"></i>{{ __('My Enrolled Children') }}</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    @forelse($children ?? [] as $ch)
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card border border-primary shadow-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <img src="{{ $ch->user->photo }}" class="rounded-circle mr-3" width="50" height="50" alt="photo">
                                        <div>
                                            <h6 class="font-weight-bold mb-0">{{ $ch->user->name }}</h6>
                                            <span class="badge badge-info">{{ $ch->my_class->name ?? '' }} ({{ $ch->section->name ?? '' }})</span>
                                            <div class="text-muted font-size-xs">{{ __('ADM') }}: {{ $ch->adm_no }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap pt-2 border-top">
                                        <a href="{{ route('marks.year_selector', Qs::hash($ch->user->id)) }}" class="btn btn-outline-primary btn-sm mr-1 mb-1"><i class="icon-check mr-1"></i>{{ __('Marksheet') }}</a>
                                        <a href="{{ route('payments.invoice', [Qs::hash($ch->user->id), Qs::getCurrentSession()]) }}" class="btn btn-outline-success btn-sm mr-1 mb-1"><i class="icon-cash mr-1"></i>{{ __('Fees') }}</a>
                                        <a href="{{ route('tt.student_timetable', Qs::hash($ch->user->id)) }}" class="btn btn-outline-info btn-sm mr-1 mb-1"><i class="icon-calendar mr-1"></i>{{ __('Timetable') }}</a>
                                        <a href="{{ route('attendance.student_history', $ch->user_id) }}" class="btn btn-outline-warning btn-sm mb-1"><i class="icon-alarm mr-1"></i>{{ __('Attendance') }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center text-muted py-3">
                            <i class="icon-user-cancel icon-2x mb-2 d-block"></i>
                            {{ __('No children currently linked to this parent account.') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    {{-- Events Calendar Begins --}}
    <div class="card">
        <div class="card-header header-elements-inline">
            <h5 class="card-title font-weight-bold"><i class="icon-calendar3 mr-2"></i>{{ __('School Calendar & Notices') }}</h5>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-12">
                    <span class="badge badge-danger p-2 mr-2"><i class="icon-primitive-dot"></i> {{ __('Public / School Holiday') }}</span>
                    <span class="badge badge-warning p-2 mr-2 text-white"><i class="icon-primitive-dot"></i> {{ __('Examination Period') }}</span>
                    <span class="badge badge-primary p-2 mr-2"><i class="icon-primitive-dot"></i> {{ __('Academic Event') }}</span>
                    <span class="badge p-2 mr-2 text-white" style="background-color: #673ab7;"><i class="icon-primitive-dot"></i> {{ __('School Notice') }}</span>
                    <span class="badge badge-success p-2 mr-2"><i class="icon-primitive-dot"></i> {{ __('Working Day') }}</span>
                </div>
            </div>
            <div id="dashboard-events-calendar"></div>
        </div>
    </div>
    {{-- Events Calendar Ends --}}

    {{-- Event / Notice Details Modal --}}
    <div class="modal fade" id="eventDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title font-weight-bold" id="eventModalTitle">{{ __('Event Details') }}</h6>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <span class="badge badge-secondary" id="eventModalType">EVENT</span>
                        <span class="text-muted font-size-sm ml-2" id="eventModalDate"></span>
                    </div>
                    <p class="mt-3 text-dark" id="eventModalDesc" style="white-space: pre-wrap; font-size: 1rem; line-height: 1.6;"></p>
                </div>
                <div class="modal-footer">
                    <a href="#" id="eventModalLink" class="btn btn-outline-primary btn-sm" style="display: none;">{{ __('View Full Notice') }}</a>
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        if ($('#dashboard-events-calendar').length) {
            $('#dashboard-events-calendar').fullCalendar({
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'month,agendaWeek,agendaDay'
                },
                events: '{{ route("calendar.events") }}',
                editable: false,
                eventLimit: true,
                eventClick: function(event) {
                    var dateStr = moment(event.start).format('YYYY-MM-DD');
                    if (event.end) {
                        var actualEnd = moment(event.end).subtract(1, 'days').format('YYYY-MM-DD');
                        if (actualEnd !== dateStr) {
                            dateStr += ' to ' + actualEnd;
                        }
                    }
                    $('#eventModalTitle').text(event.title);
                    $('#eventModalDate').text(dateStr);
                    $('#eventModalType').text(event.type ? event.type.toUpperCase() : 'EVENT');
                    $('#eventModalDesc').text(event.description || '{{ __("No additional details provided.") }}');
                    if (event.notice_id) {
                        $('#eventModalLink').attr('href', '{{ url("/notices") }}/' + event.notice_id).show();
                    } else {
                        $('#eventModalLink').hide();
                    }
                    $('#eventDetailsModal').modal('show');
                }
            });
        }
    });
</script>
@endsection
