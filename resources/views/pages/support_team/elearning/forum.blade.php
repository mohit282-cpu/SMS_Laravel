@extends('layouts.master')
@section('page_title', __('Academic Discussion Forum'))
@section('content')

    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <h3 class="font-weight-bold mb-1 text-dark">{{ __('E-Classroom Discussion Forum') }}</h3>
            <p class="text-muted mb-0 font-size-sm">{{ __('Collaborate on coursework, ask questions, and share subject clarifications') }}</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card sms-card">
                <div class="card-header"><h6 class="font-weight-bold mb-0">{{ __('Start New Topic') }}</h6></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('elearning.forum.store') }}">
                        @csrf
                        <div class="form-group"><label for="ft_title">{{ __('Topic Title') }} *</label><input id="ft_title" type="text" name="title" class="form-control" required></div>
                        <div class="form-group">
                            <label for="ft_class">{{ __('Class (Optional)') }}</label>
                            <select id="ft_class" name="my_class_id" class="form-control"><option value="">{{ __('All Classes') }}</option>@foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
                        </div>
                        <div class="form-group">
                            <label for="ft_sub">{{ __('Subject (Optional)') }}</label>
                            <select id="ft_sub" name="subject_id" class="form-control"><option value="">{{ __('General') }}</option>@foreach($subjects as $sub)<option value="{{ $sub->id }}">{{ $sub->name }}</option>@endforeach</select>
                        </div>
                        <div class="form-group"><label for="ft_body">{{ __('Message') }} *</label><textarea id="ft_body" name="body" rows="4" class="form-control" required></textarea></div>
                        <button type="submit" class="btn btn-primary">{{ __('Post Topic') }}</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            @forelse($threads as $thread)
                <div class="card sms-card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h5 class="font-weight-bold mb-1">{{ $thread->title }}</h5>
                            <span class="badge badge-info">{{ $thread->replies_count }} {{ __('replies') }}</span>
                        </div>
                        <div class="text-muted font-size-xs mb-2">{{ $thread->author->name ?? '-' }} • {{ $thread->my_class->name ?? __('General') }} @if($thread->subject) / {{ $thread->subject->name }} @endif</div>
                        <p class="mb-3">{{ $thread->body }}</p>

                        @foreach($thread->replies as $reply)
                            <div class="p-2 mb-2 rounded bg-light border-left border-primary">
                                <strong>{{ $reply->author->name ?? '-' }}:</strong> {{ $reply->body }}
                            </div>
                        @endforeach

                        <form method="POST" action="{{ route('elearning.forum.reply', $thread->id) }}" class="mt-2 d-flex" style="gap: 0.5rem;">
                            @csrf
                            <input type="text" name="body" class="form-control form-control-sm" placeholder="{{ __('Write a reply...') }}" required>
                            <button type="submit" class="btn btn-sm btn-primary">{{ __('Reply') }}</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="card sms-card"><div class="card-body text-center text-muted py-4">{{ __('No forum discussions yet.') }}</div></div>
            @endforelse
            {{ $threads->links() }}
        </div>
    </div>
@endsection
