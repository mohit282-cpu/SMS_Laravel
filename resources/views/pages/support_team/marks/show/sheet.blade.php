<table class="table table-bordered table-responsive text-center">
    <thead>
    <tr>
        <th>{{ __('SN') }}</th>
        <th>{{ __('Subject') }}</th>
        <th>{{ __('Credit Hour') }}</th>
        <th>{{ __('Theory') }}</th>
        <th>{{ __('Practical') }}</th>
        <th>{{ __('Total') }}</th>
        <th>{{ __('Grade Point') }}</th>
        <th>{{ __('Letter Grade') }}</th>
        <th>{{ __('Remarks') }}</th>
    </tr>
    </thead>

    <tbody>
    @foreach($subjects as $sub)
        @php
            $mk = $marks->where('subject_id', $sub->id)->where('exam_id', $ex->id)->first();
        @endphp
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td class="text-left font-weight-semibold">{{ $sub->name }}</td>
            <td>{{ number_format($sub->credit_hour ?: 4.0, 2) }}</td>
            <td>
                @if($mk)
                    @if($mk->is_absent) <span class="badge badge-danger">ABS</span>
                    @elseif($mk->is_exempt) <span class="badge badge-warning">EXM</span>
                    @else {{ $mk->theory_mark !== null ? $mk->theory_mark : ($mk->exm ?? '-') }}
                    @endif
                @else -
                @endif
            </td>
            <td>
                @if($sub->has_practical)
                    @if($mk)
                        @if($mk->is_absent) <span class="badge badge-danger">ABS</span>
                        @elseif($mk->is_exempt) <span class="badge badge-warning">EXM</span>
                        @else {{ $mk->practical_mark !== null ? $mk->practical_mark : ($mk->t1 ?? '-') }}
                        @endif
                    @else -
                    @endif
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td class="font-weight-bold">
                @if($mk)
                    @if($mk->is_absent) <span class="badge badge-danger">ABS</span>
                    @elseif($mk->is_exempt) <span class="badge badge-warning">EXM</span>
                    @else {{ $mk->total_mark !== null ? $mk->total_mark : ($mk->{'tex'.$ex->term} ?? '-') }}
                    @endif
                @else -
                @endif
            </td>
            <td class="font-weight-bold" style="color: purple;">
                {{ $mk && $mk->gpa_point !== null ? number_format($mk->gpa_point, 2) : '-' }}
            </td>
            <td>
                @if($mk && $mk->grade_name)
                    <span class="badge {{ in_array($mk->grade_name, ['A+', 'A', 'B+']) ? 'badge-success' : (in_array($mk->grade_name, ['B', 'C+', 'C', 'D']) ? 'badge-info' : 'badge-danger') }}">
                        {{ $mk->grade_name }}
                    </span>
                @elseif($mk && $mk->grade)
                    <span class="badge badge-secondary">{{ $mk->grade->name }}</span>
                @else -
                @endif
            </td>
            <td>{{ $mk->remarks ?? ($mk && $mk->grade ? $mk->grade->remark : '-') }}</td>
        </tr>
    @endforeach
    <tr class="font-weight-bold" style="background-color: #f8f9fa;">
        <td colspan="3" class="text-left"><strong>{{ __('Total Marks') }}:</strong> {{ $exr->total ?: '-' }}</td>
        <td colspan="2"><strong>{{ __('Percentage') }}:</strong> {{ $exr->ave ? number_format($exr->ave, 2).'%' : '-' }}</td>
        <td colspan="2" style="color: purple;"><strong>{{ __('GPA') }}:</strong> {{ $exr->gpa !== null ? number_format($exr->gpa, 2) : '-' }}</td>
        <td><strong>{{ __('Grade') }}:</strong> {{ $exr->grade ?: '-' }}</td>
        <td style="color: {{ ($exr->result_status ?? '') === 'PASS' ? '#28a745' : (($exr->result_status ?? '') === 'FAIL' ? '#dc3545' : '#6c757d') }}">
            <strong>{{ __('Result') }}:</strong> {{ $exr->result_status ?: '-' }}
        </td>
    </tr>
    </tbody>
</table>
