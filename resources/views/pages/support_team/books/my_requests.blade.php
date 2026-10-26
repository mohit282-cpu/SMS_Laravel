@extends('layouts.master')
@section('page_title', __('My Library Account: Books & Requests'))
@section('content')

    <div class="row">
        {{-- Left: Currently Borrowed Books --}}
        <div class="col-md-7">
            <div class="card">
                <div class="card-header header-elements-inline bg-light">
                    <h6 class="card-title font-weight-semibold text-primary">
                        <i class="icon-reading mr-2"></i>{{ __('Currently Borrowed Books') }} ({{ count($my_loans) }})
                    </h6>
                    <div class="header-elements">
                        <a href="{{ route('books.catalogue') }}" class="btn btn-sm btn-outline-primary font-weight-bold">
                            <i class="icon-search4 mr-1"></i> {{ __('Browse Catalogue') }}
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if(count($my_loans) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('Book Title') }}</th>
                                    <th>{{ __('Issue Date (BS)') }}</th>
                                    <th>{{ __('Due Date (BS)') }}</th>
                                    <th>{{ __('Status') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($my_loans as $loan)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="font-weight-semibold">
                                                <a href="{{ route('books.show', $loan->book_id) }}">{{ $loan->book ? $loan->book->name : '-' }}</a>
                                            </div>
                                            @if($loan->book && $loan->book->author)
                                                <div class="text-muted font-size-xs">{{ $loan->book->author }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $loan->issue_date_bs ?: $loan->issue_date }}</td>
                                        <td>
                                            <span class="{{ $loan->is_overdue ? 'text-danger font-weight-bold' : '' }}">
                                                {{ $loan->due_date_bs ?: $loan->due_date }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($loan->is_overdue)
                                                <span class="badge badge-danger font-weight-bold">{{ __('Overdue') }}</span>
                                            @else
                                                <span class="badge badge-success">{{ __('Borrowed') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="icon-book2 icon-2x mb-2 text-muted"></i>
                            <p class="mb-0">{{ __('You currently have no books on loan.') }}</p>
                            <a href="{{ route('books.catalogue') }}" class="btn btn-sm btn-primary mt-2">
                                {{ __('Explore Library Catalogue') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right: My Book Requests --}}
        <div class="col-md-5">
            <div class="card">
                <div class="card-header header-elements-inline bg-light">
                    <h6 class="card-title font-weight-semibold text-warning">
                        <i class="icon-hour-glass2 mr-2"></i>{{ __('My Book Requests') }} ({{ count($my_requests) }})
                    </h6>
                </div>

                <div class="card-body">
                    @if(count($my_requests) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('Book Title') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th class="text-center">{{ __('Action') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($my_requests as $req)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="font-weight-semibold">{{ $req->book ? $req->book->name : '-' }}</span>
                                        </td>
                                        <td>{{ $req->request_date_bs ?: $req->created_at->format('Y-m-d') }}</td>
                                        <td>
                                            @if($req->status == 'pending')
                                                <span class="badge badge-warning">{{ __('Pending') }}</span>
                                            @elseif($req->status == 'approved')
                                                <span class="badge badge-success">{{ __('Approved') }}</span>
                                            @elseif($req->status == 'issued')
                                                <span class="badge badge-primary">{{ __('Issued') }}</span>
                                            @elseif($req->status == 'rejected')
                                                <span class="badge badge-danger">{{ __('Rejected') }}</span>
                                            @elseif($req->status == 'cancelled')
                                                <span class="badge badge-secondary">{{ __('Cancelled') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($req->status == 'pending')
                                                <form method="post" action="{{ route('book_requests.cancel', $req->id) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-xs btn-outline-danger" onclick="return confirm('{{ __('Cancel this request?') }}')">
                                                        <i class="icon-cross2"></i>
                                                    </button>
                                                </form>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <p class="mb-0">{{ __('You have not submitted any book requests.') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection
