@extends('layouts.master')
@section('page_title', __('Homework & Daily Diary'))
@section('content')

    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                <div class="d-flex align-items-center">
                    <div class="d-inline-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px; border-radius: 14px; background: rgba(255, 87, 0, 0.12);">
                        <i class="icon-notebook icon-2x" style="color: #FF5700;"></i>
                    </div>
                    <div>
                        <h3 class="font-weight-bold mb-1 text-dark">{{ __('Homework, Assignments & Daily Diary') }}</h3>
                        <p class="text-muted mb-0 font-size-sm">{{ __('Digital assignments, teacher daily diary notes, student submissions & annotation remarks') }}</p>
                    </div>
                </div>
                <div class="mt-2 mt-md-0">
                    <span class="badge badge-light border px-3 py-2 text-dark">{{ \App\Helpers\NepaliHelper::formatDualDate(date('Y-m-d')) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-sm-6 col-xl-4 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-left: 4px solid #FF5700 !important;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted font-size-xs text-uppercase font-weight-bold">{{ __('Total Entries') }}</span>
                        <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ $totalHomeworks }}</h3>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-orange"><i class="icon-notebook"></i></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-left: 4px solid #2563eb !important;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted font-size-xs text-uppercase font-weight-bold">{{ __('Daily Diary Notes') }}</span>
                        <h3 class="font-weight-bold text-primary mb-0 mt-1">{{ $diaryCount }}</h3>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-blue"><i class="icon-book3"></i></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-left: 4px solid #10b981 !important;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted font-size-xs text-uppercase font-weight-bold">{{ __('Pending Checks') }}</span>
                        <h3 class="font-weight-bold text-success mb-0 mt-1">{{ $pendingEvaluations }}</h3>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-emerald"><i class="icon-checkmark4"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card sms-card">
        <div class="card-body p-3 p-md-4">
            @if(Qs::userIsTeamSAT())
                <ul class="nav nav-tabs nav-tabs-highlight mb-4">
                    <li class="nav-item"><a href="#hw-list" class="nav-link active" data-toggle="tab"><i class="icon-list mr-1"></i>{{ __('All Homework & Diary') }}</a></li>
                    <li class="nav-item"><a href="#hw-create" class="nav-link" data-toggle="tab"><i class="icon-plus2 mr-1"></i>{{ __('Assign Homework / Diary') }}</a></li>
                </ul>
            @endif

            <div class="tab-content">
                <div class="tab-pane fade show active" id="hw-list">
                    <form method="GET" action="{{ route('homework.index') }}" class="mb-4 p-3 bg-light rounded border">
                        <div class="row align-items-end">
                            <div class="col-md-4 mb-2 mb-md-0">
                                <label for="hw_filter_class" class="font-size-xs font-weight-bold text-uppercase text-muted">{{ __('Class') }}</label>
                                <select id="hw_filter_class" name="class_id" class="form-control form-control-sm">
                                    <option value="">{{ __('All Classes') }}</option>
                                    @foreach($classes as $c)
                                        <option value="{{ $c->id }}" {{ (string) request('class_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-2 mb-md-0">
                                <label for="hw_filter_type" class="font-size-xs font-weight-bold text-uppercase text-muted">{{ __('Type') }}</label>
                                <select id="hw_filter_type" name="homework_type" class="form-control form-control-sm">
                                    <option value="">{{ __('All Types') }}</option>
                                    <option value="homework" {{ request('homework_type') === 'homework' ? 'selected' : '' }}>{{ __('Homework') }}</option>
                                    <option value="assignment" {{ request('homework_type') === 'assignment' ? 'selected' : '' }}>{{ __('Assignment') }}</option>
                                    <option value="daily_diary" {{ request('homework_type') === 'daily_diary' ? 'selected' : '' }}>{{ __('Daily Diary') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex">
                                <button type="submit" class="btn btn-primary btn-sm mr-2"><i class="icon-search4 mr-1"></i>{{ __('Filter') }}</button>
                                <a href="{{ route('homework.index') }}" class="btn btn-light btn-sm">{{ __('Reset') }}</a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('Title') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Class / Subject') }}</th>
                                    <th>{{ __('Assigned (BS)') }}</th>
                                    <th>{{ __('Due (BS)') }}</th>
                                    <th>{{ __('Submissions') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($homeworks as $hw)
                                    <tr>
                                        <td>
                                            <a href="{{ route('homework.show', $hw->id) }}" class="font-weight-semibold text-dark">{{ $hw->title }}</a>
                                            <div class="text-muted font-size-xs">{{ $hw->teacher->name ?? '-' }}</div>
                                        </td>
                                        <td><span class="badge badge-info text-capitalize">{{ str_replace('_', ' ', $hw->homework_type) }}</span></td>
                                        <td>
                                            {{ $hw->my_class->name ?? '-' }} {{ $hw->section ? '(' . $hw->section->name . ')' : '' }}
                                            <div class="text-muted font-size-xs">{{ $hw->subject->name ?? __('General') }}</div>
                                        </td>
                                        <td>{{ $hw->assigned_date_bs ?: \App\Helpers\NepaliHelper::formatDualDate($hw->assigned_date_ad) }}</td>
                                        <td>{{ $hw->due_date_bs ?: ($hw->due_date_ad ? \App\Helpers\NepaliHelper::formatDualDate($hw->due_date_ad) : '-') }}</td>
                                        <td><span class="badge badge-secondary">{{ $hw->submissions_count }}</span></td>
                                        <td>
                                            <a href="{{ route('homework.show', $hw->id) }}" class="btn btn-sm btn-outline-primary">{{ __('Open') }}</a>
                                            @if(Qs::userIsTeamSA() || (int) $hw->teacher_id === (int) Auth::id())
                                                <form method="POST" action="{{ route('homework.destroy', $hw->id) }}" class="d-inline" onsubmit="return confirm('{{ __('Delete this entry?') }}')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('No homework or daily diary entries found.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $homeworks->links() }}</div>
                </div>

                @if(Qs::userIsTeamSAT())
                    <div class="tab-pane fade" id="hw-create">
                        <form method="POST" action="{{ route('homework.store') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="hw_title">{{ __('Title') }} <span class="text-danger">*</span></label>
                                    <input id="hw_title" type="text" name="title" class="form-control" required value="{{ old('title') }}">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="hw_type">{{ __('Type') }}</label>
                                    <select id="hw_type" name="homework_type" class="form-control" required>
                                        <option value="homework">{{ __('Homework') }}</option>
                                        <option value="assignment">{{ __('Assignment') }}</option>
                                        <option value="daily_diary">{{ __('Daily Diary') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="hw_max_marks">{{ __('Max Marks') }}</label>
                                    <input id="hw_max_marks" type="number" name="max_marks" class="form-control" value="10" min="1" max="500">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label for="hw_class">{{ __('Class') }} <span class="text-danger">*</span></label>
                                    <select id="hw_class" name="my_class_id" class="form-control" required>
                                        <option value="">{{ __('Select Class') }}</option>
                                        @foreach($classes as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label for="hw_section">{{ __('Section (Optional)') }}</label>
                                    <select id="hw_section" name="section_id" class="form-control">
                                        <option value="">{{ __('All Sections') }}</option>
                                        @foreach($sections as $s)
                                            <option value="{{ $s->id }}">{{ ($s->my_class->name ?? '') . ' - ' . $s->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label for="hw_subject">{{ __('Subject (Optional)') }}</label>
                                    <select id="hw_subject" name="subject_id" class="form-control">
                                        <option value="">{{ __('General / All Subjects') }}</option>
                                        @foreach($subjects as $sub)
                                            <option value="{{ $sub->id }}">{{ $sub->name }} ({{ $sub->my_class->name ?? '' }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="hw_assigned_bs">{{ __('Assigned Date (BS)') }}</label>
                                    <input id="hw_assigned_bs" type="text" name="assigned_date_bs" class="form-control" value="{{ $todayBs }}" placeholder="2083-08-12">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="hw_due_bs">{{ __('Due Date (BS)') }}</label>
                                    <input id="hw_due_bs" type="text" name="due_date_bs" class="form-control" placeholder="2083-08-15">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="hw_due_ad">{{ __('Due Date (AD)') }}</label>
                                    <input id="hw_due_ad" type="date" name="due_date_ad" class="form-control">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="hw_attachment">{{ __('Attachment') }}</label>
                                    <input id="hw_attachment" type="file" name="attachment" class="form-control-file">
                                </div>
                                <div class="col-md-12 form-group">
                                    <label for="hw_link">{{ __('Reference Link (Optional)') }}</label>
                                    <input id="hw_link" type="url" name="reference_link" class="form-control" placeholder="https://...">
                                </div>
                                <div class="col-md-12 form-group">
                                    <label for="hw_desc">{{ __('Instructions / Daily Notes') }}</label>
                                    <textarea id="hw_desc" name="description" rows="4" class="form-control">{{ old('description') }}</textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="icon-paperplane mr-1"></i>{{ __('Publish Homework / Diary') }}</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
