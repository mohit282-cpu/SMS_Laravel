@extends('layouts.master')
@section('page_title', __('Entrance Exam Results Management'))

@section('content')

    <!-- Subnav -->
    <div class="card mb-3">
        <div class="card-body p-2 d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center">
                <a href="{{ route('landing_settings.index') }}" class="btn btn-light btn-sm mr-2 font-weight-semibold">
                    <i class="icon-gear mr-1"></i> {{ __('General & Admissions Controls') }}
                </a>
                <a href="{{ route('landing_settings.inquiries') }}" class="btn btn-light btn-sm mr-2 font-weight-semibold">
                    <i class="icon-bubbles4 mr-1"></i> {{ __('View Inquiries') }}
                </a>
                <a href="{{ route('landing_settings.entrance_results') }}" class="btn btn-primary btn-sm font-weight-semibold">
                    <i class="icon-file-text2 mr-1"></i> {{ __('Manage Entrance Exam Results') }} ({{ $results->total() }})
                </a>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-success btn-sm font-weight-semibold mr-2" data-toggle="modal" data-target="#uploadCsvModal">
                    <i class="icon-upload mr-1"></i> {{ __('Bulk Import CSV') }}
                </button>
                <a href="{{ route('admissions.results') }}" target="_blank" class="btn btn-outline-dark btn-sm font-weight-semibold">
                    <i class="icon-eye mr-1"></i> {{ __('View Public Results Portal') }} ↗
                </a>
            </div>
        </div>
    </div>

    <!-- Filter & Search Card -->
    <div class="card mb-3">
        <div class="card-body py-3">
            <form method="get" action="{{ route('landing_settings.entrance_results') }}" class="row align-items-center">
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="font-weight-bold font-size-xs text-uppercase text-muted mb-1">{{ __('Batch Year') }}:</label>
                    <select name="batch_year" class="form-control select">
                        <option value="">{{ __('All Batches') }}</option>
                        @foreach($batches as $b)
                            <option value="{{ $b }}" {{ $batch === $b ? 'selected' : '' }}>{{ $b }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="font-weight-bold font-size-xs text-uppercase text-muted mb-1">{{ __('Class / Grade') }}:</label>
                    <select name="class_name" class="form-control select">
                        <option value="">{{ __('All Classes') }}</option>
                        @foreach($classes as $c)
                            <option value="{{ $c }}" {{ $class === $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 mb-2 mb-md-0">
                    <label class="font-weight-bold font-size-xs text-uppercase text-muted mb-1">{{ __('Search Symbol / Name') }}:</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="e.g. 1001 or Pooja">
                </div>

                <div class="col-md-2 mt-md-3">
                    <button type="submit" class="btn btn-primary btn-block font-weight-bold">
                        <i class="icon-filter3 mr-1"></i> {{ __('Filter') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Table -->
    <div class="card">
        <div class="card-header header-elements-inline">
            <h2 class="card-title font-weight-bold mb-0" style="font-size: 1.15rem;">
                <i class="icon-file-check mr-2 text-primary"></i> {{ __('Entrance Examination Candidates') }} ({{ $results->total() }})
            </h2>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body p-0">
            @if(count($results) > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr class="bg-light text-dark font-weight-bold">
                                <th>#</th>
                                <th>{{ __('Batch') }}</th>
                                <th>{{ __('Symbol No') }}</th>
                                <th>{{ __('Candidate Name') }}</th>
                                <th>{{ __('Class / Grade') }}</th>
                                <th>{{ __('Section') }}</th>
                                <th>{{ __('GPA (4.0 Scale)') }}</th>
                                <th>{{ __('Remarks') }}</th>
                                <th class="text-center">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $res)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><span class="badge badge-light border font-weight-bold">{{ $res->batch_year }}</span></td>
                                    <td class="font-mono font-weight-bold text-dark">{{ $res->symbol_no }}</td>
                                    <td class="font-weight-bold text-primary">{{ $res->student_name }}</td>
                                    <td>{{ $res->class_name }}</td>
                                    <td>{{ $res->section_name }}</td>
                                    <td>
                                        <span class="badge {{ $res->gpa >= 3.2 ? 'badge-success' : ($res->gpa >= 2.0 ? 'badge-primary' : 'badge-danger') }} p-1 font-size-sm">
                                            {{ number_format((float)$res->gpa, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="font-size-xs font-weight-bold {{ $res->gpa >= 1.6 ? 'text-success' : 'text-danger' }}">
                                            {{ $res->remarks }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <form method="post" action="{{ route('landing_settings.entrance_results.delete', $res->id) }}" onsubmit="return confirm('Delete entrance evaluation for {{ $res->student_name }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger p-1" title="Delete Candidate Record">
                                                <i class="icon-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-3 d-flex justify-content-center">
                    {{ $results->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="icon-file-text2 icon-3x text-muted mb-2"></i>
                    <h4 class="text-muted font-weight-bold">{{ __('No entrance examination records found matching criteria.') }}</h4>
                </div>
            @endif
        </div>
    </div>

    <!-- Upload CSV Modal -->
    <div id="uploadCsvModal" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title font-weight-bold"><i class="icon-upload mr-2"></i>{{ __('Bulk Import Entrance Exam Results (CSV)') }}</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <form method="post" action="{{ route('landing_settings.entrance_results.upload') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info border-0 font-size-xs">
                            <strong>{{ __('Expected CSV Format') }}:</strong>
                            <p class="mb-0">{{ __('Header Row: Symbol No, Roll No, Student Name, Section, Attendance, Subject1, Subject2, Subject3...') }}</p>
                            <p class="mb-0 mt-1">{{ __('Grades (A+, A, B+, etc.) and Grade Points (GP) will be automatically computed using the Nepal National CDC 4.0 scale.') }}</p>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">{{ __('Target Academic Batch') }} *</label>
                            <input type="text" name="batch_year" value="2083 B.S." class="form-control" required placeholder="e.g. 2083 B.S.">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">{{ __('Examination Title') }} *</label>
                            <input type="text" name="exam_name" value="Entrance & Admission Examination 2083" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">{{ __('Grade / Class') }} *</label>
                            <input type="text" name="class_name" value="Grade: 1" class="form-control" required placeholder="e.g. Grade: 1 or Grade: 11 Science">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">{{ __('Select CSV File') }} *</label>
                            <input type="file" name="csv_file" class="form-control-file" accept=".csv,text/csv" required>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-success font-weight-bold">
                            <i class="icon-upload mr-1"></i> {{ __('Start CSV Import') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
