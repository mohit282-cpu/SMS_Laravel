@extends('layouts.master')
@section('page_title', __('Library Circulations: Issue & Return'))
@section('content')

    {{-- Executive Header --}}
    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center">
                    <div class="d-inline-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px; border-radius: 14px; background: rgba(255, 87, 0, 0.12);">
                        <i class="icon-reading icon-2x" style="color: #FF5700;"></i>
                    </div>
                    <div>
                        <h3 class="font-weight-bold mb-1 text-dark" style="font-size: 1.35rem; letter-spacing: -0.01em;">
                            {{ __('Library Circulations: Issue & Return') }}
                        </h3>
                        <p class="text-muted mb-0 font-size-sm">
                            {{ __('Active borrower loans, overdue penalty tracking & returned circulation history') }}
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
                    <a href="{{ route('books.index') }}" class="btn btn-outline-secondary btn-sm font-weight-semibold shadow-sm ml-md-2" style="border-radius: 8px;">
                        <i class="icon-books mr-1"></i>{{ __('Inventory') }}
                    </a>
                    @if(Qs::userIsTeamLibrary())
                        <button type="button" class="btn text-white btn-sm font-weight-semibold shadow-sm" data-toggle="modal" data-target="#modal-issue-book" style="background: #FF5700; border-radius: 8px;">
                            <i class="icon-plus2 mr-1"></i>{{ __('Issue Book to Borrower') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- 4 Metric KPI Stat Cards --}}
    <div class="row mb-4">
        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #4f46e5 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Active Loans') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format(count($active_loans)) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Books currently in circulation') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(79, 70, 229, 0.1);">
                            <i class="icon-reading icon-2x" style="color: #4f46e5;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #ef4444 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Overdue Loans') }}</span>
                            <h3 class="font-weight-bold text-danger mb-0 mt-1">{{ number_format(count($overdue_loans)) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Past due date threshold') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(239, 68, 68, 0.1);">
                            <i class="icon-alarm icon-2x" style="color: #ef4444;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #10b981 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Returned History') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format(count($returned_loans)) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Completed circulations') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16, 185, 129, 0.1);">
                            <i class="icon-history icon-2x" style="color: #10b981;"></i>
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
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Available Books') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($books->where('available_copies', '>', 0)->count()) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Ready for checkout') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(6, 182, 212, 0.1);">
                            <i class="icon-books icon-2x" style="color: #06b6d4;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Circulations Card --}}
    <div class="card shadow-sm border-0" style="border-radius: 14px;">
        <div class="card-header d-flex align-items-center justify-content-between bg-white border-bottom py-3">
            <h5 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.05rem;">
                <i class="icon-sync mr-2" style="color: #FF5700;"></i>{{ __('Circulation Records & Book Loans') }}
            </h5>
            <div class="d-flex align-items-center">
                {!! Qs::getPanelOptions() !!}
            </div>
        </div>

        <div class="card-body p-3 p-md-4">
            <ul class="nav nav-tabs nav-tabs-highlight mb-4">
                <li class="nav-item">
                    <a href="#active-loans" class="nav-link active font-weight-semibold" data-toggle="tab">
                        <i class="icon-reading mr-1"></i> {{ __('Active Issued Books') }}
                        <span class="badge badge-secondary ml-1 font-size-xs">{{ count($active_loans) }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#overdue-loans" class="nav-link font-weight-semibold" data-toggle="tab">
                        <i class="icon-alarm mr-1 text-danger"></i>
                        <span class="text-danger font-weight-bold">{{ __('Overdue Loans') }}</span>
                        <span class="badge badge-danger ml-1 font-size-xs">{{ count($overdue_loans) }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#returned-history" class="nav-link font-weight-semibold" data-toggle="tab">
                        <i class="icon-history mr-1"></i> {{ __('Returned History') }}
                        <span class="badge badge-secondary ml-1 font-size-xs">{{ count($returned_loans) }}</span>
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                {{-- Tab 1: Active Issued Books --}}
                <div class="tab-pane fade show active" id="active-loans">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped datatable-button-html5-columns">
                            <thead class="bg-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>{{ __('Book Title') }}</th>
                                <th>{{ __('Borrower') }}</th>
                                <th>{{ __('Role') }}</th>
                                <th>{{ __('Issue Date (BS)') }}</th>
                                <th>{{ __('Due Date (BS)') }}</th>
                                <th>{{ __('Days Left / Overdue') }}</th>
                                <th>{{ __('Issued By') }}</th>
                                <th class="text-center" style="width: 120px;">{{ __('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($active_loans as $loan)
                                @php
                                    $today = \Carbon\Carbon::today();
                                    $dueDate = \Carbon\Carbon::parse($loan->due_date);
                                    $isOverdue = $today->gt($dueDate);
                                    $daysDiff = abs($today->diffInDays($dueDate, false));
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="font-weight-semibold text-dark">
                                            <a href="{{ route('books.show', $loan->book_id) }}">{{ $loan->book ? $loan->book->name : '-' }}</a>
                                        </div>
                                        @if($loan->book && $loan->book->isbn)
                                            <div class="text-muted font-size-xs">ISBN: {{ $loan->book->isbn }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="font-weight-semibold text-dark">{{ $loan->user ? $loan->user->name : '-' }}</div>
                                        <div class="text-muted font-size-xs">{{ $loan->user ? $loan->user->email : '' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light border">
                                            {{ ucfirst($loan->borrower_type) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="font-weight-medium text-dark">{{ $loan->issue_date_bs ?: $loan->issue_date }}</span>
                                    </td>
                                    <td>
                                        <span class="font-weight-medium {{ $isOverdue ? 'text-danger font-weight-bold' : 'text-dark' }}">
                                            {{ $loan->due_date_bs ?: $loan->due_date }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($isOverdue)
                                            <span class="badge badge-danger" style="border-radius: 6px; padding: 4px 8px;">
                                                <i class="icon-warning2 mr-1 font-size-xs"></i>{{ __('Overdue by :days days', ['days' => $daysDiff]) }}
                                            </span>
                                        @else
                                            <span class="badge badge-success" style="border-radius: 6px; padding: 4px 8px;">
                                                {{ __(':days days remaining', ['days' => $daysDiff]) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-muted font-size-xs">{{ $loan->issuer ? $loan->issuer->name : '-' }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if(Qs::userIsTeamLibrary())
                                            <button type="button" class="btn btn-sm btn-outline-success font-weight-semibold" style="border-radius: 6px;" onclick="openReturnModal({{ $loan->id }}, '{{ addslashes($loan->book ? $loan->book->name : '') }}', '{{ addslashes($loan->user ? $loan->user->name : '') }}', '{{ $isOverdue ? 1 : 0 }}')">
                                                <i class="icon-undo mr-1"></i> {{ __('Return Book') }}
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="icon-reading icon-2x mb-2 d-block text-muted"></i>
                                        <span class="font-weight-medium">{{ __('No active book circulations currently recorded.') }}</span>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Tab 2: Overdue Loans --}}
                <div class="tab-pane fade" id="overdue-loans">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped datatable-button-html5-columns">
                            <thead class="bg-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>{{ __('Book Title') }}</th>
                                <th>{{ __('Borrower') }}</th>
                                <th>{{ __('Role') }}</th>
                                <th>{{ __('Due Date (BS)') }}</th>
                                <th>{{ __('Due Date (AD)') }}</th>
                                <th>{{ __('Days Overdue') }}</th>
                                <th class="text-center" style="width: 130px;">{{ __('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($overdue_loans as $ov)
                                @php
                                    $daysOverdue = abs(\Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($ov->due_date), false));
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="font-weight-semibold text-danger">
                                            <a href="{{ route('books.show', $ov->book_id) }}" class="text-danger">{{ $ov->book ? $ov->book->name : '-' }}</a>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="font-weight-semibold text-dark">{{ $ov->user ? $ov->user->name : '-' }}</div>
                                        <div class="text-muted font-size-xs">{{ $ov->user ? $ov->user->phone : '' }}</div>
                                    </td>
                                    <td><span class="badge badge-danger">{{ ucfirst($ov->borrower_type) }}</span></td>
                                    <td class="font-weight-bold text-danger">{{ $ov->due_date_bs }}</td>
                                    <td>{{ $ov->due_date }}</td>
                                    <td>
                                        <span class="badge badge-danger font-weight-bold" style="border-radius: 6px; padding: 4px 8px;">
                                            {{ $daysOverdue }} {{ __('Days Late') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if(Qs::userIsTeamLibrary())
                                            <button type="button" class="btn btn-sm btn-danger font-weight-semibold shadow-sm" style="border-radius: 6px;" onclick="openReturnModal({{ $ov->id }}, '{{ addslashes($ov->book ? $ov->book->name : '') }}', '{{ addslashes($ov->user ? $ov->user->name : '') }}', 1)">
                                                <i class="icon-undo mr-1"></i> {{ __('Record Return') }}
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="icon-checkmark-circle icon-2x mb-2 d-block text-success"></i>
                                        <span class="font-weight-medium text-success">{{ __('Excellent! There are no overdue book loans at this time.') }}</span>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Tab 3: Returned History --}}
                <div class="tab-pane fade" id="returned-history">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped datatable-button-html5-columns">
                            <thead class="bg-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>{{ __('Book Title') }}</th>
                                <th>{{ __('Borrower') }}</th>
                                <th>{{ __('Issue Date (BS)') }}</th>
                                <th>{{ __('Returned Date (BS)') }}</th>
                                <th>{{ __('Received By') }}</th>
                                <th>{{ __('Fine Assessed') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($returned_loans as $ret)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="font-weight-semibold text-dark">
                                            <a href="{{ route('books.show', $ret->book_id) }}">{{ $ret->book ? $ret->book->name : '-' }}</a>
                                        </div>
                                    </td>
                                    <td>{{ $ret->user ? $ret->user->name : '-' }}</td>
                                    <td>{{ $ret->issue_date_bs ?: $ret->issue_date }}</td>
                                    <td>{{ $ret->return_date_bs ?: ($ret->return_date ?: '-') }}</td>
                                    <td>{{ $ret->receiver ? $ret->receiver->name : '-' }}</td>
                                    <td>
                                        @if($ret->fine_amount > 0)
                                            <span class="badge badge-danger">Rs. {{ number_format($ret->fine_amount, 2) }}</span>
                                        @else
                                            <span class="text-muted">Rs. 0.00</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-success" style="border-radius: 6px; padding: 4px 8px;">{{ __('Returned') }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="icon-history icon-2x mb-2 d-block text-muted"></i>
                                        <span class="font-weight-medium">{{ __('No archived circulation records available.') }}</span>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Issue Book --}}
    @if(Qs::userIsTeamLibrary())
        <div id="modal-issue-book" class="modal fade" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content shadow" style="border-radius: 12px; overflow: hidden;">
                    <div class="modal-header d-flex align-items-center justify-content-between" style="background: #111827; color: #FFFFFF;">
                        <h6 class="modal-title font-weight-bold mb-0 text-white"><i class="icon-reading mr-2" style="color: #FF5700;"></i>{{ __('Issue Book to Student or Staff') }}</h6>
                        <button type="button" class="close text-white" data-dismiss="modal" style="opacity: 0.8;">&times;</button>
                    </div>

                    <form method="post" action="{{ route('books.issue_book') }}">
                        @csrf
                        <div class="modal-body p-4">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-semibold text-dark">{{ __('Select Book') }} <span class="text-danger">*</span></label>
                                    <select class="form-control select" name="book_id" required>
                                        <option value="">{{ __('Choose a Book...') }}</option>
                                        @foreach($books as $b)
                                            <option value="{{ $b->id }}" {{ (request('book_id') == $b->id) ? 'selected' : '' }}>
                                                {{ $b->name }} ({{ __('Available') }}: {{ $b->available_copies }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-semibold text-dark">{{ __('Select Borrower') }} <span class="text-danger">*</span></label>
                                    <select class="form-control select" name="user_id" required>
                                        <option value="">{{ __('Choose Student or Staff...') }}</option>
                                        @foreach($borrowers as $borrower)
                                            <option value="{{ $borrower->id }}">
                                                {{ $borrower->name }} [{{ strtoupper($borrower->user_type) }}] ({{ $borrower->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-semibold text-dark">{{ __('Issue Date (BS - YYYY-MM-DD)') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="issue_date_bs" value="{{ $today_bs }}" class="form-control nepali-date" required placeholder="YYYY-MM-DD">
                                    <span class="form-text text-muted font-size-xs">{{ __('Default: Today in Bikram Sambat') }}</span>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-semibold text-dark">{{ __('Due Date (BS - YYYY-MM-DD)') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="due_date_bs" value="{{ $default_due_bs }}" class="form-control nepali-date" required placeholder="YYYY-MM-DD">
                                    <span class="form-text text-muted font-size-xs">{{ __('Default loan period: 14 days') }}</span>
                                </div>
                            </div>

                            <div class="form-group mb-0">
                                <label class="font-weight-semibold text-dark">{{ __('Circulation Notes (Optional)') }}</label>
                                <textarea name="notes" rows="2" class="form-control" placeholder="{{ __('e.g. Clean condition, issued for semester study...') }}"></textarea>
                            </div>
                        </div>

                        <div class="modal-footer bg-light p-3">
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn text-white btn-sm font-weight-semibold shadow-sm" style="background: #FF5700; border-radius: 8px;">
                                <i class="icon-checkmark mr-1"></i>{{ __('Confirm & Issue Book') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal: Return Book --}}
        <div id="modal-return-book" class="modal fade" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow" style="border-radius: 12px; overflow: hidden;">
                    <div class="modal-header d-flex align-items-center justify-content-between" style="background: #111827; color: #FFFFFF;">
                        <h6 class="modal-title font-weight-bold mb-0 text-white"><i class="icon-undo mr-2 text-success"></i>{{ __('Process Book Return') }}</h6>
                        <button type="button" class="close text-white" data-dismiss="modal" style="opacity: 0.8;">&times;</button>
                    </div>

                    <form id="form-return-book" method="post" action="">
                        @csrf
                        <div class="modal-body p-4">
                            <div class="p-3 bg-light rounded border mb-3">
                                <div><strong>{{ __('Book:') }}</strong> <span id="return-book-title" class="text-dark font-weight-semibold"></span></div>
                                <div class="mt-1"><strong>{{ __('Borrower:') }}</strong> <span id="return-borrower-name" class="text-dark"></span></div>
                            </div>

                            <div class="form-group mb-3">
                                <label class="font-weight-semibold text-dark">{{ __('Return Date (BS - YYYY-MM-DD)') }} <span class="text-danger">*</span></label>
                                <input type="text" name="return_date_bs" value="{{ $today_bs }}" class="form-control nepali-date" required>
                            </div>

                            <div class="form-group mb-3" id="fine-section">
                                <label class="font-weight-semibold text-dark">{{ __('Overdue Fine Amount (NPR / Rs.)') }}</label>
                                <input type="number" step="0.01" min="0" name="fine_amount" value="0.00" class="form-control">
                                <span class="form-text text-muted font-size-xs">{{ __('Optional fine if returned past due date') }}</span>
                            </div>

                            <div class="form-group mb-0">
                                <label class="font-weight-semibold text-dark">{{ __('Return Condition / Notes') }}</label>
                                <textarea name="notes" rows="2" class="form-control" placeholder="{{ __('e.g. Good condition, returned in person...') }}"></textarea>
                            </div>
                        </div>

                        <div class="modal-footer bg-light p-3">
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-success btn-sm font-weight-semibold shadow-sm" style="border-radius: 8px;">
                                <i class="icon-checkmark mr-1"></i>{{ __('Record Book Return') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

@endsection

@section('scripts')
<script>
    function openReturnModal(requestId, bookTitle, borrowerName, isOverdue) {
        var actionUrl = "{{ url('books/circulations') }}/" + requestId + "/return";
        $('#form-return-book').attr('action', actionUrl);
        $('#return-book-title').text(bookTitle);
        $('#return-borrower-name').text(borrowerName);
        if (isOverdue) {
            $('#fine-section').show();
        }
        $('#modal-return-book').modal('show');
    }
</script>
@endsection
