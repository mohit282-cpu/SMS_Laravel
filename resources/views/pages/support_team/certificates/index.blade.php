@extends('layouts.master')
@section('page_title', __('Certificates & Document Vault'))
@section('content')

    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4 d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h3 class="font-weight-bold mb-1 text-dark">{{ __('Certificates, Digital Vault & Verification') }}</h3>
                <p class="text-muted mb-0 font-size-sm">{{ __('Issue Character, Transfer & Leaving certificates, verify by code, and archive student files') }}</p>
            </div>
            @if(Qs::userIsTeamSA())
                <div class="d-flex" style="gap: 0.5rem;">
                    <a href="{{ route('documents.id_cards') }}" class="btn btn-outline-primary btn-sm"><i class="icon-vcard mr-1"></i>{{ __('ID Cards') }}</a>
                    <a href="{{ route('documents.admit_cards') }}" class="btn btn-outline-info btn-sm"><i class="icon-ticket mr-1"></i>{{ __('Admit Cards') }}</a>
                </div>
            @endif
        </div>
    </div>

    {{-- Verification Lookup Card --}}
    <div class="card sms-card mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('certificates.index') }}" class="form-inline" style="gap: 0.75rem;">
                <label for="verify_code" class="font-weight-semibold">{{ __('Verify Certificate Code / Number:') }}</label>
                <input id="verify_code" type="text" name="verify_code" value="{{ request('verify_code') }}" class="form-control form-control-sm" placeholder="Enter Verification Code">
                <button type="submit" class="btn btn-primary btn-sm">{{ __('Verify Authenticity') }}</button>
            </form>
            @if(request()->filled('verify_code'))
                <div class="mt-3">
                    @if($verifiedCertificate)
                        <div class="alert alert-success mb-0">
                            <strong>{{ __('VERIFIED CERTIFICATE') }}:</strong> {{ $verifiedCertificate->certificate_number }} ({{ ucfirst($verifiedCertificate->certificate_type) }}) — {{ $verifiedCertificate->studentRecord->user->name ?? '-' }} ({{ $verifiedCertificate->studentRecord->my_class->name ?? '-' }}) • {{ __('Issued') }}: {{ $verifiedCertificate->issued_date_bs }} BS
                        </div>
                    @else
                        <div class="alert alert-danger mb-0">{{ __('No matching certificate found for code:') }} {{ request('verify_code') }}</div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="card sms-card">
        <div class="card-body p-3 p-md-4">
            <ul class="nav nav-tabs nav-tabs-highlight mb-4">
                <li class="nav-item"><a href="#cert-list" class="nav-link active" data-toggle="tab">{{ __('Issued Certificates') }}</a></li>
                <li class="nav-item"><a href="#doc-vault" class="nav-link" data-toggle="tab">{{ __('Document Vault') }}</a></li>
                @if(Qs::userIsTeamSA())
                    <li class="nav-item"><a href="#cert-issue" class="nav-link" data-toggle="tab">{{ __('Issue Certificate') }}</a></li>
                    <li class="nav-item"><a href="#doc-upload" class="nav-link" data-toggle="tab">{{ __('Upload Student File') }}</a></li>
                @endif
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="cert-list">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead><tr><th>{{ __('Cert No.') }}</th><th>{{ __('Verify Code') }}</th><th>{{ __('Student') }}</th><th>{{ __('Type') }}</th><th>{{ __('Issued (BS)') }}</th><th>{{ __('Print') }}</th></tr></thead>
                            <tbody>
                                @forelse($certificates as $cert)
                                    <tr>
                                        <td><strong>{{ $cert->certificate_number }}</strong></td>
                                        <td><code>{{ $cert->verification_code }}</code></td>
                                        <td>{{ $cert->studentRecord->user->name ?? '-' }} <div class="text-muted font-size-xs">{{ $cert->studentRecord->my_class->name ?? '' }}</div></td>
                                        <td><span class="badge badge-info text-capitalize">{{ $cert->certificate_type }}</span></td>
                                        <td>{{ $cert->issued_date_bs }}</td>
                                        <td><a href="{{ route('certificates.print', $cert->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">{{ __('Print') }}</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('No certificates issued yet.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $certificates->links() }}
                </div>

                <div class="tab-pane fade" id="doc-vault">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead><tr><th>{{ __('Student') }}</th><th>{{ __('Type') }}</th><th>{{ __('Title') }}</th><th>{{ __('Uploaded By') }}</th><th>{{ __('Download') }}</th></tr></thead>
                            <tbody>
                                @forelse($documents as $doc)
                                    <tr>
                                        <td>{{ $doc->studentRecord->user->name ?? '-' }}</td>
                                        <td><span class="badge badge-light border">{{ $doc->document_type }}</span></td>
                                        <td>{{ $doc->title }}</td>
                                        <td>{{ $doc->uploader->name ?? '-' }}</td>
                                        <td><a href="{{ route('certificates.documents.download', $doc->id) }}" class="btn btn-sm btn-outline-primary">{{ __('Download') }}</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">{{ __('No student documents uploaded yet.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $documents->links() }}
                </div>

                @if(Qs::userIsTeamSA())
                    <div class="tab-pane fade" id="cert-issue">
                        <form method="POST" action="{{ route('certificates.store') }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-5 form-group"><label>{{ __('Student') }} *</label><select name="student_record_id" class="form-control" required>@foreach($students as $st)<option value="{{ $st->id }}">{{ $st->adm_no }} - {{ $st->user->name ?? '' }}</option>@endforeach</select></div>
                                <div class="col-md-4 form-group">
                                    <label>{{ __('Certificate Type') }} *</label>
                                    <select name="certificate_type" class="form-control" required>
                                        <option value="character">Character Certificate</option>
                                        <option value="transfer">Transfer Certificate</option>
                                        <option value="leaving">School Leaving Certificate</option>
                                        <option value="bonafide">Bonafide Certificate</option>
                                        <option value="study">Study Certificate</option>
                                    </select>
                                </div>
                                <div class="col-md-3 form-group"><label>{{ __('Issue Date (BS)') }}</label><input type="text" name="issued_date_bs" class="form-control" value="{{ \App\Helpers\NepaliHelper::adToBs(date('Y-m-d')) }}"></div>
                                <div class="col-md-12 form-group"><label>{{ __('Remarks') }}</label><textarea name="remarks" rows="2" class="form-control"></textarea></div>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Issue Certificate') }}</button>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="doc-upload">
                        <form method="POST" action="{{ route('certificates.documents.store') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-md-4 form-group"><label>{{ __('Student') }} *</label><select name="student_record_id" class="form-control" required>@foreach($students as $st)<option value="{{ $st->id }}">{{ $st->adm_no }} - {{ $st->user->name ?? '' }}</option>@endforeach</select></div>
                                <div class="col-md-4 form-group"><label>{{ __('Document Type') }} *</label><input type="text" name="document_type" class="form-control" required placeholder="Birth Certificate / Transfer Record"></div>
                                <div class="col-md-4 form-group"><label>{{ __('Document Title') }} *</label><input type="text" name="title" class="form-control" required></div>
                                <div class="col-md-6 form-group"><label>{{ __('File') }} *</label><input type="file" name="document_file" class="form-control-file" required></div>
                                <div class="col-md-6 form-group"><label>{{ __('Remarks') }}</label><input type="text" name="remarks" class="form-control"></div>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Upload to Vault') }}</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
