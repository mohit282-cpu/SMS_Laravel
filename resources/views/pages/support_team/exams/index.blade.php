@extends('layouts.master')
@section('page_title', __('Manage Examinations'))
@section('content')

    <div class="card">
        <div class="card-header header-elements-inline">
            <h6 class="card-title font-weight-bold">{{ __('Manage Examinations & Terminal Evaluations') }}</h6>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <ul class="nav nav-tabs nav-tabs-highlight">
                <li class="nav-item"><a href="#all-exams" class="nav-link active" data-toggle="tab"><i class="icon-list mr-1"></i> {{ __('All Examinations') }}</a></li>
                <li class="nav-item"><a href="#new-exam" class="nav-link" data-toggle="tab"><i class="icon-plus2 mr-1"></i> {{ __('Create Examination') }}</a></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="all-exams">
                    <table class="table datatable-button-html5-columns">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Exam Name') }}</th>
                            <th>{{ __('Evaluation Type') }}</th>
                            <th>{{ __('Term') }}</th>
                            <th>{{ __('Session') }}</th>
                            <th>{{ __('Dates (BS)') }}</th>
                            <th>{{ __('Result Status') }}</th>
                            <th>{{ __('Action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($exams as $ex)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="font-weight-semibold">{{ $ex->name }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $ex->exam_type ?? 'terminal')) }}</td>
                                <td>{{ __('Term') }} {{ $ex->term }}</td>
                                <td>{{ $ex->year }}</td>
                                <td>
                                    @if($ex->start_date_bs)
                                        {{ $ex->start_date_bs }} @if($ex->end_date_bs && $ex->end_date_bs !== $ex->start_date_bs) - {{ $ex->end_date_bs }} @endif BS
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if(($ex->status ?? 'published') === 'published')
                                        <span class="badge badge-success p-2">{{ __('Published') }}</span>
                                    @elseif($ex->status === 'completed')
                                        <span class="badge badge-info p-2">{{ __('Completed (Unpublished)') }}</span>
                                    @elseif($ex->status === 'ongoing')
                                        <span class="badge badge-warning p-2 text-white">{{ __('Ongoing') }}</span>
                                    @else
                                        <span class="badge badge-secondary p-2">{{ ucfirst($ex->status ?? 'Draft') }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="list-icons">
                                        <div class="dropdown">
                                            <a href="#" class="list-icons-item" data-toggle="dropdown">
                                                <i class="icon-menu9"></i>
                                            </a>

                                            <div class="dropdown-menu dropdown-menu-left">
                                                @if(Qs::userIsTeamSA())
                                                    <a href="{{ route('exams.edit', $ex->id) }}" class="dropdown-item"><i class="icon-pencil"></i> {{ __('Edit') }}</a>

                                                    @if(($ex->status ?? 'published') !== 'published')
                                                        <form method="post" action="{{ route('exams.publish', $ex->id) }}">
                                                            @csrf @method('put')
                                                            <button type="submit" class="dropdown-item text-success"><i class="icon-checkmark"></i> {{ __('Publish Results') }}</button>
                                                        </form>
                                                    @else
                                                        <form method="post" action="{{ route('exams.unpublish', $ex->id) }}">
                                                            @csrf @method('put')
                                                            <button type="submit" class="dropdown-item text-warning"><i class="icon-eye-blocked"></i> {{ __('Unpublish Results') }}</button>
                                                        </form>
                                                    @endif
                                                @endif

                                                @if(Qs::userIsSuperAdmin())
                                                    <form method="post" action="{{ route('exams.destroy', $ex->id) }}" onsubmit="return confirm('{{ __('Are you sure you want to delete this examination?') }}');">
                                                        @csrf @method('delete')
                                                        <button type="submit" class="dropdown-item text-danger"><i class="icon-trash"></i> {{ __('Delete') }}</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade" id="new-exam">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-info border-0 alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                                <span>{{ __('Creating Examination for Academic Session:') }} <strong>{{ Qs::getCurrentSession() }}</strong></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <form method="post" action="{{ route('exams.store') }}">
                                @csrf
                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Exam Name') }} <span class="text-danger">*</span></label>
                                    <div class="col-lg-9">
                                        <input name="name" value="{{ old('name') }}" required type="text" class="form-control" placeholder="e.g. First Terminal Examination">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Evaluation Type') }} <span class="text-danger">*</span></label>
                                    <div class="col-lg-9">
                                        <select class="form-control select" name="exam_type">
                                            <option value="first_terminal">{{ __('First Terminal Examination (प्रथम त्रैमासिक)') }}</option>
                                            <option value="second_terminal">{{ __('Second Terminal Examination (दोस्रो त्रैमासिक)') }}</option>
                                            <option value="third_terminal">{{ __('Third Terminal Examination (तेस्रो त्रैमासिक)') }}</option>
                                            <option value="mid_term">{{ __('Mid-Term Examination (मध्यसत्र)') }}</option>
                                            <option value="annual">{{ __('Annual Examination (वार्षिक परीक्षा)') }}</option>
                                            <option value="unit_test">{{ __('Unit Test / Continuous Assessment (एकाइ परीक्षा)') }}</option>
                                            <option value="practical">{{ __('Practical / Project Evaluation (प्रयोगात्मक परीक्षा)') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="term" class="col-lg-3 col-form-label font-weight-semibold">{{ __('Term') }} <span class="text-danger">*</span></label>
                                    <div class="col-lg-9">
                                        <select class="form-control select" name="term" id="term" required>
                                            <option {{ old('term') == 1 ? 'selected' : '' }} value="1">{{ __('Term 1 (प्रथम सत्र)') }}</option>
                                            <option {{ old('term') == 2 ? 'selected' : '' }} value="2">{{ __('Term 2 (दोस्रो सत्र)') }}</option>
                                            <option {{ old('term') == 3 ? 'selected' : '' }} value="3">{{ __('Term 3 (तेस्रो सत्र)') }}</option>
                                            <option {{ old('term') == 4 ? 'selected' : '' }} value="4">{{ __('Term 4 (चौथो सत्र)') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Start Date (BS)') }}:</label>
                                    <div class="col-lg-9">
                                        <input name="start_date_bs" value="{{ old('start_date_bs') }}" type="text" class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('End Date (BS)') }}:</label>
                                    <div class="col-lg-9">
                                        <input name="end_date_bs" value="{{ old('end_date_bs') }}" type="text" class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Initial Status') }}:</label>
                                    <div class="col-lg-9">
                                        <select class="form-control select" name="status">
                                            <option value="draft">{{ __('Draft (मस्यौदा)') }}</option>
                                            <option value="scheduled">{{ __('Scheduled (तालिकाबद्ध)') }}</option>
                                            <option value="ongoing">{{ __('Ongoing (सञ्चालनमा)') }}</option>
                                            <option value="completed">{{ __('Completed (सम्पन्न)') }}</option>
                                            <option value="published" selected>{{ __('Published (प्रकाशित)') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Description') }}:</label>
                                    <div class="col-lg-9">
                                        <textarea name="description" class="form-control" rows="2" placeholder="{{ __('Optional notes or guidelines') }}">{{ old('description') }}</textarea>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <button type="submit" class="btn btn-primary">{{ __('Create Examination') }} <i class="icon-paperplane ml-2"></i></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
