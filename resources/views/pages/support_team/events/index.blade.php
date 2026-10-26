@extends('layouts.master')
@section('page_title', __('School Events & Calendar (BS)'))
@section('content')

    <div class="card shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title font-weight-bold mb-0">
                <i class="icon-calendar3 mr-2" style="color: #FF5700;"></i>{{ __('School Calendar & Event Management (Bikram Sambat BS)') }}
            </h5>
            <div class="d-flex align-items-center">
                @if($isAdmin)
                    <a href="{{ route('events.create') }}" class="btn btn-sm text-white mr-2" style="background: #FF5700; border-radius: 8px;">
                        <i class="icon-plus2 mr-1"></i>{{ __('Create Event') }}
                    </a>
                @endif
                {!! Qs::getPanelOptions() !!}
            </div>
        </div>

        <div class="card-body">
            <ul class="nav nav-tabs nav-tabs-highlight mb-3">
                <li class="nav-item">
                    <a href="#calendar-tab" class="nav-link active" data-toggle="tab">
                        <i class="icon-calendar mr-1"></i> {{ __('Bikram Sambat Calendar') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#events-list-tab" class="nav-link" data-toggle="tab">
                        <i class="icon-list mr-1"></i> {{ __('All Events List') }}
                    </a>
                </li>
                @if($isAdmin)
                    <li class="nav-item">
                        <a href="#new-event-tab" class="nav-link" data-toggle="tab">
                            <i class="icon-plus2 mr-1"></i> {{ __('Quick Add Event') }}
                        </a>
                    </li>
                @endif
            </ul>

            <div class="tab-content">
                {{-- Calendar View Tab --}}
                <div class="tab-pane fade show active" id="calendar-tab">
                    <div id="events-bs-calendar"></div>
                </div>

                {{-- Events List Table Tab --}}
                <div class="tab-pane fade" id="events-list-tab">
                    {{-- Filter Form --}}
                    <form method="GET" action="{{ route('events.index') }}" class="mb-4 p-3 bg-light rounded border">
                        <div class="row align-items-end">
                            <div class="col-md-3 mb-2">
                                <label class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('Category') }}</label>
                                <select name="category" class="form-control form-control-sm">
                                    <option value="">{{ __('All Categories') }}</option>
                                    @foreach($categories as $key => $c)
                                        <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>
                                            {{ (app()->getLocale() === 'ne') ? $c['name_ne'] : $c['name_en'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('Session') }}</label>
                                <select name="session" class="form-control form-control-sm">
                                    <option value="">{{ __('All Sessions') }}</option>
                                    @foreach($academic_years as $ay)
                                        <option value="{{ $ay->code }}" {{ (request('session') === $ay->code || (!request()->has('session') && $ay->is_active)) ? 'selected' : '' }}>
                                            {{ $ay->name }} ({{ $ay->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @if($isAdmin)
                                <div class="col-md-2 mb-2">
                                    <label class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('Status') }}</label>
                                    <select name="is_published" class="form-control form-control-sm">
                                        <option value="">{{ __('All Statuses') }}</option>
                                        <option value="1" {{ request('is_published') === '1' ? 'selected' : '' }}>{{ __('Published') }}</option>
                                        <option value="0" {{ request('is_published') === '0' ? 'selected' : '' }}>{{ __('Draft / Unpublished') }}</option>
                                    </select>
                                </div>
                            @endif
                            <div class="col-md-2 mb-2">
                                <label class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('From (BS)') }}</label>
                                <input type="text" name="date_from_bs" value="{{ request('date_from_bs') }}" class="form-control form-control-sm nepali-date" placeholder="YYYY-MM-DD">
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('To (BS)') }}</label>
                                <input type="text" name="date_to_bs" value="{{ request('date_to_bs') }}" class="form-control form-control-sm nepali-date" placeholder="YYYY-MM-DD">
                            </div>
                            <div class="col-md-1 mb-2">
                                <button type="submit" class="btn btn-primary btn-sm btn-block">
                                    <i class="icon-search4"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('Event Title') }}</th>
                                <th>{{ __('Category') }}</th>
                                <th>{{ __('BS Date Range') }}</th>
                                <th>{{ __('Time / Schedule') }}</th>
                                <th>{{ __('Audience') }}</th>
                                <th>{{ __('Status') }}</th>
                                @if($isAdmin)
                                    <th class="text-center">{{ __('Actions') }}</th>
                                @endif
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($events as $ev)
                                <tr>
                                    <td>{{ $loop->iteration + ($events->currentPage() - 1) * $events->perPage() }}</td>
                                    <td>
                                        <div class="font-weight-semibold text-dark">{{ $ev->title }}</div>
                                        @if($ev->title_ne && $ev->title_ne !== $ev->title)
                                            <div class="text-muted font-size-xs">{{ $ev->title_ne }}</div>
                                        @endif
                                        @if($ev->location)
                                            <div class="text-muted font-size-xs"><i class="icon-location4 mr-1"></i>{{ $ev->location }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge" style="background: {{ $ev->category_color }}; color: #FFFFFF;">
                                            {{ $ev->category_name }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="font-weight-semibold">{{ $ev->start_date_bs }}</span>
                                        @if($ev->end_date_bs && $ev->end_date_bs !== $ev->start_date_bs)
                                            <span class="text-muted font-size-xs d-block">to {{ $ev->end_date_bs }}</span>
                                        @endif
                                        <span class="text-muted font-size-xs d-block">({{ $ev->start_date ? $ev->start_date->format('d M Y') : '-' }} AD)</span>
                                    </td>
                                    <td>
                                        @if($ev->is_all_day)
                                            <span class="badge badge-light border">{{ __('All Day') }}</span>
                                        @elseif($ev->start_time)
                                            <span class="font-size-sm">{{ $ev->start_time }} {{ $ev->end_time ? '- ' . $ev->end_time : '' }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($ev->target_audience === 'all')
                                            <span class="badge badge-primary">{{ __('Entire School') }}</span>
                                        @elseif($ev->target_audience === 'classes')
                                            <span class="badge badge-info">{{ $ev->my_class->name ?? 'Class' }} {{ $ev->section ? '(' . $ev->section->name . ')' : '' }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ ucfirst($ev->target_audience) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($ev->is_published)
                                            <span class="badge badge-success">{{ __('Published') }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ __('Draft') }}</span>
                                        @endif
                                    </td>
                                    @if($isAdmin)
                                        <td class="text-center">
                                            <div class="list-icons">
                                                <div class="dropdown">
                                                    <a href="#" class="list-icons-item" data-toggle="dropdown">
                                                        <i class="icon-menu9"></i>
                                                    </a>
                                                    <div class="dropdown-menu dropdown-menu-right">
                                                        <a href="{{ route('events.show', $ev->id) }}" class="dropdown-item">
                                                            <i class="icon-eye mr-2"></i>{{ __('View Details') }}
                                                        </a>
                                                        <a href="{{ route('events.edit', $ev->id) }}" class="dropdown-item">
                                                            <i class="icon-pencil mr-2"></i>{{ __('Edit Event') }}
                                                        </a>
                                                        @if($ev->is_published)
                                                            <form action="{{ route('events.unpublish', $ev->id) }}" method="POST">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item">
                                                                    <i class="icon-eye-blocked mr-2"></i>{{ __('Unpublish') }}
                                                                </button>
                                                            </form>
                                                        @else
                                                            <form action="{{ route('events.publish', $ev->id) }}" method="POST">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item">
                                                                    <i class="icon-eye mr-2"></i>{{ __('Publish') }}
                                                                </button>
                                                            </form>
                                                        @endif
                                                        @if(!$ev->exam_id)
                                                            <div class="dropdown-divider"></div>
                                                            <form action="{{ route('events.destroy', $ev->id) }}" method="POST" onsubmit="return confirm('{{ __('Are you sure you want to delete this event?') }}');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="dropdown-item text-danger">
                                                                    <i class="icon-trash mr-2"></i>{{ __('Delete Event') }}
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isAdmin ? 8 : 7 }}" class="text-center py-4 text-muted">
                                        <i class="icon-calendar-cross icon-2x mb-2 d-block"></i>
                                        {{ __('No school events found matching the criteria.') }}
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 d-flex justify-content-end">
                        {{ $events->links() }}
                    </div>
                </div>

                {{-- Quick Add Event Tab (Admins) --}}
                @if($isAdmin)
                    <div class="tab-pane fade" id="new-event-tab">
                        <form method="POST" action="{{ route('events.store') }}" class="p-3">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-semibold">{{ __('Event Title (English)') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="title" required value="{{ old('title') }}" class="form-control" placeholder="e.g. Annual Sports Meet 2083">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-semibold">{{ __('Event Title (Nepali)') }}</label>
                                    <input type="text" name="title_ne" value="{{ old('title_ne') }}" class="form-control" placeholder="उदा. वार्षिक खेलकुद सप्ताह २०८३">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="font-weight-semibold">{{ __('Category') }} <span class="text-danger">*</span></label>
                                    <select name="category" required class="form-control">
                                        @foreach($categories as $key => $c)
                                            <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>
                                                {{ (app()->getLocale() === 'ne') ? $c['name_ne'] : $c['name_en'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="font-weight-semibold">{{ __('Start Date (BS)') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="start_date_bs" required value="{{ old('start_date_bs', $today_bs['bs_date'] ?? '') }}" class="form-control nepali-date" placeholder="YYYY-MM-DD">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="font-weight-semibold">{{ __('End Date (BS)') }}</label>
                                    <input type="text" name="end_date_bs" value="{{ old('end_date_bs', $today_bs['bs_date'] ?? '') }}" class="form-control nepali-date" placeholder="YYYY-MM-DD">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="font-weight-semibold">{{ __('Start Time') }}</label>
                                    <input type="time" name="start_time" value="{{ old('start_time') }}" class="form-control">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="font-weight-semibold">{{ __('End Time') }}</label>
                                    <input type="time" name="end_time" value="{{ old('end_time') }}" class="form-control">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="font-weight-semibold">{{ __('Academic Session') }}</label>
                                    <select name="academic_year_id" class="form-control">
                                        @foreach($academic_years as $ay)
                                            <option value="{{ $ay->id }}" {{ ($active_year && $active_year->id === $ay->id) ? 'selected' : '' }}>
                                                {{ $ay->name }} ({{ $ay->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="font-weight-semibold">{{ __('Target Audience') }} <span class="text-danger">*</span></label>
                                    <select name="target_audience" id="quick_target_audience" required class="form-control">
                                        <option value="all">{{ __('Entire School') }}</option>
                                        <option value="teachers">{{ __('Teachers Only') }}</option>
                                        <option value="students">{{ __('Students Only') }}</option>
                                        <option value="parents">{{ __('Parents Only') }}</option>
                                        <option value="classes">{{ __('Specific Class / Section') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row" id="quick_class_target_row" style="display: none;">
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-semibold">{{ __('Class') }}</label>
                                    <select name="my_class_id" id="quick_my_class_id" class="form-control">
                                        <option value="">{{ __('Select Class') }}</option>
                                        @foreach($my_classes as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-semibold">{{ __('Section (Optional)') }}</label>
                                    <select name="section_id" id="quick_section_id" class="form-control">
                                        <option value="">{{ __('All Sections') }}</option>
                                        @foreach($sections as $s)
                                            <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->my_class->name ?? '' }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-semibold">{{ __('Location / Venue') }}</label>
                                    <input type="text" name="location" value="{{ old('location') }}" class="form-control" placeholder="e.g. School Auditorium">
                                </div>
                                <div class="col-md-6 mb-3 d-flex align-items-center pt-3">
                                    <div class="custom-control custom-checkbox mr-4">
                                        <input type="checkbox" name="is_all_day" value="1" class="custom-control-input" id="quick_is_all_day" checked>
                                        <label class="custom-control-label" for="quick_is_all_day">{{ __('All-Day Event') }}</label>
                                    </div>
                                    <div class="custom-control custom-checkbox mr-4">
                                        <input type="checkbox" name="is_published" value="1" class="custom-control-input" id="quick_is_published" checked>
                                        <label class="custom-control-label font-weight-semibold text-success" for="quick_is_published">{{ __('Publish Immediately') }}</label>
                                    </div>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="is_closure" value="1" class="custom-control-input" id="quick_is_closure">
                                        <label class="custom-control-label text-danger" for="quick_is_closure">{{ __('School Closure') }}</label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-semibold">{{ __('Description') }}</label>
                                    <textarea name="description" rows="3" class="form-control" placeholder="{{ __('Details about the event...') }}">{{ old('description') }}</textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-semibold">{{ __('Instructions / Notes') }}</label>
                                    <textarea name="instructions" rows="3" class="form-control" placeholder="{{ __('Dress code, required materials, or special notes...') }}">{{ old('instructions') }}</textarea>
                                </div>
                            </div>

                            <div class="text-right">
                                <button type="submit" class="btn text-white px-4 font-weight-semibold" style="background: #FF5700; border-radius: 8px;">
                                    <i class="icon-checkmark mr-1"></i> {{ __('Save & Publish Event') }}
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Event Details Modal --}}
    <div class="modal fade" id="eventDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow-lg border-0" style="border-radius: 20px;">
                <div class="modal-header d-flex align-items-center justify-content-between" style="background: #111827; color: #FFFFFF; border-bottom: 1px solid rgba(255,255,255,0.08);">
                    <h6 class="modal-title font-weight-bold mb-0 text-white" id="eventModalTitle">{{ __('Event Details') }}</h6>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.8;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3 d-flex align-items-center">
                        <span class="badge" id="eventModalType" style="background: #FF5700; color: #fff;">EVENT</span>
                        <span class="text-muted font-size-sm ml-2 font-weight-medium" id="eventModalDate"></span>
                    </div>
                    <p class="text-dark mb-0" id="eventModalDesc" style="white-space: pre-wrap; font-size: 0.95rem; line-height: 1.6;"></p>
                </div>
                <div class="modal-footer bg-light p-3">
                    <a href="#" id="eventModalLink" class="btn btn-outline-primary btn-sm font-weight-semibold" style="display: none;">{{ __('View Full Details') }}</a>
                    <button type="button" class="btn btn-secondary btn-sm font-weight-semibold" data-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        if ($('#events-bs-calendar').length) {
            new NepaliBsCalendar('#events-bs-calendar', {
                feedUrl: '{{ route("calendar.events") }}',
                createEventUrl: '{{ route("events.create") }}',
                canCreateEvent: {{ $isAdmin ? 'true' : 'false' }},
                initialView: 'month'
            });
        }

        // Quick add target audience toggle
        $('#quick_target_audience').on('change', function() {
            if ($(this).val() === 'classes') {
                $('#quick_class_target_row').slideDown(150);
            } else {
                $('#quick_class_target_row').slideUp(150);
            }
        });
    });
</script>
@endsection
