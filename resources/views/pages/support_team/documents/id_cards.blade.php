@extends('layouts.master')
@section('page_title', __('Smart ID Cards Generator'))
@section('content')

    <div class="card sms-card mb-4 no-print">
        <div class="card-body p-3 p-md-4 d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h3 class="font-weight-bold mb-1 text-dark">{{ __('Batch Smart ID Card Generator') }}</h3>
                <p class="text-muted mb-0 font-size-sm">{{ __('Generate printable section-wise student & staff identity cards with gate verification codes') }}</p>
            </div>
            <button type="button" onclick="window.print()" class="btn btn-primary btn-sm"><i class="icon-printer mr-1"></i>{{ __('Print Cards') }}</button>
        </div>
        <div class="card-footer bg-transparent border-top pt-3">
            <form method="GET" action="{{ route('documents.id_cards') }}" class="form-inline" style="gap: 0.75rem;">
                <select name="mode" class="form-control form-control-sm">
                    <option value="student" {{ $cardMode === 'student' ? 'selected' : '' }}>{{ __('Students') }}</option>
                    <option value="staff" {{ $cardMode === 'staff' ? 'selected' : '' }}>{{ __('Teachers & Staff') }}</option>
                </select>
                <select name="my_class_id" class="form-control form-control-sm">
                    <option value="">{{ __('All Classes') }}</option>
                    @foreach($classes as $c)<option value="{{ $c->id }}" {{ (string) request('my_class_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-primary">{{ __('Generate Cards') }}</button>
            </form>
        </div>
    </div>

    <div class="row">
        @if($cardMode === 'staff')
            @foreach($staffCards as $st)
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="card sms-card border shadow-sm">
                        <div class="card-header bg-dark text-white py-2 text-center font-weight-bold">{{ $school->name ?? Qs::getSystemName() }} — STAFF ID</div>
                        <div class="card-body text-center p-3">
                            <img src="{{ $st->photo }}" width="64" height="64" class="rounded-circle mb-2" style="object-fit: cover;" alt="{{ $st->name }}">
                            <h5 class="font-weight-bold mb-1">{{ $st->name }}</h5>
                            <span class="badge badge-primary mb-2 text-uppercase">{{ str_replace('_', ' ', $st->user_type) }}</span>
                            <div class="font-size-xs text-muted">ID Code: <strong>{{ $st->code }}</strong></div>
                            <div class="font-size-xs text-muted">Phone: {{ $st->phone ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            @forelse($studentCards as $sr)
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="card sms-card border shadow-sm">
                        <div class="card-header text-white py-2 text-center font-weight-bold" style="background: #0F172A;">{{ $school->name ?? Qs::getSystemName() }} — STUDENT ID</div>
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center mb-2">
                                <img src="{{ $sr->user->photo ?? Qs::getDefaultUserImage() }}" width="56" height="56" class="rounded-circle mr-3" style="object-fit: cover;" alt="{{ $sr->user->name ?? 'Student' }}">
                                <div>
                                    <h6 class="font-weight-bold mb-0">{{ $sr->user->name ?? '-' }}</h6>
                                    <div class="font-size-xs text-primary font-weight-semibold">{{ $sr->my_class->name ?? '' }} {{ $sr->section ? '(' . $sr->section->name . ')' : '' }}</div>
                                </div>
                            </div>
                            <div class="font-size-xs"><strong>ADM / ID:</strong> {{ $sr->adm_no }}</div>
                            <div class="font-size-xs"><strong>DOB (BS):</strong> {{ $sr->dob_bs ?: ($sr->user->dob ?? '-') }}</div>
                            <div class="font-size-xs"><strong>Blood Group:</strong> {{ $sr->user->blood_group->name ?? '-' }}</div>
                            <div class="font-size-xs"><strong>Verify Code:</strong> <code>{{ $sr->user->code ?? $sr->adm_no }}</code></div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12"><div class="card sms-card"><div class="card-body text-center text-muted py-4">{{ __('No student records matched this filter.') }}</div></div></div>
            @endforelse
        @endif
    </div>
@endsection
