@extends('layouts.master')
@section('page_title', __('Book Details: :name', ['name' => $book->name]))
@section('content')

    <div class="row">
        {{-- Book Metadata Left Column --}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-header header-elements-inline">
                    <h6 class="card-title font-weight-semibold">{{ __('Book Profile') }}</h6>
                    <span class="badge {{ $book->available_copies > 0 ? 'badge-success' : 'badge-danger' }}">
                        {{ $book->available_copies > 0 ? __('Available') : __('Out of Stock') }}
                    </span>
                </div>

                <div class="card-body text-center">
                    @if($book->cover_image)
                        <img src="{{ asset('storage/' . $book->cover_image) }}" class="img-fluid rounded mb-3 shadow" style="max-height: 240px;" alt="{{ $book->name }}">
                    @else
                        <div class="p-4 bg-light rounded mb-3 border">
                            <i class="icon-book icon-4x text-muted opacity-50"></i>
                        </div>
                    @endif

                    <h5 class="font-weight-semibold mb-1">{{ $book->name }}</h5>
                    <p class="text-muted mb-3">{{ $book->author ?: __('Author Unknown') }}</p>

                    <div class="d-flex justify-content-center mb-3">
                        @if(Qs::userIsTeamLibrary())
                            <a href="{{ route('books.edit', $book->id) }}" class="btn btn-primary btn-sm mr-2">
                                <i class="icon-pencil mr-1"></i> {{ __('Edit Book') }}
                            </a>
                            @if($book->available_copies > 0)
                                <a href="{{ route('books.circulations') }}?book_id={{ $book->id }}" class="btn btn-success btn-sm">
                                    <i class="icon-reading mr-1"></i> {{ __('Issue Book') }}
                                </a>
                            @endif
                        @else
                            @if($book->available_copies > 0)
                                <form method="post" action="{{ route('book_requests.store') }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="book_id" value="{{ $book->id }}">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="icon-paperplane mr-1"></i> {{ __('Request This Book') }}
                                    </button>
                                </form>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-borderless table-xs">
                        <tbody>
                        <tr>
                            <td class="font-weight-semibold text-muted">{{ __('ISBN') }}:</td>
                            <td class="text-right"><code>{{ $book->isbn ?: '-' }}</code></td>
                        </tr>
                        <tr>
                            <td class="font-weight-semibold text-muted">{{ __('Category') }}:</td>
                            <td class="text-right">
                                @if($book->category)
                                    <span class="badge badge-light border-left-primary badge-striped">{{ $book->category->name }}</span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-semibold text-muted">{{ __('Publisher') }}:</td>
                            <td class="text-right">{{ $book->publisher ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-semibold text-muted">{{ __('Edition') }}:</td>
                            <td class="text-right">{{ $book->edition ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-semibold text-muted">{{ __('Pub. Year') }}:</td>
                            <td class="text-right">{{ $book->publication_year ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-semibold text-muted">{{ __('Language') }}:</td>
                            <td class="text-right">{{ $book->language ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-semibold text-muted">{{ __('Class / Grade') }}:</td>
                            <td class="text-right">{{ $book->my_class ? $book->my_class->name : __('General') }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-semibold text-muted">{{ __('Shelf Location') }}:</td>
                            <td class="text-right"><span class="badge badge-info">{{ $book->location ?: '-' }}</span></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Inventory Breakdown Card --}}
            <div class="card">
                <div class="card-header header-elements-inline">
                    <h6 class="card-title font-weight-semibold">{{ __('Inventory Status') }}</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ __('Total Copies') }}</span>
                        <span class="font-weight-bold">{{ $book->total_copies }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ __('Available for Loan') }}</span>
                        <span class="font-weight-bold text-success">{{ $book->available_copies }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>{{ __('Currently Issued') }}</span>
                        <span class="font-weight-bold text-danger">{{ $book->issued_copies }}</span>
                    </div>

                    @php
                        $issuedPct = $book->total_copies > 0 ? round(($book->issued_copies / $book->total_copies) * 100) : 0;
                    @endphp
                    <div class="progress mb-2" style="height: 10px;">
                        <div class="progress-bar bg-danger" style="width: {{ $issuedPct }}%"></div>
                        <div class="progress-bar bg-success" style="width: {{ 100 - $issuedPct }}%"></div>
                    </div>
                    <span class="font-size-xs text-muted">{{ $issuedPct }}% {{ __('copies currently loaned out') }}</span>
                </div>
            </div>
        </div>

        {{-- Book Loans & Request Right Column --}}
        <div class="col-md-8">
            {{-- Description Card --}}
            @if($book->description)
                <div class="card">
                    <div class="card-header header-elements-inline">
                        <h6 class="card-title font-weight-semibold">{{ __('Description / Synopsis') }}</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0 text-muted">{{ $book->description }}</p>
                    </div>
                </div>
            @endif

            {{-- Active Loans Card --}}
            <div class="card">
                <div class="card-header header-elements-inline">
                    <h6 class="card-title font-weight-semibold"><i class="icon-reading mr-2"></i>{{ __('Current Active Loans') }}</h6>
                    <span class="badge badge-primary">{{ count($book->activeLoans) }} {{ __('Active') }}</span>
                </div>

                <div class="card-body">
                    @if(count($book->activeLoans) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                <tr>
                                    <th>{{ __('Borrower') }}</th>
                                    <th>{{ __('Role') }}</th>
                                    <th>{{ __('Issue Date (BS)') }}</th>
                                    <th>{{ __('Due Date (BS)') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    @if(Qs::userIsTeamLibrary())
                                        <th class="text-center">{{ __('Action') }}</th>
                                    @endif
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($book->activeLoans as $loan)
                                    <tr>
                                        <td>
                                            <div class="font-weight-semibold">{{ $loan->user ? $loan->user->name : '-' }}</div>
                                            <div class="text-muted font-size-xs">{{ $loan->user ? $loan->user->email : '' }}</div>
                                        </td>
                                        <td><span class="badge badge-light">{{ ucfirst($loan->borrower_type) }}</span></td>
                                        <td>{{ $loan->issue_date_bs ?: $loan->issue_date }}</td>
                                        <td>
                                            <span class="{{ $loan->is_overdue ? 'text-danger font-weight-bold' : '' }}">
                                                {{ $loan->due_date_bs ?: $loan->due_date }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($loan->is_overdue)
                                                <span class="badge badge-danger">{{ __('Overdue') }}</span>
                                            @else
                                                <span class="badge badge-primary">{{ __('Issued') }}</span>
                                            @endif
                                        </td>
                                        @if(Qs::userIsTeamLibrary())
                                            <td class="text-center">
                                                <form method="post" action="{{ route('books.return_book', $loan->id) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('{{ __('Confirm returning this book to library inventory?') }}')">
                                                        <i class="icon-undo mr-1"></i> {{ __('Return') }}
                                                    </button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="icon-info22 icon-2x mb-2 text-muted"></i>
                            <p class="mb-0">{{ __('No copies of this book are currently on loan.') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Past Circulation History Card --}}
            <div class="card">
                <div class="card-header header-elements-inline">
                    <h6 class="card-title font-weight-semibold"><i class="icon-history mr-2"></i>{{ __('Circulation History') }}</h6>
                    <a href="{{ route('books.index') }}" class="btn btn-sm btn-outline-dark">
                        <i class="icon-arrow-left7 mr-1"></i> {{ __('Back to Inventory') }}
                    </a>
                </div>

                <div class="card-body">
                    @if(isset($recent_loans) && count($recent_loans) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                <tr>
                                    <th>{{ __('Borrower') }}</th>
                                    <th>{{ __('Issued') }}</th>
                                    <th>{{ __('Due Date') }}</th>
                                    <th>{{ __('Returned Date') }}</th>
                                    <th>{{ __('Status') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($recent_loans as $hist)
                                    <tr>
                                        <td>{{ $hist->user ? $hist->user->name : '-' }}</td>
                                        <td>{{ $hist->issue_date_bs ?: $hist->issue_date }}</td>
                                        <td>{{ $hist->due_date_bs ?: $hist->due_date }}</td>
                                        <td>{{ $hist->return_date_bs ?: ($hist->return_date ?: '-') }}</td>
                                        <td>
                                            @if($hist->status == 'returned')
                                                <span class="badge badge-success">{{ __('Returned') }}</span>
                                            @elseif($hist->status == 'issued')
                                                <span class="badge badge-primary">{{ __('Issued') }}</span>
                                            @elseif($hist->status == 'lost')
                                                <span class="badge badge-dark">{{ __('Lost') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-3">
                            <p class="mb-0">{{ __('No past loan records found for this book.') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection
