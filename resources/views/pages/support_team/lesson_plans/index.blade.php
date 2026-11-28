@extends('layouts.master')
@section('page_title', __('Lesson Plans & Teacher Daily Report'))
@section('content')

    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="font-weight-bold mb-1 text-dark">{{ __('Lesson Plans & Daily Teaching Reports') }}</h3>
                <p class="text-muted mb-0 font-size-sm">{{ __('Syllabus progress tracking, unit outlines, and teacher daily class execution logs') }}</p>
            </div>
            <span class="badge badge-light border px-3 py-2">{{ \App\Helpers\NepaliHelper::formatDualDate(date('Y-m-d')) }}</span>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('Total Plans') }}</span><h3 class="font-weight-bold mb-0">{{ $totalPlans }}</h3></div></div></div>
        <div class="col-md-3"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('Completed') }}</span><h3 class="font-weight-bold text-success mb-0">{{ $completedCount }}</h3></div></div></div>
        <div class="col-md-3"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('In Progress') }}</span><h3 class="font-weight-bold text-info mb-0">{{ $inProgressCount }}</h3></div></div></div>
        <div class="col-md-3"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('Planned') }}</span><h3 class="font-weight-bold text-warning mb-0">{{ $plannedCount }}</h3></div></div></div>
    </div>

    <div class="card sms-card">
        <div class="card-body p-3 p-md-4">
            <ul class="nav nav-tabs nav-tabs-highlight mb-4">
                <li class="nav-item"><a href="#lp-list" class="nav-link active" data-toggle="tab">{{ __('Lesson Plans') }}</a></li>
                <li class="nav-item"><a href="#lp-new" class="nav-link" data-toggle="tab">{{ __('Create Lesson Plan / Daily Log') }}</a></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="lp-list">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('Unit / Topic') }}</th>
                                    <th>{{ __('Class / Subject') }}</th>
                                    <th>{{ __('Teacher') }}</th>
                                    <th>{{ __('Planned (BS)') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Update') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lessonPlans as $lp)
                                    <tr>
                                        <td>
                                            <strong>{{ $lp->unit_title }}</strong>: {{ $lp->lesson_topic }}
                                            @if($lp->daily_report_notes)<div class="text-muted font-size-xs">{{ $lp->daily_report_notes }}</div>@endif
                                        </td>
                                        <td>{{ $lp->my_class->name ?? '-' }} / {{ $lp->subject->name ?? '-' }}</td>
                                        <td>{{ $lp->teacher->name ?? '-' }}</td>
                                        <td>{{ $lp->planned_date_bs ?: \App\Helpers\NepaliHelper::formatDualDate($lp->planned_date_ad) }}</td>
                                        <td><span class="badge badge-{{ $lp->completion_status === 'completed' ? 'success' : ($lp->completion_status === 'in_progress' ? 'info' : 'secondary') }} text-capitalize">{{ str_replace('_', ' ', $lp->completion_status) }}</span></td>
                                        <td>
                                            <form method="POST" action="{{ route('lesson_plans.status', $lp->id) }}" class="form-inline" style="gap: 0.35rem;">
                                                @csrf
                                                <select name="completion_status" class="form-control form-control-sm">
                                                    <option value="planned" {{ $lp->completion_status === 'planned' ? 'selected' : '' }}>{{ __('Planned') }}</option>
                                                    <option value="in_progress" {{ $lp->completion_status === 'in_progress' ? 'selected' : '' }}>{{ __('In Progress') }}</option>
                                                    <option value="completed" {{ $lp->completion_status === 'completed' ? 'selected' : '' }}>{{ __('Completed') }}</option>
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-primary">{{ __('Update') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('No lesson plans recorded yet.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $lessonPlans->links() }}</div>
                </div>

                <div class="tab-pane fade" id="lp-new">
                    <form method="POST" action="{{ route('lesson_plans.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label for="lp_class">{{ __('Class') }} *</label>
                                <select id="lp_class" name="my_class_id" class="form-control" required>
                                    @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="lp_subject">{{ __('Subject') }}</label>
                                <select id="lp_subject" name="subject_id" class="form-control">
                                    <option value="">{{ __('General') }}</option>
                                    @foreach($subjects as $sub)<option value="{{ $sub->id }}">{{ $sub->name }} ({{ $sub->my_class->name ?? '' }})</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="lp_date_bs">{{ __('Planned Date (BS)') }}</label>
                                <input id="lp_date_bs" type="text" name="planned_date_bs" class="form-control" value="{{ \App\Helpers\NepaliHelper::adToBs(date('Y-m-d')) }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="lp_unit">{{ __('Unit / Chapter') }} *</label>
                                <input id="lp_unit" type="text" name="unit_title" class="form-control" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="lp_topic">{{ __('Lesson Topic') }} *</label>
                                <input id="lp_topic" type="text" name="lesson_topic" class="form-control" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="lp_obj">{{ __('Learning Objectives') }}</label>
                                <textarea id="lp_obj" name="learning_objectives" rows="3" class="form-control"></textarea>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="lp_notes">{{ __('Daily Report Notes / Teaching Aids') }}</label>
                                <textarea id="lp_notes" name="daily_report_notes" rows="3" class="form-control"></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('Save Lesson Plan') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
