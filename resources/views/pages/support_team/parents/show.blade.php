@extends('layouts.master')
@section('page_title', 'Parent Details — ' . $parent->name)
@section('content')

    {{-- Executive Header --}}
    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center">
                    <a href="{{ route('parents.index') }}" class="btn btn-light btn-icon mr-3 shadow-sm" style="border-radius: 10px;" title="{{ __('Back to Parents List') }}">
                        <i class="icon-arrow-left7"></i>
                    </a>
                    <div>
                        <h3 class="font-weight-bold mb-1 text-dark" style="font-size: 1.35rem; letter-spacing: -0.01em;">
                            {{ $parent->name }}
                        </h3>
                        <p class="text-muted mb-0 font-size-sm">
                            <span class="badge badge-light border text-muted mr-1">@<span>{{ $parent->username }}</span></span>
                            <span>{{ __('Guardian Profile & Student Relations') }}</span>
                        </p>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 mt-2 mt-md-0">
                    <span class="badge badge-light border px-3 py-2 text-dark font-size-sm shadow-sm" style="border-radius: 8px;">
                        <i class="icon-calendar22 mr-1 text-primary"></i> {{ \App\Helpers\NepaliHelper::formatDualDate(date('Y-m-d')) }}
                    </span>
                    <span class="badge badge-primary px-3 py-2 font-size-sm shadow-sm" style="border-radius: 8px;">
                        <i class="icon-graduation mr-1"></i> {{ __('Session') }}: {{ Qs::getSetting('current_session') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Activation Link Flash Notification --}}
    @if(session('activation_url'))
        <div class="alert alert-success alert-dismissible shadow-sm mb-4" style="border-left: 5px solid #28a745; border-radius: 12px;">
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            <div class="d-flex align-items-center mb-2">
                <i class="icon-checkmark-circle icon-2x text-success mr-2"></i>
                <div>
                    <h5 class="alert-heading font-weight-bold mb-0">{{ __('Fresh Activation Link Ready') }}</h5>
                    <span class="font-size-sm text-dark">{{ __('Share this secure, time-limited link with :name to set/reset their password:', ['name' => session('parent_name') ?? $parent->name]) }}</span>
                </div>
            </div>
            <div class="input-group" style="max-width: 650px;">
                <input type="text" id="activation-link-input" class="form-control font-weight-semibold bg-white" value="{{ session('activation_url') }}" readonly>
                <div class="input-group-append">
                    <button class="btn btn-dark" type="button" id="copy-activation-btn" onclick="copyActivationLink()">
                        <i class="icon-copy mr-1"></i><span id="copy-btn-text">{{ __('Copy Link') }}</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        {{-- Parent Profile Info Column --}}
        <div class="col-lg-4 mb-4">
            <div class="card sms-card border-0 shadow-sm mb-4" style="border-radius: 14px;">
                <div class="card-body text-center p-4">
                    <img src="{{ $parent->photo }}" class="rounded-circle mb-3 shadow" style="width: 88px; height: 88px; object-fit: cover; border: 3px solid #FF5700;" alt="{{ $parent->name }}">
                    <h4 class="font-weight-bold text-dark mb-1">{{ $parent->name }}</h4>
                    <span class="badge badge-light border text-muted mb-3 font-size-xs">@<span>{{ $parent->username }}</span></span>

                    <div class="mb-3">
                        @if($parent->is_active)
                            <span class="badge badge-success py-1 px-2 font-weight-semibold">{{ __('Active Account') }}</span>
                        @else
                            <span class="badge badge-danger py-1 px-2 font-weight-semibold">{{ __('Deactivated') }}</span>
                        @endif

                        @if($parent->activation_token)
                            <span class="badge badge-warning py-1 px-2 ml-1" title="{{ __('Password setup pending') }}">
                                <i class="icon-key font-size-xs mr-1"></i>{{ __('Pending Activation') }}
                            </span>
                        @endif
                    </div>

                    <div class="border-top pt-3 text-left font-size-sm">
                        <div class="mb-2">
                            <i class="icon-phone mr-2 text-primary"></i>
                            <a href="tel:{{ $parent->phone }}" class="text-dark font-weight-semibold">{{ $parent->phone ?: '—' }}</a>
                        </div>
                        <div class="mb-2">
                            <i class="icon-envelop mr-2 text-primary"></i>
                            @if($parent->email)
                                <a href="mailto:{{ $parent->email }}" class="text-dark">{{ $parent->email }}</a>
                            @else
                                <span class="text-muted font-italic">{{ __('No email set') }}</span>
                            @endif
                        </div>
                        <div class="mb-2">
                            <i class="icon-location4 mr-2 text-primary"></i>
                            <span>{{ $parent->address ?: __('No address set') }}</span>
                        </div>
                        <div>
                            <i class="icon-calendar2 mr-2 text-primary"></i>
                            <span>{{ __('Registered') }}: {{ $parent->created_at ? $parent->created_at->format('M d, Y') : '—' }}</span>
                        </div>
                    </div>

                    <div class="border-top pt-3 mt-3 d-flex flex-column gap-2">
                        <form method="POST" action="{{ route('parents.reset_access', Qs::hash($parent->id)) }}" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-outline-warning btn-block btn-sm font-weight-semibold">
                                <i class="icon-key mr-1"></i>{{ __('Generate Fresh Activation Link') }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('parents.toggle_active', Qs::hash($parent->id)) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-block btn-sm font-weight-semibold">
                                <i class="icon-switch mr-1"></i>{{ $parent->is_active ? __('Deactivate Account') : __('Activate Account') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Quick Edit Parent Details --}}
            <div class="card sms-card border-0 shadow-sm" style="border-radius: 14px;">
                <div class="card-header bg-white py-3 border-bottom" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                    <h6 class="font-weight-bold mb-0 text-dark"><i class="icon-pencil5 mr-2 text-primary"></i>{{ __('Edit Parent Details') }}</h6>
                </div>
                <div class="card-body p-3">
                    <form method="POST" action="{{ route('parents.update', Qs::hash($parent->id)) }}">
                        @csrf
                        @method('PUT')
                        <div class="form-group mb-2">
                            <label class="font-size-xs font-weight-bold text-uppercase text-muted">{{ __('Full Name') }}: <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $parent->name) }}" required>
                        </div>
                        <div class="form-group mb-2">
                            <label class="font-size-xs font-weight-bold text-uppercase text-muted">{{ __('Mobile Number') }}: <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control form-control-sm" value="{{ old('phone', $parent->phone) }}" required>
                        </div>
                        <div class="form-group mb-2">
                            <label class="font-size-xs font-weight-bold text-uppercase text-muted">{{ __('Email Address') }}:</label>
                            <input type="email" name="email" class="form-control form-control-sm" value="{{ old('email', $parent->email) }}">
                        </div>
                        <div class="form-group mb-3">
                            <label class="font-size-xs font-weight-bold text-uppercase text-muted">{{ __('Residential Address') }}:</label>
                            <input type="text" name="address" class="form-control form-control-sm" value="{{ old('address', $parent->address) }}">
                        </div>
                        <button type="submit" class="btn btn-primary btn-block btn-sm font-weight-semibold shadow-sm">
                            <i class="icon-checkmark4 mr-1"></i>{{ __('Save Changes') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Linked Students & Management Column --}}
        <div class="col-lg-8">
            <div class="card sms-card border-0 shadow-sm mb-4" style="border-radius: 14px;">
                <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between py-3 border-bottom" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                    <div>
                        <h5 class="font-weight-bold mb-0 text-dark">
                            <i class="icon-users4 mr-2" style="color: #FF5700;"></i>{{ __('Linked Students') }}
                            <span class="badge badge-primary badge-pill ml-1">{{ $parent->children->count() }}</span>
                        </h5>
                        <span class="text-muted font-size-xs">{{ __('Students who can be accessed, monitored, and paid for by this parent') }}</span>
                    </div>

                    <button type="button" class="btn btn-outline-primary btn-sm font-weight-semibold mt-2 mt-sm-0" data-toggle="collapse" data-target="#collapse-link-student">
                        <i class="icon-plus2 mr-1"></i>{{ __('Link Another Student') }}
                    </button>
                </div>

                {{-- Collapsible Link Student Form --}}
                <div class="collapse border-bottom p-3 bg-light" id="collapse-link-student">
                    <form method="POST" action="{{ route('parents.link_student', Qs::hash($parent->id)) }}">
                        @csrf
                        <div class="row align-items-end">
                            <div class="col-md-6 mb-2 mb-md-0">
                                <label class="font-size-xs font-weight-bold text-uppercase text-muted">{{ __('Select Student') }}: <span class="text-danger">*</span></label>
                                <select name="student_id" class="form-control select" required>
                                    <option value="">{{ __('Choose Student...') }}</option>
                                    @foreach($all_students as $st)
                                        <option value="{{ $st->id }}">{{ $st->user->name }} ({{ $st->my_class->name ?? '' }} - {{ $st->adm_no }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-2 mb-md-0">
                                <label class="font-size-xs font-weight-bold text-uppercase text-muted">{{ __('Relationship') }}:</label>
                                <select name="relationship" class="form-control select">
                                    <option value="Father">{{ __('Father') }}</option>
                                    <option value="Mother">{{ __('Mother') }}</option>
                                    <option value="Guardian" selected>{{ __('Guardian') }}</option>
                                    <option value="Other">{{ __('Other') }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-block btn-sm font-weight-semibold shadow-sm">
                                    <i class="icon-link mr-1"></i>{{ __('Link') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>{{ __('Student') }}</th>
                                <th>{{ __('Class & Section') }}</th>
                                <th>{{ __('ADM No') }}</th>
                                <th>{{ __('Relationship') }}</th>
                                <th class="text-center" style="width: 120px;">{{ __('Actions') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($parent->children as $ch)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="{{ $ch->user->photo ?? Qs::getDefaultUserImage() }}" class="rounded-circle mr-2 border shadow-sm" style="width: 38px; height: 38px; object-fit: cover;" alt="{{ $ch->user->name }}">
                                            <div>
                                                <a href="{{ route('students.show', Qs::hash($ch->id)) }}" class="font-weight-semibold text-dark">
                                                    {{ $ch->user->name }}
                                                </a>
                                                <span class="text-muted font-size-xs d-block">@<span>{{ $ch->user->username ?? '' }}</span></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light border">
                                            {{ ($ch->my_class->name ?? '') . ' ' . ($ch->section->name ?? '') }}
                                        </span>
                                    </td>
                                    <td><span class="badge badge-light border">{{ $ch->adm_no }}</span></td>
                                    <td>
                                        <span class="badge badge-primary">{{ $ch->parent_relationship ?: __('Guardian') }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1">
                                            <a href="{{ route('students.show', Qs::hash($ch->id)) }}" class="btn btn-light btn-sm mr-1 shadow-sm" title="{{ __('View Student Profile') }}">
                                                <i class="icon-eye"></i>
                                            </a>
                                            <form method="POST" action="{{ route('parents.unlink_student', [Qs::hash($parent->id), Qs::hash($ch->id)]) }}" onsubmit="return confirm('{{ __('Are you sure you want to unlink this student from parent :name?', ['name' => $parent->name]) }}');">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-danger btn-sm shadow-sm" title="{{ __('Unlink Student') }}">
                                                    <i class="icon-unlink"></i> {{ __('Unlink') }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="icon-user-cancel icon-3x text-muted mb-2"></i>
                                            <h6 class="font-weight-bold text-dark">{{ __('No Students Currently Linked') }}</h6>
                                            <p class="font-size-sm mb-3 text-muted">{{ __('Use the button above to link an existing enrolled student to this parent account.') }}</p>
                                            <button type="button" class="btn btn-outline-primary btn-sm" data-toggle="collapse" data-target="#collapse-link-student">
                                                <i class="icon-plus2 mr-1"></i>{{ __('Link Student Now') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="text-left">
                <a href="{{ route('parents.index') }}" class="btn btn-light btn-sm font-weight-semibold shadow-sm">
                    <i class="icon-arrow-left7 mr-1"></i>{{ __('Back to Parents List') }}
                </a>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    function copyActivationLink() {
        var copyText = document.getElementById("activation-link-input");
        if (!copyText) return;
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value).then(function() {
            var btnText = document.getElementById("copy-btn-text");
            if (btnText) {
                var original = btnText.innerText;
                btnText.innerText = "{{ __('Copied!') }}";
                setTimeout(function() {
                    btnText.innerText = original;
                }, 2500);
            }
        });
    }
</script>
@endsection
