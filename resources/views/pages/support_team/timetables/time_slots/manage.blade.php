<div class="card">
    <div class="card-header header-elements-inline">
        <h2 class="font-weight-bold card-title mb-0" style="font-size: 1.1rem;"><i class="icon-alarm mr-2" style="color: #FF5700;"></i>{{ __('Manage Time Slots') }} &bull; {{ $ttr->name }}</h2>
        {!! Qs::getPanelOptions() !!}
    </div>

    <div class="card-body collapse">
        <table id="time_slots_table" class="table datatable-button-html5-columns">
            <thead>
            <tr>
                <th>S/N</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @foreach($time_slots as $tms)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $tms->time_from }}</td>
                    <td>{{ $tms->time_to}}</td>
                    <td class="text-center">
                        <div class="list-icons">
                            <div class="dropdown">
                                <a href="#" class="list-icons-item" data-toggle="dropdown">
                                    <i class="icon-menu9"></i>
                                </a>

                                <div class="dropdown-menu dropdown-menu-right">
                                    {{--Edit--}}
                                    <a href="{{ route('ts.edit', $tms->id) }}" class="dropdown-item"><i class="icon-pencil"></i> Edit</a>

                                    {{--Delete--}}
                                    @if(Qs::userIsSuperAdmin())
                                        <a id="{{ $tms->id }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item"><i class="icon-trash"></i> Delete</a>
                                        <form method="post" id="item-delete-{{ $tms->id }}" action="{{ route('ts.destroy', $tms->id) }}" class="hidden">@csrf @method('delete')</form>
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
