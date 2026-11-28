@extends('layouts.master')
@section('page_title', __('Smart Communication & Notification Logs'))
@section('content')

    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <h3 class="font-weight-bold mb-1 text-dark">{{ __('Smart SMS, Push Broadcasts & Birthday Wishes Log') }}</h3>
            <p class="text-muted mb-0 font-size-sm">{{ __('Targeted notifications, SMS/email queue tracking & today\'s student/staff birthday roster') }}</p>
        </div>
    </div>

    @if($birthdayUsers->isNotEmpty())
        <div class="alert alert-info mb-4">
            <strong><i class="icon-gift mr-1"></i> {{ __("Today's Birthdays:") }}</strong>
            @foreach($birthdayUsers as $bu)
                <span class="badge badge-primary mr-1">{{ $bu->name }} ({{ ucfirst($bu->user_type) }})</span>
            @endforeach
        </div>
    @endif

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card sms-card">
                <div class="card-header"><h6 class="font-weight-bold mb-0">{{ __('Send Notification Broadcast') }}</h6></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('communications.broadcast') }}">
                        @csrf
                        <div class="form-group"><label>{{ __('Channel') }}</label><select name="channel" class="form-control"><option value="in_app">In-App Notification</option><option value="web_push">Web Push Notification</option><option value="sms_queued">Smart SMS (Queue Log)</option><option value="email_queued">Email (Queue Log)</option></select></div>
                        <div class="form-group"><label>{{ __('Target Group') }}</label><select name="target_role" class="form-control"><option value="all">All Users</option><option value="parent">Parents</option><option value="student">Students</option><option value="teacher">Teachers</option></select></div>
                        <div class="form-group"><label>{{ __('Category') }}</label><input type="text" name="category" class="form-control" value="general" required></div>
                        <div class="form-group"><label>{{ __('Title') }} *</label><input type="text" name="title" class="form-control" required></div>
                        <div class="form-group"><label>{{ __('Message') }} *</label><textarea name="message" rows="3" class="form-control" required></textarea></div>
                        <button type="submit" class="btn btn-primary btn-block">{{ __('Dispatch Broadcast') }}</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card sms-card">
                <div class="card-header"><h6 class="font-weight-bold mb-0">{{ __('Notification Delivery Log') }}</h6></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead><tr><th>{{ __('Channel') }}</th><th>{{ __('Target') }}</th><th>{{ __('Title & Message') }}</th><th>{{ __('Status') }}</th></tr></thead>
                            <tbody>
                                @forelse($logs as $lg)
                                    <tr>
                                        <td><span class="badge badge-info text-uppercase">{{ str_replace('_', ' ', $lg->channel) }}</span></td>
                                        <td class="text-capitalize">{{ $lg->target_role ?? 'User' }}</td>
                                        <td><strong>{{ $lg->title }}</strong> <div class="text-muted font-size-xs">{{ \Illuminate\Support\Str::limit($lg->message, 80) }}</div></td>
                                        <td><span class="badge badge-{{ $lg->status === 'sent' ? 'success' : 'warning' }} text-uppercase">{{ $lg->status }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">{{ __('No notifications logged yet.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
