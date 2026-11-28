@extends('layouts.master')
@section('page_title', __('Exam Admit Cards Generator'))
@section('content')

    <div class="card sms-card mb-4 no-print">
        <div class="card-body p-3 p-md-4 d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h3 class="font-weight-bold mb-1 text-dark">{{ __('Exam Entrance / Admit Cards') }}</h3>
                <p class="text-muted mb-0 font-size-sm">{{ __('Generate printable admit cards for examinations with symbol and admission numbers') }}</p>
            </div>
            <button type="button" onclick="window.print()" class="btn btn-primary btn-sm"><i class="icon-printer mr-1"></i>{{ __('Print Admit Cards') }}</button>
        </div>
        <div class="card-footer bg-transparent border-top pt-3">
            <form method="GET" action="{{ route('documents.admit_cards') }}" class="form-inline" style="gap: 0.75rem;">
                <select name="exam_id" class="form-control form-control-sm">
                    @foreach($exams as $ex)<option value="{{ $ex->id }}" {{ $selectedExam && $selectedExam->id === $ex->id ? 'selected' : '' }}>{{ $ex->name }} ({{ $ex->year }})</option>@endforeach
                </select>
                <select name="my_class_id" class="form-control form-control-sm">
                    @foreach($classes as $c)<option value="{{ $c->id }}" {{ $selectedClass && $selectedClass->id === $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-primary">{{ __('Load Admit Cards') }}</button>
            </form>
        </div>
    </div>

    <div class="row">
        @forelse($students as $sr)
            <div class="col-md-6 mb-4">
                <div class="card sms-card border shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <strong>{{ $school->name ?? Qs::getSystemName() }}</strong>
                        <span class="badge badge-primary">{{ $selectedExam->name ?? __('Examination') }}</span>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="font-weight-bold mb-1">{{ $sr->user->name ?? '-' }}</h5>
                                <div class="font-size-sm"><strong>{{ __('ADM No') }}:</strong> {{ $sr->adm_no }}</div>
                                <div class="font-size-sm"><strong>{{ __('Class / Section') }}:</strong> {{ $sr->my_class->name ?? '-' }} {{ $sr->section ? '(' . $sr->section->name . ')' : '' }}</div>
                                <div class="font-size-sm"><strong>{{ __('Session') }}:</strong> {{ $sr->session }}</div>
                            </div>
                            <img src="{{ $sr->user->photo ?? Qs::getDefaultUserImage() }}" width="64" height="64" class="rounded" style="object-fit: cover;" alt="{{ $sr->user->name ?? '' }}">
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="card sms-card"><div class="card-body text-center text-muted py-4">{{ __('Select an exam and class with enrolled students.') }}</div></div></div>
        @endforelse
    </div>
@endsection
