@extends('layouts.master')
@section('page_title', __('Direct Portal Messages'))
@section('content')

    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <h3 class="font-weight-bold mb-1 text-dark">{{ __('School Direct Messaging & Parent-Teacher Chat') }}</h3>
            <p class="text-muted mb-0 font-size-sm">{{ __('Direct portal communication between parents, students, teachers, and administration') }}</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card sms-card">
                <div class="card-header"><h6 class="font-weight-bold mb-0">{{ __('Compose Message') }}</h6></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('messages.send') }}">
                        @csrf
                        <div class="form-group">
                            <label for="msg_recipient">{{ __('Recipient') }} *</label>
                            <select id="msg_recipient" name="recipient_id" class="form-control" required>
                                @foreach($recipients as $r)
                                    <option value="{{ $r->id }}">{{ $r->name }} ({{ ucwords(str_replace('_', ' ', $r->user_type)) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group"><label for="msg_subject">{{ __('Subject') }} *</label><input id="msg_subject" type="text" name="subject" class="form-control" required></div>
                        <div class="form-group"><label for="msg_body">{{ __('Message') }} *</label><textarea id="msg_body" name="body" rows="4" class="form-control" required></textarea></div>
                        <button type="submit" class="btn btn-primary btn-block">{{ __('Send Message') }}</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card sms-card">
                <div class="card-header"><h6 class="font-weight-bold mb-0">{{ __('Inbox & Sent History') }}</h6></div>
                <div class="card-body">
                    @forelse($inbox as $msg)
                        <div class="p-3 mb-3 rounded border bg-light">
                            <div class="d-flex justify-content-between mb-1">
                                <strong>{{ $msg->subject }}</strong>
                                <span class="badge badge-{{ (int) $msg->sender_id === (int) Auth::id() ? 'info' : 'primary' }}">{{ (int) $msg->sender_id === (int) Auth::id() ? __('Sent') : __('Received') }}</span>
                            </div>
                            <div class="text-muted font-size-xs mb-2">{{ __('From') }}: {{ $msg->sender->name ?? '-' }} &rarr; {{ __('To') }}: {{ $msg->recipient->name ?? '-' }} • {{ $msg->created_at->diffForHumans() }}</div>
                            <p class="mb-0">{{ $msg->body }}</p>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">{{ __('No messages in your inbox yet.') }}</div>
                    @endforelse
                    {{ $inbox->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
