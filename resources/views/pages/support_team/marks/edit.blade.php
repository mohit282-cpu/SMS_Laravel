<form class="ajax-update" action="{{ route('marks.update', [$exam_id, $my_class_id, $section_id, $subject_id]) }}" method="post">
    @csrf @method('put')
    <div class="table-responsive">
        <table class="table table-striped table-bordered text-center">
            <thead>
            <tr class="bg-light">
                <th style="width: 5%;">#</th>
                <th style="text-align: left; width: 22%;">{{ __('Student Name') }}</th>
                <th style="width: 15%;">{{ __('Roll / Adm No') }}</th>
                <th>{{ __('Theory') }} (Max {{ $m->subject->theory_max ?? 75 }})</th>
                @if($m->subject->has_practical ?? true)
                    <th>{{ __('Practical') }} (Max {{ $m->subject->practical_max ?? 25 }})</th>
                @endif
                <th>{{ __('Total') }}</th>
                <th>{{ __('Grade / GPA') }}</th>
                <th>{{ __('Absent') }}</th>
                <th>{{ __('Exempt') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($marks->sortBy('user.name') as $mk)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td style="text-align: left;">
                        <span class="font-weight-semibold">{{ $mk->user->name }}</span>
                    </td>
                    <td>{{ $mk->user->student_record->adm_no ?? '-' }}</td>

                    {{-- Theory Marks --}}
                    <td>
                        <input title="{{ __('Theory Marks') }}"
                               min="0"
                               max="{{ $m->subject->theory_max ?? 100 }}"
                               step="0.5"
                               class="form-control text-center theory-input"
                               name="theory_{{ $mk->id }}"
                               value="{{ $mk->theory_mark ?? $mk->exm }}"
                               type="number"
                               style="max-width: 110px; margin: 0 auto;">
                    </td>

                    {{-- Practical Marks --}}
                    @if($m->subject->has_practical ?? true)
                        <td>
                            <input title="{{ __('Practical Marks') }}"
                                   min="0"
                                   max="{{ $m->subject->practical_max ?? 25 }}"
                                   step="0.5"
                                   class="form-control text-center practical-input"
                                   name="practical_{{ $mk->id }}"
                                   value="{{ $mk->practical_mark ?? $mk->t1 }}"
                                   type="number"
                                   style="max-width: 110px; margin: 0 auto;">
                        </td>
                    @endif

                    {{-- Total Display --}}
                    <td>
                        <span class="font-weight-bold total-display text-primary">
                            {{ $mk->is_absent ? 'ABS' : ($mk->is_exempt ? 'EXM' : ($mk->total_mark ?? ($mk->tca + $mk->exm))) }}
                        </span>
                    </td>

                    {{-- Grade & GPA --}}
                    <td>
                        <span class="badge {{ ($mk->grade_name === 'NG' || $mk->is_absent) ? 'badge-danger' : 'badge-success' }} p-1 font-weight-bold">
                            {{ $mk->grade_name ?? ($mk->grade ? $mk->grade->name : '-') }}
                            @if($mk->gpa_point !== null)
                                ({{ number_format($mk->gpa_point, 2) }})
                            @endif
                        </span>
                    </td>

                    {{-- Absent Checkbox --}}
                    <td>
                        <div class="form-check">
                            <label class="form-check-label">
                                <input type="checkbox" name="absent_{{ $mk->id }}" value="1" {{ $mk->is_absent ? 'checked' : '' }} class="form-check-input status-absent">
                            </label>
                        </div>
                    </td>

                    {{-- Exempt Checkbox --}}
                    <td>
                        <div class="form-check">
                            <label class="form-check-label">
                                <input type="checkbox" name="exempt_{{ $mk->id }}" value="1" {{ $mk->is_exempt ? 'checked' : '' }} class="form-check-input status-exempt">
                            </label>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="text-right mt-3">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="icon-floppy-disk mr-2"></i> {{ __('Save & Compute Nepal Grades') }}
        </button>
    </div>
</form>
