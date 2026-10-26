{{--<!--NAME , CLASS AND OTHER INFO -->--}}
<table style="width:100%; border-collapse:collapse; margin-bottom: 10px; font-size: 14px;">
    <tbody>
    <tr>
        <td><strong>{{ __('NAME') }}:</strong> {{ strtoupper($sr->user->name) }}</td>
        <td><strong>{{ __('ADM / ROLL NO') }}:</strong> {{ $sr->adm_no }}</td>
        <td><strong>{{ __('CLASS') }}:</strong> {{ strtoupper($my_class->name) }} {{ $sr->section->name ?? '' }}</td>
    </tr>
    <tr>
        <td><strong>{{ __('EXAMINATION') }}:</strong> {{ strtoupper($ex->name) }}</td>
        <td><strong>{{ __('ACADEMIC YEAR') }}:</strong> {{ $ex->year }}</td>
        <td><strong>{{ __('DATE OF BIRTH') }}:</strong> {{ App\Helpers\NepaliHelper::formatDualDate($sr->user->dob) ?: '-' }}</td>
    </tr>
    @if($ex->start_date_bs)
    <tr>
        <td colspan="3"><strong>{{ __('EXAM PERIOD (BS)') }}:</strong> {{ $ex->start_date_bs }} @if($ex->end_date_bs && $ex->end_date_bs !== $ex->start_date_bs) - {{ $ex->end_date_bs }} @endif BS</td>
    </tr>
    @endif
    </tbody>
</table>

{{--Nepal Standard Grade Sheet Table--}}
<table style="width:100%; border-collapse:collapse; border: 1px solid #333; margin: 10px auto; font-size: 13px;" border="1" cellpadding="5">
    <thead>
    <tr style="background-color: #f2f2f2; text-align: center;">
        <th rowspan="2" style="text-align: left; width: 25%;">{{ __('SUBJECTS') }}</th>
        <th rowspan="2" style="width: 8%;">{{ __('CREDIT HOUR') }}</th>
        <th colspan="2">{{ __('OBTAINED MARKS') }}</th>
        <th rowspan="2" style="width: 10%;">{{ __('FINAL MARKS') }}</th>
        <th rowspan="2" style="width: 10%;">{{ __('GRADE POINT') }}</th>
        <th rowspan="2" style="width: 10%;">{{ __('LETTER GRADE') }}</th>
        <th rowspan="2" style="width: 15%;">{{ __('REMARKS') }}</th>
    </tr>
    <tr style="background-color: #f8f9fa; text-align: center;">
        <th style="width: 11%;">TH</th>
        <th style="width: 11%;">PR</th>
    </tr>
    </thead>
    <tbody>
    @php
        $totalCredits = 0;
        $totalWeightedGpa = 0;
    @endphp
    @foreach($subjects as $sub)
        @php
            $mk = $marks->where('subject_id', $sub->id)->where('exam_id', $ex->id)->first();
            $cr = $sub->credit_hour ?: 4.0;
            $gpaPoint = $mk ? ($mk->gpa_point ?? 0.0) : 0.0;
            $totalCredits += $cr;
            $totalWeightedGpa += ($gpaPoint * $cr);
        @endphp
        <tr>
            <td style="font-weight: bold; text-align: left;">{{ $sub->name }}</td>
            <td style="text-align: center;">{{ number_format($cr, 2) }}</td>
            <td style="text-align: center;">
                @if($mk)
                    {{ $mk->is_absent ? 'ABS' : ($mk->is_exempt ? 'EXM' : ($mk->theory_mark ?? $mk->exm ?? '-')) }}
                @else
                    -
                @endif
            </td>
            <td style="text-align: center;">
                @if($mk)
                    {{ $mk->is_absent ? 'ABS' : ($mk->is_exempt ? 'EXM' : ($mk->practical_mark ?? $mk->t1 ?? '-')) }}
                @else
                    -
                @endif
            </td>
            <td style="text-align: center; font-weight: bold;">
                @if($mk)
                    {{ $mk->is_absent ? 'ABS' : ($mk->is_exempt ? 'EXM' : ($mk->total_mark ?? $mk->$tex ?? '-')) }}
                @else
                    -
                @endif
            </td>
            <td style="text-align: center; font-weight: bold;">
                {{ $mk && $mk->gpa_point !== null ? number_format($mk->gpa_point, 2) : '-' }}
            </td>
            <td style="text-align: center; font-weight: bold;">
                {{ $mk ? ($mk->grade_name ?? ($mk->grade ? $mk->grade->name : '-')) : '-' }}
            </td>
            <td style="text-align: center;">
                {{ $mk ? ($mk->remarks ?? ($mk->grade ? $mk->grade->remark : '-')) : '-' }}
            </td>
        </tr>
    @endforeach
    <tr style="background-color: #f2f2f2; font-weight: bold;">
        <td colspan="4" style="text-align: right; padding-right: 10px;">{{ __('GRADE POINT AVERAGE (GPA)') }}:</td>
        <td colspan="2" style="text-align: center; font-size: 16px; color: #1b0c80;">
            {{ $exr->gpa ? number_format($exr->gpa, 2) : ($totalCredits > 0 ? number_format(round($totalWeightedGpa / $totalCredits, 2), 2) : '-') }}
        </td>
        <td colspan="2" style="text-align: center; font-size: 16px; color: {{ ($exr->result_status === 'FAIL') ? '#d9534f' : '#28a745' }};">
            {{ $exr->grade ?: '-' }} ({{ $exr->result_status ?: 'PASS' }})
        </td>
    </tr>
    </tbody>
</table>

{{--Key to Grading Summary Table (Nepal Model)--}}
<div style="margin-top: 15px; margin-bottom: 10px;">
    <strong>{{ __('GRADING SYSTEM (NEPAL STANDARD)') }}:</strong>
    <table style="width:100%; border-collapse:collapse; border: 1px solid #777; font-size: 11px; margin-top: 4px; text-align: center;" border="1" cellpadding="3">
        <tr style="background-color: #eee;">
            <th>{{ __('Interval (%)') }}</th>
            <th>90 - 100</th>
            <th>80 - 89.9</th>
            <th>70 - 79.9</th>
            <th>60 - 69.9</th>
            <th>50 - 59.9</th>
            <th>40 - 49.9</th>
            <th>35 - 39.9</th>
            <th>Below 35</th>
        </tr>
        <tr>
            <th>{{ __('Grade') }}</th>
            <td>A+</td><td>A</td><td>B+</td><td>B</td><td>C+</td><td>C</td><td>D</td><td>NG</td>
        </tr>
        <tr>
            <th>{{ __('Grade Point') }}</th>
            <td>4.0</td><td>3.6</td><td>3.2</td><td>2.8</td><td>2.4</td><td>2.0</td><td>1.6</td><td>0.0</td>
        </tr>
        <tr>
            <th>{{ __('Description') }}</th>
            <td>Outstanding</td><td>Excellent</td><td>Very Good</td><td>Good</td><td>Satisfactory</td><td>Acceptable</td><td>Basic</td><td>Non-Graded</td>
        </tr>
    </table>
</div>
