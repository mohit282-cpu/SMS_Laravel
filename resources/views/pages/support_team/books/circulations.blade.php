@extends('layouts.master')
@section('page_title', __('Library Circulations: Issue & Return'))
@section('content')

    {{-- Top Action Row --}}
    <div class="row mb-3">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <h4 class="mb-0 font-weight-semibold">
                <i class="icon-sync mr-2 text-primary"></i>{{ __('Circulation Management') }}
            </h4>
            @if(Qs::userIsTeamLibrary())
                <button type="button" class="btn btn-primary font-weight-bold" data-toggle="modal" data-target="#modal-issue-book">
                    <i class="icon-plus2 mr-2"></i>{{ __('Issue Book to Borrower') }}
                </button>
            @endif
        </div>
    </div>

    {{-- Main Circulations Card --}}
    <div class="card">
        <div class="card-header header-elements-inline">
            <h6 class="card-title font-weight-semibold">{{ __('Circulation Records') }}</h6>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <ul class="nav nav-tabs nav-tabs-highlight">
                <li class="nav-item">
                    <a href="#active-loans" class="nav-link active" data-toggle="tab">
                        <i class="icon-reading mr-2"></i>{{ __('Active Issued Books') }} ({{ count($active_loans) }})
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#overdue-loans" class="nav-link" data-toggle="tab">
                        <i class="icon-alarm mr-2 text-danger"></i>
                        <span class="text-danger">{{ __('Overdue Loans') }} ({{ count($overdue_loans) }})</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#returned-history" class="nav-link" data-toggle="tab">
                        <i class="icon-history mr-2"></i>{{ __('Returned History') }} ({{ count($returned_loans) }})
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                {{-- Tab 1: Active Issued Books --}}
                <div class="tab-pane fade show active" id="active-loans">
                    <table class="table datatable-button-html5-columns table-striped table-hover">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Book Title') }}</th>
                            <th>{{ __('Borrower') }}</th>
                            <th>{{ __('Borrower Role') }}</th>
                            <th>{{ __('Issue Date (BS)') }}</th>
                            <th>{{ __('Due Date (BS)') }}</th>
                            <th>{{ __('Days Left / Overdue') }}</th>
                            <th>{{ __('Issued By') }}</th>
                            <th class="text-center">{{ __('Action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($active_loans as $loan)
                            @php
                                $today = \Carbon\Carbon::today();
                                $dueDate = \Carbon\Carbon::parse($loan->due_date);
                                $isOverdue = $today->gt($dueDate);
                                $daysDiff = abs($today->diffInDays($dueDate, false));
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <a href="{{ route('books.show', $loan->book_id) }}" class="font-weight-semibold">
                                        {{ $loan->book ? $loan->book->name : '-' }}
                                    </a>
                                    @if($loan->book && $loan->book->isbn)
                                        <div class="text-muted font-size-xs">ISBN: {{ $loan->book->isbn }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="font-weight-semibold">{{ $loan->user ? $loan->user->name : '-' }}</div>
                                    <div class="text-muted font-size-xs">{{ $loan->user ? $loan->user->email : '' }}</div>
                                </td>
                                <td>
                                    <span class="badge badge-light border-left-info badge-striped">
                                        {{ ucfirst($loan->borrower_type) }}
                                    </span>
                                </td>
                                <td>{{ $loan->issue_date_bs ?: $loan->issue_date }}</td>
                                <td>{{ $loan->due_date_bs ?: $loan->due_date }}</td>
                                <td>
                                    @if($isOverdue)
                                        <span class="badge badge-danger font-weight-bold">
                                            {{ __('Overdue by :days days', ['days' => $daysDiff]) }}
                                        </span>
                                    @else
                                        <span class="badge badge-success">
                                            {{ __(':days days remaining', ['days' => $daysDiff]) }}
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $loan->issuer ? $loan->issuer->name : '-' }}</td>
                                <td class="text-center">
                                    @if(Qs::userIsTeamLibrary())
                                        <button type="button" class="btn btn-sm btn-outline-success font-weight-bold" onclick="openReturnModal({{ $loan->id }}, '{{ addslashes($loan->book ? $loan->book->name : '') }}', '{{ addslashes($loan->user ? $loan->user->name : '') }}', '{{ $isOverdue ? 1 : 0 }}')">
                                            <i class="icon-undo mr-1"></i> {{ __('Return Book') }}
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Tab 2: Overdue Loans --}}
                <div class="tab-pane fade" id="overdue-loans">
                    <table class="table datatable-button-html5-columns table-striped table-hover">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Book Title') }}</th>
                            <th>{{ __('Borrower') }}</th>
                            <th>{{ __('Borrower Role') }}</th>
                            <th>{{ __('Due Date (BS)') }}</th>
                            <th>{{ __('Due Date (AD)') }}</th>
                            <th>{{ __('Days Overdue') }}</th>
                            <th class="text-center">{{ __('Action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($overdue_loans as $ov)
                            @php
                                $daysOverdue = abs(\Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($ov->due_date), false));
                            @endphp
                            <tr class="table-danger">
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <a href="{{ route('books.show', $ov->book_id) }}" class="font-weight-semibold text-danger">
                                        {{ $ov->book ? $ov->book->name : '-' }}
                                    </a>
                                </td>
                                <td>
                                    <div class="font-weight-semibold">{{ $ov->user ? $ov->user->name : '-' }}</div>
                                    <div class="text-muted font-size-xs">{{ $ov->user ? $ov->user->phone : '' }}</div>
                                </td>
                                <td><span class="badge badge-danger">{{ ucfirst($ov->borrower_type) }}</span></td>
                                <td class="font-weight-bold">{{ $ov->due_date_bs }}</td>
                                <td>{{ $ov->due_date }}</td>
                                <td>
                                    <span class="badge badge-danger font-weight-bold">{{ $daysOverdue }} {{ __('Days') }}</span>
                                </td>
                                <td class="text-center">
                                    @if(Qs::userIsTeamLibrary())
                                        <button type="button" class="btn btn-sm btn-danger font-weight-bold" onclick="openReturnModal({{ $ov->id }}, '{{ addslashes($ov->book ? $ov->book->name : '') }}', '{{ addslashes($ov->user ? $ov->user->name : '') }}', 1)">
                                            <i class="icon-undo mr-1"></i> {{ __('Record Return') }}
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Tab 3: Returned History --}}
                <div class="tab-pane fade" id="returned-history">
                    <table class="table datatable-button-html5-columns table-striped table-hover">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Book Title') }}</th>
                            <th>{{ __('Borrower') }}</th>
                            <th>{{ __('Issue Date (BS)') }}</th>
                            <th>{{ __('Returned Date (BS)') }}</th>
                            <th>{{ __('Received By') }}</th>
                            <th>{{ __('Fine (NPR)') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($returned_loans as $ret)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <a href="{{ route('books.show', $ret->book_id) }}" class="font-weight-semibold">
                                        {{ $ret->book ? $ret->book->name : '-' }}
                                    </a>
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
                                    <span class="badge badge-success">{{ __('Returned') }}</span>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Issue Book --}}
    @if(Qs::userIsTeamLibrary())
        <div id="modal-issue-book" class="modal fade" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h6 class="modal-title font-weight-semibold"><i class="icon-reading mr-2"></i>{{ __('Issue Book to Student or Staff') }}</h6>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>

                    <form method="post" action="{{ route('books.issue_book') }}">
                        @csrf
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-semibold">{{ __('Select Book') }} <span class="text-danger">*</span></label>
                                        <select class="form-control select" name="book_id" required>
                                            <option value="">{{ __('Choose a Book...') }}</option>
                                            @foreach($books as $b)
                                                <option value="{{ $b->id }}" {{ (request('book_id') == $b->id) ? 'selected' : '' }}>
                                                    {{ $b->name }} ({{ __('Available') }}: {{ $b->available_copies }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-semibold">{{ __('Select Borrower') }} <span class="text-danger">*</span></label>
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
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-semibold">{{ __('Issue Date (BS - YYYY-MM-DD)') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="issue_date_bs" value="{{ $today_bs }}" class="form-control" required placeholder="YYYY-MM-DD">
                                        <span class="form-text text-muted font-size-xs">{{ __('Default: Today in Bikram Sambat') }}</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-semibold">{{ __('Due Date (BS - YYYY-MM-DD)') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="due_date_bs" value="{{ $default_due_bs }}" class="form-control" required placeholder="YYYY-MM-DD">
                                        <span class="form-text text-muted font-size-xs">{{ __('Default loan period: 14 days') }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-semibold">{{ __('Circulation Notes (Optional)') }}</label>
                                <textarea name="notes" rows="2" class="form-control" placeholder="{{ __('e.g. Clean condition, issued for class project') }}"></textarea>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-link" data-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-primary font-weight-bold">
                                <i class="icon-check mr-2"></i>{{ __('Confirm & Issue Book') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal: Return Book --}}
        <div id="modal-return-book" class="modal fade" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h6 class="modal-title font-weight-semibold"><i class="icon-undo mr-2"></i>{{ __('Process Book Return') }}</h6>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>

                    <form id="form-return-book" method="post" action="">
                        @csrf
                        <div class="modal-body">
                            <div class="alert alert-info border-0">
                                <strong>{{ __('Book:') }}</strong> <span id="return-book-title"></span><br>
                                <strong>{{ __('Borrower:') }}</strong> <span id="return-borrower-name"></span>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-semibold">{{ __('Return Date (BS - YYYY-MM-DD)') }} <span class="text-danger">*</span></label>
                                <input type="text" name="return_date_bs" value="{{ $today_bs }}" class="form-control" required>
                            </div>

                            <div class="form-group" id="fine-section">
                                <label class="font-weight-semibold">{{ __('Overdue Fine Amount (NPR / Rs.)') }}</label>
                                <input type="number" step="0.01" min="0" name="fine_amount" value="0.00" class="form-control">
                                <span class="form-text text-muted font-size-xs">{{ __('Optional fine if returned past due date') }}</span>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-semibold">{{ __('Return Condition / Notes') }}</label>
                                <textarea name="notes" rows="2" class="form-control" placeholder="{{ __('e.g. Good condition, returned on time') }}"></textarea>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-link" data-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-success font-weight-bold">
                                <i class="icon-check mr-2"></i>{{ __('Record Book Return') }}
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
