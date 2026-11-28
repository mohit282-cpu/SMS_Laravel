@extends('layouts.master')
@section('page_title', __('Nepal IEMIS, Curriculum & SEE Hub'))
@section('content')

    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4 d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h3 class="font-weight-bold mb-1 text-dark">{{ __('Nepal EMIS / IEMIS, CDC Curriculum & SEE Management') }}</h3>
                <p class="text-muted mb-0 font-size-sm">{{ __('School EMIS profile, CDC curriculum mapping, SEE candidates, and institutional export summary') }}</p>
            </div>
            <a href="{{ route('nepal_emis.export_json', ['session' => $session]) }}" class="btn btn-primary btn-sm"><i class="icon-download mr-1"></i>{{ __('Export IEMIS JSON') }}</a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('Active Students') }}</span><h3 class="font-weight-bold mb-0">{{ $iemisSummary['enrollment']['total_students'] ?? 0 }}</h3></div></div></div>
        <div class="col-md-3"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('Scholarships') }}</span><h3 class="font-weight-bold text-success mb-0">{{ $iemisSummary['enrollment']['scholarship_recipients'] ?? 0 }}</h3></div></div></div>
        <div class="col-md-3"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('Teachers') }}</span><h3 class="font-weight-bold text-primary mb-0">{{ $iemisSummary['staffing']['total_teachers'] ?? 0 }}</h3></div></div></div>
        <div class="col-md-3"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('SEE Candidates') }}</span><h3 class="font-weight-bold text-info mb-0">{{ $seeCandidates->count() }}</h3></div></div></div>
    </div>

    <div class="card sms-card">
        <div class="card-body p-3 p-md-4">
            <ul class="nav nav-tabs nav-tabs-highlight mb-4">
                <li class="nav-item"><a href="#emis-profile" class="nav-link active" data-toggle="tab">{{ __('School EMIS Profile') }}</a></li>
                <li class="nav-item"><a href="#emis-curriculum" class="nav-link" data-toggle="tab">{{ __('CDC Curricula & Grading') }}</a></li>
                <li class="nav-item"><a href="#emis-see" class="nav-link" data-toggle="tab">{{ __('SEE Candidates') }}</a></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="emis-profile">
                    <form method="POST" action="{{ route('nepal_emis.profile.update') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-4 form-group"><label>{{ __('School Name (EN)') }} *</label><input type="text" name="name" value="{{ old('name', $profile->name) }}" class="form-control" required></div>
                            <div class="col-md-4 form-group"><label>{{ __('School Name (Nepali)') }}</label><input type="text" name="name_ne" value="{{ old('name_ne', $profile->name_ne) }}" class="form-control"></div>
                            <div class="col-md-4 form-group"><label>{{ __('EMIS / School Code') }}</label><input type="text" name="emis_code" value="{{ old('emis_code', $profile->emis_code) }}" class="form-control"></div>
                            <div class="col-md-3 form-group">
                                <label>{{ __('School Type') }}</label>
                                <select name="school_type" class="form-control">
                                    @foreach(['institutional', 'community', 'montessori', 'religious', 'technical', 'other'] as $t)
                                        <option value="{{ $t }}" {{ $profile->school_type === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 form-group">
                                <label>{{ __('Boarding Type') }}</label>
                                <select name="boarding_type" class="form-control">
                                    @foreach(['day', 'boarding', 'both'] as $b)<option value="{{ $b }}" {{ $profile->boarding_type === $b ? 'selected' : '' }}>{{ ucfirst($b) }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-3 form-group"><label>{{ __('PAN Number') }}</label><input type="text" name="pan_number" value="{{ old('pan_number', $profile->pan_number) }}" class="form-control"></div>
                            <div class="col-md-3 form-group">
                                <label>{{ __('Calendar Preference') }}</label>
                                <select name="calendar_preference" class="form-control">
                                    <option value="both" {{ $profile->calendar_preference === 'both' ? 'selected' : '' }}>Both (BS & AD)</option>
                                    <option value="bs" {{ $profile->calendar_preference === 'bs' ? 'selected' : '' }}>BS Only</option>
                                    <option value="ad" {{ $profile->calendar_preference === 'ad' ? 'selected' : '' }}>AD Only</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('Save School EMIS Profile') }}</button>
                    </form>
                </div>

                <div class="tab-pane fade" id="emis-curriculum">
                    <form method="POST" action="{{ route('nepal_emis.curriculum.store') }}" class="mb-4 p-3 bg-light rounded border">
                        @csrf
                        <div class="row">
                            <div class="col-md-4 form-group"><label>{{ __('Curriculum Name') }} *</label><input type="text" name="name" class="form-control" required placeholder="CDC Secondary 2083"></div>
                            <div class="col-md-2 form-group"><label>{{ __('Version') }} *</label><input type="text" name="version" class="form-control" value="2083.1" required></div>
                            <div class="col-md-3 form-group"><label>{{ __('Subject Name') }} *</label><input type="text" name="subject_name" class="form-control" required></div>
                            <div class="col-md-3 form-group"><label>{{ __('Subject Code') }}</label><input type="text" name="subject_code" class="form-control"></div>
                            <div class="col-md-3 form-group">
                                <label>{{ __('Subject Type') }}</label>
                                <select name="subject_type" class="form-control"><option value="compulsory">Compulsory</option><option value="optional">Optional</option><option value="practical">Practical</option></select>
                            </div>
                            <div class="col-md-2 form-group"><label>{{ __('Theory Max') }}</label><input type="number" name="theory_max" value="75" class="form-control"></div>
                            <div class="col-md-2 form-group"><label>{{ __('Theory Pass') }}</label><input type="number" name="theory_pass" value="27" class="form-control"></div>
                            <div class="col-md-2 form-group"><label>{{ __('Practical Max') }}</label><input type="number" name="practical_max" value="25" class="form-control"></div>
                            <div class="col-md-2 form-group"><label>{{ __('Practical Pass') }}</label><input type="number" name="practical_pass" value="10" class="form-control"></div>
                            <div class="col-md-1 form-group"><label>{{ __('Cr.') }}</label><input type="number" step="0.5" name="credit_hour" value="4" class="form-control"></div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('Add Curriculum Subject') }}</button>
                    </form>

                    @foreach($curricula as $curr)
                        <div class="mb-3 p-3 border rounded">
                            <strong>{{ $curr->name }}</strong> <span class="badge badge-info">v{{ $curr->version }}</span>
                            <ul class="mb-0 mt-2">
                                @foreach($curr->subjects as $cs)
                                    <li>{{ $cs->subject_code }} {{ $cs->subject_name }} ({{ ucfirst($cs->subject_type) }}, TH {{ $cs->theory_max }} / PR {{ $cs->practical_max }}, {{ $cs->credit_hour }} Cr)</li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>

                <div class="tab-pane fade" id="emis-see">
                    <form method="POST" action="{{ route('nepal_emis.see.store') }}" class="mb-4 p-3 bg-light rounded border">
                        @csrf
                        <input type="hidden" name="academic_year" value="{{ $session }}">
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label>{{ __('Student') }} *</label>
                                <select name="student_record_id" class="form-control" required>
                                    @foreach($students as $st)<option value="{{ $st->id }}">{{ $st->adm_no }} - {{ $st->user->name ?? '' }} ({{ $st->my_class->name ?? '' }})</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-3 form-group"><label>{{ __('Symbol Number') }}</label><input type="text" name="symbol_number" class="form-control"></div>
                            <div class="col-md-3 form-group"><label>{{ __('GPA') }}</label><input type="number" step="0.01" min="0" max="4" name="gpa" class="form-control"></div>
                            <div class="col-md-2 form-group">
                                <label>{{ __('Status') }}</label>
                                <select name="result_status" class="form-control">
                                    @foreach(['registered','eligible','appeared','passed','ng','absent','withheld'] as $rs)<option value="{{ $rs }}">{{ strtoupper($rs) }}</option>@endforeach
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('Save SEE Candidate') }}</button>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead><tr><th>{{ __('Student') }}</th><th>{{ __('Symbol No.') }}</th><th>{{ __('GPA') }}</th><th>{{ __('Status') }}</th></tr></thead>
                            <tbody>
                                @forelse($seeCandidates as $see)
                                    <tr>
                                        <td>{{ $see->studentRecord->user->name ?? '-' }}</td>
                                        <td>{{ $see->symbol_number ?: '-' }}</td>
                                        <td>{{ $see->gpa ?? '-' }}</td>
                                        <td><span class="badge badge-primary text-uppercase">{{ $see->result_status }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">{{ __('No SEE candidates registered for this session.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
