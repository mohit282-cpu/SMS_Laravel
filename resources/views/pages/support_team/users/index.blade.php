@extends('layouts.master')
@section('page_title', __('Manage Users'))
@section('content')

    <div class="card">
        <div class="card-header header-elements-inline">
            <h2 class="card-title font-weight-bold mb-0" style="font-size: 1.1rem;">
                <i class="icon-users4 mr-2" style="color: #FF5700;"></i>{{ __('Manage System Users') }}
            </h2>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <ul class="nav nav-tabs nav-tabs-highlight">
                <li class="nav-item">
                    <a href="#all-users" class="nav-link active" data-toggle="tab">
                        <i class="icon-users4 mr-2"></i>{{ __('All Staff & Users') }}
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" data-default="{{ __('Filter by Role') }}">
                        <i class="icon-filter3 mr-1"></i><span class="selected-text">{{ __('Filter by Role') }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        @foreach($user_types as $ut)
                            <a href="#ut-{{ Qs::hash($ut->id) }}" class="dropdown-item" data-toggle="tab">
                                <i class="icon-user mr-2"></i>{{ $ut->name }}s
                            </a>
                        @endforeach
                    </div>
                </li>
                <li class="nav-item">
                    <a href="#new-user" class="nav-link" data-toggle="tab">
                        <i class="icon-user-plus mr-2"></i>{{ __('Create New User') }}
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                {{-- All Users Tab --}}
                <div class="tab-pane fade show active" id="all-users">
                    <div class="table-responsive">
                        <table class="table datatable-button-html5-columns">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Photo') }}</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Role') }}</th>
                                <th>{{ __('Username') }}</th>
                                <th>{{ __('Phone') }}</th>
                                <th>{{ __('Email') }}</th>
                                <th class="text-center">{{ __('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($users as $u)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><img class="rounded-circle" style="height: 40px; width: 40px; object-fit: cover;" src="{{ $u->photo }}" alt="photo"></td>
                                    <td class="font-weight-semibold">{{ $u->name }}</td>
                                    <td>
                                        @if($u->user_type === 'super_admin')
                                            <span class="badge badge-danger p-1">{{ __('Super Admin') }}</span>
                                        @elseif($u->user_type === 'admin')
                                            <span class="badge badge-warning text-white p-1">{{ __('Admin') }}</span>
                                        @elseif($u->user_type === 'teacher')
                                            <span class="badge badge-primary p-1">{{ __('Teacher') }}</span>
                                        @elseif($u->user_type === 'accountant')
                                            <span class="badge badge-success p-1">{{ __('Accountant') }}</span>
                                        @elseif($u->user_type === 'librarian')
                                            <span class="badge badge-info p-1">{{ __('Librarian') }}</span>
                                        @elseif($u->user_type === 'parent')
                                            <span class="badge badge-secondary p-1">{{ __('Parent') }}</span>
                                        @else
                                            <span class="badge badge-light p-1">{{ ucfirst($u->user_type) }}</span>
                                        @endif
                                    </td>
                                    <td><code>{{ $u->username }}</code></td>
                                    <td>{{ $u->phone ?: '-' }}</td>
                                    <td>{{ $u->email ?: '-' }}</td>
                                    <td class="text-center">
                                        <div class="list-icons">
                                            <div class="dropdown">
                                                <a href="#" class="list-icons-item" data-toggle="dropdown">
                                                    <i class="icon-menu9"></i>
                                                </a>

                                                <div class="dropdown-menu dropdown-menu-right">
                                                    {{--View Profile--}}
                                                    <a href="{{ route('users.show', Qs::hash($u->id)) }}" class="dropdown-item"><i class="icon-eye"></i> {{ __('View Profile') }}</a>
                                                    {{--Edit--}}
                                                    <a href="{{ route('users.edit', Qs::hash($u->id)) }}" class="dropdown-item"><i class="icon-pencil"></i> {{ __('Edit') }}</a>
                                                    @if(Qs::userIsSuperAdmin() && !Qs::headSA($u->id) && Auth::id() !== $u->id)
                                                        {{--Reset Password--}}
                                                        <a href="#" onclick="event.preventDefault(); if(confirm('{{ __('Are you sure you want to reset this user\'s password?') }}')) { document.getElementById('item-reset-all-{{ Qs::hash($u->id) }}').submit(); }" class="dropdown-item"><i class="icon-lock"></i> {{ __('Reset Password') }}</a>
                                                        <form method="post" id="item-reset-all-{{ Qs::hash($u->id) }}" action="{{ route('users.reset_pass', Qs::hash($u->id)) }}" class="hidden">@csrf</form>
                                                        {{--Delete--}}
                                                        <a id="del-all-{{ Qs::hash($u->id) }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item text-danger"><i class="icon-trash"></i> {{ __('Delete') }}</a>
                                                        <form method="post" id="item-delete-del-all-{{ Qs::hash($u->id) }}" action="{{ route('users.destroy', Qs::hash($u->id)) }}" class="hidden">@csrf @method('delete')</form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Role-specific Tabs --}}
                @foreach($user_types as $ut)
                    <div class="tab-pane fade" id="ut-{{ Qs::hash($ut->id) }}">
                        <div class="table-responsive">
                            <table class="table datatable-button-html5-columns">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('Photo') }}</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Username') }}</th>
                                    <th>{{ __('Phone') }}</th>
                                    <th>{{ __('Email') }}</th>
                                    <th class="text-center">{{ __('Action') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($users->where('user_type', $ut->title) as $u)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td><img class="rounded-circle" style="height: 40px; width: 40px; object-fit: cover;" src="{{ $u->photo }}" alt="photo"></td>
                                        <td class="font-weight-semibold">{{ $u->name }}</td>
                                        <td><code>{{ $u->username }}</code></td>
                                        <td>{{ $u->phone ?: '-' }}</td>
                                        <td>{{ $u->email ?: '-' }}</td>
                                        <td class="text-center">
                                            <div class="list-icons">
                                                <div class="dropdown">
                                                    <a href="#" class="list-icons-item" data-toggle="dropdown">
                                                        <i class="icon-menu9"></i>
                                                    </a>

                                                    <div class="dropdown-menu dropdown-menu-right">
                                                        {{--View Profile--}}
                                                        <a href="{{ route('users.show', Qs::hash($u->id)) }}" class="dropdown-item"><i class="icon-eye"></i> {{ __('View Profile') }}</a>
                                                        {{--Edit--}}
                                                        <a href="{{ route('users.edit', Qs::hash($u->id)) }}" class="dropdown-item"><i class="icon-pencil"></i> {{ __('Edit') }}</a>
                                                        @if(Qs::userIsSuperAdmin() && !Qs::headSA($u->id) && Auth::id() !== $u->id)
                                                            {{--Reset Password--}}
                                                            <a href="#" onclick="event.preventDefault(); if(confirm('{{ __('Are you sure you want to reset this user\'s password?') }}')) { document.getElementById('item-reset-{{ Qs::hash($u->id) }}').submit(); }" class="dropdown-item"><i class="icon-lock"></i> {{ __('Reset Password') }}</a>
                                                            <form method="post" id="item-reset-{{ Qs::hash($u->id) }}" action="{{ route('users.reset_pass', Qs::hash($u->id)) }}" class="hidden">@csrf</form>
                                                            {{--Delete--}}
                                                            <a id="{{ Qs::hash($u->id) }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item text-danger"><i class="icon-trash"></i> {{ __('Delete') }}</a>
                                                            <form method="post" id="item-delete-{{ Qs::hash($u->id) }}" action="{{ route('users.destroy', Qs::hash($u->id)) }}" class="hidden">@csrf @method('delete')</form>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach

                {{-- Create User Tab --}}
                <div class="tab-pane fade" id="new-user">
                    <form method="post" enctype="multipart/form-data" class="ajax-store" action="{{ route('users.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="user_type" class="font-weight-semibold">{{ __('Select Role') }}: <span class="text-danger">*</span></label>
                                    <select required data-placeholder="{{ __('Choose Role...') }}" class="form-control select" name="user_type" id="user_type">
                                        <option value=""></option>
                                        @foreach($user_types as $ut)
                                            <option value="{{ Qs::hash($ut->id) }}">{{ $ut->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-semibold">{{ __('Full Name') }}: <span class="text-danger">*</span></label>
                                    <input value="{{ old('name') }}" required type="text" name="name" placeholder="{{ __('e.g. Ram Bahadur Thapa') }}" class="form-control">
                                </div>
                            </div>

                            <div class="col-md-5">
                                <div class="form-group">
                                    <label class="font-weight-semibold">{{ __('Address') }}: <span class="text-danger">*</span></label>
                                    <input value="{{ old('address') }}" class="form-control" placeholder="{{ __('e.g. Kathmandu, Baneshwor') }}" name="address" type="text" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="font-weight-semibold">{{ __('Email Address') }}: </label>
                                    <input value="{{ old('email') }}" type="email" name="email" class="form-control" placeholder="user@school.edu.np">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="font-weight-semibold">{{ __('Username') }}: </label>
                                    <input value="{{ old('username') }}" type="text" name="username" class="form-control" placeholder="{{ __('Leave blank to auto-generate') }}">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="font-weight-semibold">{{ __('Mobile Phone') }}:</label>
                                    <input value="{{ old('phone') }}" type="text" name="phone" class="form-control" placeholder="98XXXXXXXX">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="font-weight-semibold">{{ __('Telephone / Alt Phone') }}:</label>
                                    <input value="{{ old('phone2') }}" type="text" name="phone2" class="form-control" placeholder="01-XXXXXXX">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="font-weight-semibold">{{ __('Date of Employment') }}:</label>
                                    <input autocomplete="off" name="emp_date" value="{{ old('emp_date') }}" type="text" class="form-control date-pick" placeholder="{{ __('Select Date...') }}">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="password" class="font-weight-semibold">{{ __('Password') }}: </label>
                                    <input id="password" type="password" name="password" class="form-control" placeholder="{{ __('Leave blank to auto-generate') }}">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="gender" class="font-weight-semibold">{{ __('Gender') }}: <span class="text-danger">*</span></label>
                                    <select class="select form-control" id="gender" name="gender" required data-placeholder="{{ __('Choose...') }}">
                                        <option value=""></option>
                                        <option {{ (old('gender') == 'Male') ? 'selected' : '' }} value="Male">{{ __('Male') }}</option>
                                        <option {{ (old('gender') == 'Female') ? 'selected' : '' }} value="Female">{{ __('Female') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="nal_id" class="font-weight-semibold">{{ __('Nationality') }}: <span class="text-danger">*</span></label>
                                    <select data-placeholder="{{ __('Choose...') }}" required name="nal_id" id="nal_id" class="select-search form-control">
                                        <option value=""></option>
                                        @foreach($nationals as $nal)
                                            <option {{ (old('nal_id') == $nal->id || (old('nal_id') == null && $nal->name == 'Nepalese') ? 'selected' : '') }} value="{{ $nal->id }}">{{ $nal->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Nepal Address Selector --}}
                        <x-nepal-address-selector :provinces="$provinces ?? null" :required="true" />

                        <div class="row">
                            {{-- BLOOD GROUP --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="bg_id" class="font-weight-semibold">{{ __('Blood Group') }}: </label>
                                    <select class="select form-control" id="bg_id" name="bg_id" data-placeholder="{{ __('Choose...') }}">
                                        <option value=""></option>
                                        @foreach($blood_groups as $bg)
                                            <option {{ (old('bg_id') == $bg->id ? 'selected' : '') }} value="{{ $bg->id }}">{{ $bg->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- PASSPORT PHOTO --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="d-block font-weight-semibold">{{ __('Passport Photo') }}:</label>
                                    <input value="{{ old('photo') }}" accept="image/*" type="file" name="photo" class="form-input-styled">
                                    <span class="form-text text-muted">{{ __('Accepted: jpeg, png. Max file size: 2MB') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="text-right mt-3">
                            <button type="submit" class="btn btn-primary font-weight-semibold">
                                {{ __('Create User Account') }} <i class="icon-paperplane ml-2"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
