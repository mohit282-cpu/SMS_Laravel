@extends('layouts.master')
@section('page_title', 'Parent Details — ' . $parent->name)
@section('content')

    {{-- Activation Link Flash Notification --}}
    @if(session('activation_url'))
        <div class="alert alert-success alert-dismissible shadow-sm mb-4" style="border-left: 5px solid #28a745; border-radius: 12px;">
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            <h5 class="alert-heading font-weight-bold mb-1">
                <i class="icon-checkmark-circle mr-1"></i>{{ __('Activation Link Ready') }}
            </h5>
            <p class="mb-2">{{ __('Share this link with the parent to set their password:') }}</p>
            <div class="input-group" style="max-width: 600px;">
                <input type="text" id="activation-link-input" class="form-control font-weight-semibold" value="{{ session('activation_url') }}" readonly>
                <div class="input-group-append">
                    <button class="btn btn-dark" type="button" onclick="navigator.clipboard.writeText(document.getElementById('activation-link-input').value); alert('{{ __('Activation link copied!') }}');">
                        <i class="icon-copy mr-1"></i>{{ __('Copy') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        {{-- Parent Profile Info Card --}}
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm" style="border-radius: 16px;">
                <div class="card-body text-center p-4">
                    <img src="{{ $parent->photo }}" class="rounded-circle mb-3" style="width: 84px; height: 84px; object-fit: cover; border: 3px solid #FF5700;" alt="{{ $parent->name }}">
                    <h4 class="font-weight-bold text-dark mb-0">{{ $parent->name }}</h4>
                    <span class="badge badge-light border text-muted mb-2">@<span>{{ $parent->username }}</span></span>

                    <div class="mb-3">
                        @if($parent->is_active)
                            <span class="badge badge-success py-1 px-2">{{ __('Active Account') }}</span>
                        @else
                            <span class="badge badge-danger py-1 px-2">{{ __('Deactivated') }}</span>
                        @endif

                        @if($parent->activation_token)
                            <span class="badge badge-warning py-1 px-2 ml-1">{{ __('Pending Activation') }}</span>
                        @endif
                    </div>

                    <div class="border-top pt-3 text-left font-size-sm">
                        <div class="mb-2"><i class="icon-phone mr-2 text-primary"></i><strong>{{ $parent->phone ?: '—' }}</strong></div>
                        <div class="mb-2"><i class="icon-envelop mr-2 text-primary"></i><span>{{ $parent->email ?: __('No email set') }}</span></div>
                        <div class="mb-2"><i class="icon-location4 mr-2 text-primary"></i><span>{{ $parent->address ?: __('No address set') }}</span></div>
                        <div><i class="icon-calendar2 mr-2 text-primary"></i><span>{{ __('Joined') }}: {{ $parent->created_at ? $parent->created_at->format('M d, Y') : '—' }}</span></div>
                    </div>

                    <div class="border-top pt-3 mt-3 d-flex flex-column gap-2">
                        <form method="POST" action="{{ route('parents.reset_access', Qs::hash($parent->id)) }}" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary btn-block btn-sm">
                                <i class="icon-key mr-1"></i>{{ __('Generate Fresh Activation Link') }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('parents.toggle_active', Qs::hash($parent->id)) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-block btn-sm">
                                <i class="icon-switch mr-1"></i>{{ $parent->is_active ? __('Deactivate Account') : __('Activate Account') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Quick Edit Parent Details --}}
            <div class="card shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-white py-3">
                    <h6 class="font-weight-bold mb-0 text-dark"><i class="icon-pencil5 mr-2 text-primary"></i>{{ __('Edit Parent Details') }}</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('parents.update', Qs::hash($parent->id)) }}">
                        @csrf
                        @method('PUT')
                        <div class="form-group mb-2">
                            <label class="font-size-sm font-weight-semibold">{{ __('Full Name') }}:</label>
                            <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $parent->name) }}" required>
                        </div>
                        <div class="form-group mb-2">
                            <label class="font-size-sm font-weight-semibold">{{ __('Mobile Number') }}:</label>
                            <input type="text" name="phone" class="form-control form-control-sm" value="{{ old('phone', $parent->phone) }}" required>
                        </div>
                        <div class="form-group mb-2">
                            <label class="font-size-sm font-weight-semibold">{{ __('Email Address') }}:</label>
                            <input type="email" name="email" class="form-control form-control-sm" value="{{ old('email', $parent->email) }}">
                        </div>
                        <div class="form-group mb-3">
                            <label class="font-size-sm font-weight-semibold">{{ __('Address') }}:</label>
                            <input type="text" name="address" class="form-control form-control-sm" value="{{ old('address', $parent->address) }}">
                        </div>
                        <button type="submit" class="btn btn-primary btn-block btn-sm font-weight-semibold">
                            <i class="icon-checkmark4 mr-1"></i>{{ __('Save Changes') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Linked Students & Management Column --}}
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4" style="border-radius: 16px;">
                <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                    <div>
                        <h5 class="font-weight-bold mb-0 text-dark"><i class="icon-users4 mr-2" style="color: #FF5700;"></i>{{ __('Linked Students') }}</h5>
                        <span class="text-muted font-size-xs">{{ __('Students who can be accessed by this parent account') }}</span>
                    </div>

                    <button type="button" class="btn btn-outline-primary btn-sm font-weight-semibold" data-toggle="collapse" data-target="#collapse-link-student">
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
                                <select name="student_id" class="form-control select-search" required>
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
                                <button type="submit" class="btn btn-primary btn-block btn-sm font-weight-semibold">
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
                                <th>#</th>
                                <th>{{ __('Student') }}</th>
                                <th>{{ __('Class & Section') }}</th>
                                <th>{{ __('ADM No') }}</th>
                                <th>{{ __('Relationship') }}</th>
                                <th class="text-center">{{ __('Actions') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($parent->children as $ch)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="{{ $ch->user->photo }}" class="rounded-circle mr-2" style="width: 38px; height: 38px; object-fit: cover;" alt="{{ $ch->user->name }}">
                                            <div>
                                                <a href="{{ route('students.show', Qs::hash($ch->id)) }}" class="font-weight-semibold text-dark">
                                                    {{ $ch->user->name }}
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ ($ch->my_class->name ?? '') . ' ' . ($ch->section->name ?? '') }}</td>
                                    <td><span class="badge badge-light border">{{ $ch->adm_no }}</span></td>
                                    <td>
                                        <span class="badge badge-primary">{{ $ch->parent_relationship ?: __('Guardian') }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1">
                                            <a href="{{ route('students.show', Qs::hash($ch->id)) }}" class="btn btn-light btn-sm mr-1" title="{{ __('View Student Profile') }}">
                                                <i class="icon-eye"></i>
                                            </a>
                                            <form method="POST" action="{{ route('parents.unlink_student', [Qs::hash($parent->id), Qs::hash($ch->id)]) }}" onsubmit="return confirm('{{ __('Are you sure you want to unlink this student from parent :name?', ['name' => $parent->name]) }}');">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="{{ __('Unlink Student') }}">
                                                    <i class="icon-unlink"></i> {{ __('Unlink') }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="icon-user-cancel icon-2x d-block mb-2 text-muted"></i>
                                        <h6 class="font-weight-bold text-dark">{{ __('No Students Currently Linked') }}</h6>
                                        <p class="font-size-sm mb-0">{{ __('Use the button above to link an existing enrolled student to this parent account.') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="text-left">
                <a href="{{ route('parents.index') }}" class="btn btn-light btn-sm font-weight-semibold">
                    <i class="icon-arrow-left7 mr-1"></i>{{ __('Back to Parents List') }}
                </a>
            </div>
        </div>
    </div>
@endsection
