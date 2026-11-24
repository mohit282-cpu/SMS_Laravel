@extends('layouts.master')
@section('page_title', 'Parent Account Management')
@section('content')

    {{-- Executive Header --}}
    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center">
                    <div class="d-inline-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px; border-radius: 14px; background: rgba(255, 87, 0, 0.12);">
                        <i class="icon-users4 icon-2x" style="color: #FF5700;"></i>
                    </div>
                    <div>
                        <h3 class="font-weight-bold mb-1 text-dark" style="font-size: 1.35rem; letter-spacing: -0.01em;">
                            {{ __('Parent Account Management') }}
                        </h3>
                        <p class="text-muted mb-0 font-size-sm">
                            {{ __('Guardian directory, student linkage, account activation & SMS credentials') }}
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
                    <button type="button" class="btn btn-primary btn-sm font-weight-semibold shadow-sm ml-md-2" data-toggle="modal" data-target="#modal-add-parent" style="border-radius: 8px;">
                        <i class="icon-user-plus mr-1"></i>{{ __('Create Parent Account') }}
                    </button>
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
                    <h5 class="alert-heading font-weight-bold mb-0">{{ __('Parent Account Activation Link Ready') }}</h5>
                    <span class="font-size-sm text-dark">{{ __('Share this secure, time-limited activation link with parent :name to set their password:', ['name' => session('parent_name') ?? '']) }}</span>
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

    {{-- 4 Metric Stat Cards --}}
    <div class="row mb-4">
        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #4f46e5 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Total Parents') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($total_parents ?? $parents->total()) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Registered guardians') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(79, 70, 229, 0.1);">
                            <i class="icon-users4 icon-2x" style="color: #4f46e5;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #10b981 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Active Accounts') }}</span>
                            <h3 class="font-weight-bold text-success mb-0 mt-1">{{ number_format($active_parents ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Portal access enabled') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16, 185, 129, 0.1);">
                            <i class="icon-checkmark-circle2 icon-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #f59e0b !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Pending Activation') }}</span>
                            <h3 class="font-weight-bold text-warning mb-0 mt-1">{{ number_format($pending_activation ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Awaiting password setup') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1);">
                            <i class="icon-key icon-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #ef4444 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Unlinked Students') }}</span>
                            <h3 class="font-weight-bold text-danger mb-0 mt-1">{{ number_format($total_unlinked ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Missing guardian record') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(239, 68, 68, 0.1);">
                            <i class="icon-user-cancel icon-2x text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Tabbed Container --}}
    <div class="card sms-card border-0 shadow-sm" style="border-radius: 16px;">
        <div class="card-header bg-white border-bottom p-0" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
            <ul class="nav nav-tabs nav-tabs-bottom mb-0 border-0 px-3 pt-2">
                <li class="nav-item">
                    <a href="#tab-parents-directory" class="nav-link active font-weight-semibold" data-toggle="tab">
                        <i class="icon-address-book mr-1"></i> {{ __('Parents Directory') }}
                        <span class="badge badge-primary badge-pill ml-1">{{ $parents->total() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#tab-unlinked-students" class="nav-link font-weight-semibold" data-toggle="tab">
                        <i class="icon-user-cancel mr-1"></i> {{ __('Unlinked Students Queue') }}
                        @if(($total_unlinked ?? 0) > 0)
                            <span class="badge badge-danger badge-pill ml-1">{{ $total_unlinked }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#tab-create-parent" class="nav-link font-weight-semibold" data-toggle="tab">
                        <i class="icon-user-plus mr-1"></i> {{ __('Quick Create Parent') }}
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body p-3 p-md-4">
            <div class="tab-content">
                {{-- TAB 1: Parents Directory --}}
                <div class="tab-pane fade show active" id="tab-parents-directory">
                    {{-- Search Filter Form --}}
                    <form method="GET" action="{{ route('parents.index') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-7 col-lg-6">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="{{ __('Search by parent name, mobile number, email, or username...') }}" value="{{ request('search') }}">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="submit"><i class="icon-search4 mr-1"></i>{{ __('Search') }}</button>
                                        @if(request('search'))
                                            <a href="{{ route('parents.index') }}" class="btn btn-light" title="{{ __('Clear Search') }}"><i class="icon-cross2"></i></a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @if(request('search'))
                                <div class="col-md-5 d-flex align-items-center mt-2 mt-md-0">
                                    <span class="text-muted font-size-sm">
                                        {{ __('Showing search results for:') }} <strong>"{{ request('search') }}"</strong> ({{ $parents->total() }} found)
                                    </span>
                                </div>
                            @endif
                        </div>
                    </form>

                    {{-- Parents Table --}}
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="thead-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>{{ __('Parent Details') }}</th>
                                <th>{{ __('Contact Information') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Linked Student(s)') }}</th>
                                <th class="text-center" style="width: 100px;">{{ __('Actions') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($parents as $p)
                                <tr>
                                    <td>{{ $loop->iteration + ($parents->currentPage() - 1) * $parents->perPage() }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="{{ $p->photo }}" class="rounded-circle mr-2 border shadow-sm" style="width: 42px; height: 42px; object-fit: cover;" alt="{{ $p->name }}">
                                            <div>
                                                <a href="{{ route('parents.show', Qs::hash($p->id)) }}" class="font-weight-bold text-dark d-block">
                                                    {{ $p->name }}
                                                </a>
                                                <span class="text-muted font-size-xs">@<span>{{ $p->username }}</span></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <a href="tel:{{ $p->phone }}" class="text-dark font-weight-semibold">
                                                <i class="icon-phone mr-1 text-primary"></i>{{ $p->phone ?: '—' }}
                                            </a>
                                        </div>
                                        @if($p->email)
                                            <div class="font-size-xs text-muted">
                                                <a href="mailto:{{ $p->email }}" class="text-muted">
                                                    <i class="icon-envelop mr-1"></i>{{ $p->email }}
                                                </a>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($p->is_active)
                                            <span class="badge badge-success py-1 px-2 font-weight-semibold">{{ __('Active') }}</span>
                                        @else
                                            <span class="badge badge-danger py-1 px-2 font-weight-semibold">{{ __('Deactivated') }}</span>
                                        @endif

                                        @if($p->activation_token)
                                            <span class="badge badge-warning py-1 px-2 ml-1" title="{{ __('Password setup pending') }}">
                                                <i class="icon-key font-size-xs mr-1"></i>{{ __('Pending Activation') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($p->children->isNotEmpty())
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($p->children as $ch)
                                                    <span class="badge badge-light border py-1 px-2 mr-1 mb-1 shadow-sm" style="font-size: 0.82rem; border-radius: 6px;">
                                                        <i class="icon-user text-primary mr-1"></i>
                                                        <a href="{{ route('students.show', Qs::hash($ch->id)) }}" class="text-dark font-weight-semibold">
                                                            {{ $ch->user->name }}
                                                        </a>
                                                        <span class="text-muted font-size-xs">({{ $ch->my_class->name ?? '' }} {{ $ch->section->name ?? '' }})</span>
                                                        @if($ch->parent_relationship)
                                                            <span class="badge badge-primary py-0 px-1 ml-1" style="font-size: 0.72rem;">{{ $ch->parent_relationship }}</span>
                                                        @endif
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="badge badge-light text-muted border py-1 px-2 font-italic font-size-xs">
                                                <i class="icon-warning22 text-warning mr-1"></i>{{ __('No students linked') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="list-icons">
                                            <div class="dropdown">
                                                <a href="#" class="list-icons-item" data-toggle="dropdown" aria-expanded="false">
                                                    <i class="icon-menu9"></i>
                                                </a>

                                                <div class="dropdown-menu dropdown-menu-right shadow-sm">
                                                    <a href="{{ route('parents.show', Qs::hash($p->id)) }}" class="dropdown-item">
                                                        <i class="icon-eye text-primary"></i> {{ __('View & Manage Linked Students') }}
                                                    </a>

                                                    <form method="POST" action="{{ route('parents.reset_access', Qs::hash($p->id)) }}">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="icon-key text-warning"></i> {{ __('Generate New Activation Link') }}
                                                        </button>
                                                    </form>

                                                    <form method="POST" action="{{ route('parents.toggle_active', Qs::hash($p->id)) }}">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="icon-switch text-secondary"></i> {{ $p->is_active ? __('Deactivate Account') : __('Activate Account') }}
                                                        </button>
                                                    </form>

                                                    @if(Qs::userIsSuperAdmin() && $p->children->isEmpty())
                                                        <div class="dropdown-divider"></div>
                                                        <form method="POST" action="{{ route('parents.destroy', Qs::hash($p->id)) }}" onsubmit="return confirm('{{ __('Are you sure you want to permanently delete this parent account?') }}');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="icon-trash"></i> {{ __('Delete Account') }}
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="icon-users4 icon-3x text-muted mb-2"></i>
                                            <h6 class="font-weight-bold text-dark">{{ __('No Parent Accounts Found') }}</h6>
                                            <p class="font-size-sm mb-3">{{ __('No parents found matching the given search criteria.') }}</p>
                                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modal-add-parent">
                                                <i class="icon-user-plus mr-1"></i>{{ __('Create First Parent') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 d-flex justify-content-between align-items-center">
                        <span class="text-muted font-size-sm">
                            {{ __('Showing :from to :to of :total parent accounts', [
                                'from' => $parents->firstItem() ?: 0,
                                'to' => $parents->lastItem() ?: 0,
                                'total' => $parents->total()
                            ]) }}
                        </span>
                        {{ $parents->links() }}
                    </div>
                </div>

                {{-- TAB 2: Unlinked Students Queue --}}
                <div class="tab-pane fade" id="tab-unlinked-students">
                    <div class="alert alert-warning border-0 shadow-sm mb-4" style="border-radius: 12px;">
                        <div class="d-flex align-items-center">
                            <i class="icon-info22 icon-2x text-warning mr-3"></i>
                            <div>
                                <h6 class="font-weight-bold mb-1 text-dark">{{ __('Unlinked Enrolled Students Queue') }}</h6>
                                <p class="mb-0 font-size-sm text-dark">
                                    {{ __('These enrolled students do not currently have an assigned parent or guardian account. Link them to an existing parent or create a fresh parent account.') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('Student Details') }}</th>
                                <th>{{ __('Class & Section') }}</th>
                                <th>{{ __('Admission No') }}</th>
                                <th>{{ __('Gender & Session') }}</th>
                                <th class="text-center">{{ __('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($unlinked_students as $st)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="{{ $st->user->photo ?? Qs::getDefaultUserImage() }}" class="rounded-circle mr-2 border shadow-sm" style="width: 40px; height: 40px; object-fit: cover;" alt="{{ $st->user->name }}">
                                            <div>
                                                <a href="{{ route('students.show', Qs::hash($st->id)) }}" class="font-weight-bold text-dark d-block">
                                                    {{ $st->user->name }}
                                                </a>
                                                <span class="text-muted font-size-xs">@<span>{{ $st->user->username ?? '' }}</span></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light border font-weight-semibold">
                                            {{ $st->my_class->name ?? '—' }} {{ $st->section->name ?? '' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-light border">{{ $st->adm_no }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary">{{ $st->user->gender ?? '—' }}</span>
                                        <span class="text-muted font-size-xs ml-1">{{ $st->session }}</span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-primary btn-sm font-weight-semibold" data-toggle="modal" data-target="#modal-add-parent" onclick="preselectStudent('{{ $st->id }}')">
                                            <i class="icon-user-plus mr-1"></i>{{ __('Create Parent') }}
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="icon-checkmark-circle2 icon-3x text-success mb-2"></i>
                                        <h6 class="font-weight-bold text-dark">{{ __('All Students Linked!') }}</h6>
                                        <p class="font-size-sm mb-0">{{ __('Great job! Every active enrolled student has an assigned guardian account.') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB 3: Quick Create Parent Account --}}
                <div class="tab-pane fade" id="tab-create-parent">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="card border shadow-none" style="border-radius: 12px;">
                                <div class="card-header bg-light py-3">
                                    <h5 class="font-weight-bold mb-0 text-dark">
                                        <i class="icon-user-plus mr-2" style="color: #FF5700;"></i>{{ __('Register New Parent / Guardian') }}
                                    </h5>
                                    <span class="text-muted font-size-xs">{{ __('Creates user record with role "parent", auto-normalizes phone number, and issues activation token.') }}</span>
                                </div>
                                <div class="card-body p-4">
                                    <form method="POST" action="{{ route('parents.store') }}">
                                        @csrf
                                        <div class="row">
                                            <div class="col-md-6 form-group">
                                                <label class="font-weight-semibold">{{ __('Parent Full Name') }}: <span class="text-danger">*</span></label>
                                                <input type="text" name="name" class="form-control" required placeholder="{{ __('e.g. Maya Devi Adhikari') }}" value="{{ old('name') }}">
                                            </div>

                                            <div class="col-md-6 form-group">
                                                <label class="font-weight-semibold">{{ __('Mobile Number (Login & SMS)') }}: <span class="text-danger">*</span></label>
                                                <input type="text" name="phone" class="form-control" required placeholder="{{ __('e.g. 9841234567') }}" value="{{ old('phone') }}">
                                                <span class="form-text text-muted font-size-xs">{{ __('10-digit mobile number used for login and instant SMS notifications.') }}</span>
                                            </div>

                                            <div class="col-md-6 form-group">
                                                <label class="font-weight-semibold">{{ __('Email Address') }}: </label>
                                                <input type="email" name="email" class="form-control" placeholder="{{ __('e.g. parent@example.com') }}" value="{{ old('email') }}">
                                            </div>

                                            <div class="col-md-6 form-group">
                                                <label class="font-weight-semibold">{{ __('Residential Address') }}: </label>
                                                <input type="text" name="address" class="form-control" placeholder="{{ __('e.g. Kathmandu Ward 4, Nepal') }}" value="{{ old('address') }}">
                                            </div>

                                            <div class="col-12"><hr class="my-3"></div>

                                            <div class="col-md-8 form-group">
                                                <label class="font-weight-semibold">{{ __('Link Enrolled Student') }}:</label>
                                                <select name="student_id" id="inline_student_id" class="form-control select">
                                                    <option value="">{{ __('None (Create Parent Account Only)') }}</option>
                                                    @foreach($unlinked_students as $st)
                                                        <option value="{{ $st->id }}">{{ $st->user->name }} ({{ $st->my_class->name ?? '' }} - {{ $st->adm_no }})</option>
                                                    @endforeach
                                                </select>
                                                <span class="form-text text-muted font-size-xs">{{ __('Optionally link a student from the unlinked queue immediately.') }}</span>
                                            </div>

                                            <div class="col-md-4 form-group">
                                                <label class="font-weight-semibold">{{ __('Relationship') }}:</label>
                                                <select name="relationship" class="form-control select">
                                                    <option value="Father">{{ __('Father') }}</option>
                                                    <option value="Mother">{{ __('Mother') }}</option>
                                                    <option value="Guardian" selected>{{ __('Guardian') }}</option>
                                                    <option value="Other">{{ __('Other') }}</option>
                                                </select>
                                            </div>

                                            <div class="col-12 mt-3">
                                                <button type="submit" class="btn btn-primary btn-block font-weight-semibold py-2">
                                                    <i class="icon-checkmark4 mr-1"></i>{{ __('Create Parent & Generate Secure Activation Link') }}
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Create Parent Modal --}}
    <div id="modal-add-parent" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content shadow" style="border-radius: 14px;">
                <div class="modal-header bg-white py-3" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                    <h5 class="modal-title font-weight-bold text-dark"><i class="icon-user-plus mr-2" style="color: #FF5700;"></i>{{ __('Create Parent Account') }}</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <form method="POST" action="{{ route('parents.store') }}">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Parent Full Name') }}: <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="{{ __('e.g. Maya Devi Adhikari') }}" value="{{ old('name') }}">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Mobile Number') }}: <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" required placeholder="{{ __('e.g. 9841234567') }}" value="{{ old('phone') }}">
                            <span class="form-text text-muted font-size-xs">{{ __('Phone number is normalized and used for parent login and SMS delivery.') }}</span>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Email Address') }}: </label>
                            <input type="email" name="email" class="form-control" placeholder="{{ __('e.g. parent@example.com') }}" value="{{ old('email') }}">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Address') }}: </label>
                            <input type="text" name="address" class="form-control" placeholder="{{ __('e.g. Kathmandu, Nepal') }}" value="{{ old('address') }}">
                        </div>

                        @if(!empty($unlinked_students) && $unlinked_students->isNotEmpty())
                            <div class="border-top pt-3 mt-3">
                                <h6 class="font-weight-bold text-dark mb-2">{{ __('Optionally Link Student Immediately') }}</h6>
                                <div class="form-group">
                                    <label class="font-size-sm">{{ __('Select Student') }}:</label>
                                    <select name="student_id" id="modal_student_id" class="form-control select">
                                        <option value="">{{ __('None (Link Later)') }}</option>
                                        @foreach($unlinked_students as $st)
                                            <option value="{{ $st->id }}">{{ $st->user->name }} ({{ $st->my_class->name ?? '' }} - {{ $st->adm_no }})</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="font-size-sm">{{ __('Relationship') }}:</label>
                                    <select name="relationship" class="form-control select">
                                        <option value="Father">{{ __('Father') }}</option>
                                        <option value="Mother">{{ __('Mother') }}</option>
                                        <option value="Guardian" selected>{{ __('Guardian') }}</option>
                                        <option value="Other">{{ __('Other') }}</option>
                                    </select>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer bg-light" style="border-bottom-left-radius: 14px; border-bottom-right-radius: 14px;">
                        <button type="button" class="btn btn-link text-muted font-weight-semibold" data-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary font-weight-semibold shadow-sm"><i class="icon-checkmark4 mr-1"></i>{{ __('Create & Generate Activation Link') }}</button>
                    </div>
                </form>
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

    function preselectStudent(studentId) {
        var select = document.getElementById('modal_student_id');
        if (select) {
            select.value = studentId;
        }
        var inlineSelect = document.getElementById('inline_student_id');
        if (inlineSelect) {
            inlineSelect.value = studentId;
        }
    }
</script>
@endsection
