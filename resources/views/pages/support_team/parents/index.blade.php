@extends('layouts.master')
@section('page_title', 'Parent Account Management')
@section('content')

    {{-- Activation Link Flash Notification --}}
    @if(session('activation_url'))
        <div class="alert alert-success alert-dismissible shadow-sm mb-4" style="border-left: 5px solid #28a745; border-radius: 12px;">
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            <h5 class="alert-heading font-weight-bold mb-1">
                <i class="icon-checkmark-circle mr-1"></i>{{ __('Parent Account Activation Link Ready') }}
            </h5>
            <p class="mb-2">{{ __('Share this secure, time-limited activation link with parent :name to set their password:', ['name' => session('parent_name') ?? '']) }}</p>
            <div class="input-group" style="max-width: 600px;">
                <input type="text" id="activation-link-input" class="form-control font-weight-semibold" value="{{ session('activation_url') }}" readonly>
                <div class="input-group-append">
                    <button class="btn btn-dark" type="button" onclick="navigator.clipboard.writeText(document.getElementById('activation-link-input').value); alert('{{ __('Activation link copied to clipboard!') }}');">
                        <i class="icon-copy mr-1"></i>{{ __('Copy Link') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div class="card shadow-sm" style="border-radius: 16px;">
        <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
            <div class="d-flex align-items-center">
                <div class="d-inline-flex align-items-center justify-content-center mr-3" style="width: 44px; height: 44px; border-radius: 12px; background: rgba(255, 87, 0, 0.1);">
                    <i class="icon-users4 icon-2x" style="color: #FF5700;"></i>
                </div>
                <div>
                    <h2 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.15rem;">{{ __('Parent Accounts & Portal Management') }}</h2>
                    <span class="text-muted font-size-sm">{{ __('Manage parent users, linked students, account activation, and access credentials') }}</span>
                </div>
            </div>

            <div class="mt-2 mt-md-0 d-flex gap-2">
                <button type="button" class="btn btn-primary btn-sm font-weight-semibold" data-toggle="modal" data-target="#modal-add-parent">
                    <i class="icon-user-plus mr-1"></i>{{ __('Create Parent Account') }}
                </button>
            </div>
        </div>

        <div class="card-body">
            {{-- Search Filter Form --}}
            <form method="GET" action="{{ route('parents.index') }}" class="mb-4">
                <div class="row">
                    <div class="col-md-6 col-lg-5">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="{{ __('Search by parent name, mobile number, email, or username...') }}" value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit"><i class="icon-search4 mr-1"></i>{{ __('Search') }}</button>
                                @if(request('search'))
                                    <a href="{{ route('parents.index') }}" class="btn btn-light"><i class="icon-cross2"></i></a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            {{-- Parents Table --}}
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('Parent Details') }}</th>
                        <th>{{ __('Contact Information') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Linked Student(s)') }}</th>
                        <th class="text-center">{{ __('Actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($parents as $p)
                        <tr>
                            <td>{{ $loop->iteration + ($parents->currentPage() - 1) * $parents->perPage() }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="{{ $p->photo }}" class="rounded-circle mr-2" style="width: 40px; height: 40px; object-fit: cover;" alt="{{ $p->name }}">
                                    <div>
                                        <a href="{{ route('parents.show', Qs::hash($p->id)) }}" class="font-weight-bold text-dark d-block">
                                            {{ $p->name }}
                                        </a>
                                        <span class="text-muted font-size-xs">@<span>{{ $p->username }}</span></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><i class="icon-phone mr-1 text-muted"></i><strong>{{ $p->phone ?: '—' }}</strong></div>
                                @if($p->email)
                                    <div class="font-size-xs text-muted"><i class="icon-envelop mr-1"></i>{{ $p->email }}</div>
                                @endif
                            </td>
                            <td>
                                @if($p->is_active)
                                    <span class="badge badge-success py-1 px-2">{{ __('Active') }}</span>
                                @else
                                    <span class="badge badge-danger py-1 px-2">{{ __('Deactivated') }}</span>
                                @endif

                                @if($p->activation_token)
                                    <span class="badge badge-warning py-1 px-2 ml-1" title="{{ __('Pending password setup') }}">{{ __('Pending Activation') }}</span>
                                @endif
                            </td>
                            <td>
                                @if($p->children->isNotEmpty())
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($p->children as $ch)
                                            <span class="badge badge-light border py-1 px-2 mr-1 mb-1" style="font-size: 0.8rem;">
                                                <i class="icon-user text-primary mr-1"></i>{{ $ch->user->name }}
                                                <span class="text-muted">({{ $ch->my_class->name ?? '' }})</span>
                                                @if($ch->parent_relationship)
                                                    <span class="text-danger font-italic ml-1">[{{ $ch->parent_relationship }}]</span>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted font-italic font-size-xs">{{ __('No students linked') }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="list-icons">
                                    <div class="dropdown">
                                        <a href="#" class="list-icons-item" data-toggle="dropdown">
                                            <i class="icon-menu9"></i>
                                        </a>

                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a href="{{ route('parents.show', Qs::hash($p->id)) }}" class="dropdown-item">
                                                <i class="icon-eye"></i> {{ __('View & Manage Linked Students') }}
                                            </a>

                                            <form method="POST" action="{{ route('parents.reset_access', Qs::hash($p->id)) }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="icon-key"></i> {{ __('Generate New Activation Link') }}
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('parents.toggle_active', Qs::hash($p->id)) }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="icon-switch"></i> {{ $p->is_active ? __('Deactivate Account') : __('Activate Account') }}
                                                </button>
                                            </form>

                                            @if(Qs::userIsSuperAdmin() && $p->children->isEmpty())
                                                <div class="dropdown-divider"></div>
                                                <form method="POST" action="{{ route('parents.destroy', Qs::hash($p->id)) }}" onsubmit="return confirm('{{ __('Are you sure you want to delete this parent account?') }}');">
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
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="icon-users4 icon-2x d-block mb-1 text-muted"></i>
                                {{ __('No parent accounts found matching your query.') }}
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $parents->links() }}
            </div>
        </div>
    </div>

    {{-- Create Parent Modal --}}
    <div id="modal-add-parent" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius: 16px;">
                <div class="modal-header bg-white py-3">
                    <h5 class="modal-title font-weight-bold text-dark"><i class="icon-user-plus mr-2" style="color: #FF5700;"></i>{{ __('Create Parent Account') }}</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <form method="POST" action="{{ route('parents.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Parent Full Name') }}: <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="{{ __('e.g. Maya Devi Adhikari') }}" value="{{ old('name') }}">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Mobile Number') }}: <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" required placeholder="{{ __('e.g. 9841234567') }}" value="{{ old('phone') }}">
                            <span class="form-text text-muted">{{ __('Phone number is normalized and used for parent login and SMS delivery.') }}</span>
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
                                    <label>{{ __('Select Student') }}:</label>
                                    <select name="student_id" class="form-control select">
                                        <option value="">{{ __('None (Link Later)') }}</option>
                                        @foreach($unlinked_students as $st)
                                            <option value="{{ $st->id }}">{{ $st->user->name }} ({{ $st->my_class->name ?? '' }} - {{ $st->adm_no }})</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Relationship') }}:</label>
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

                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-link text-muted" data-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary font-weight-semibold"><i class="icon-checkmark4 mr-1"></i>{{ __('Create & Generate Activation Link') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
