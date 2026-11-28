<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ strtoupper($cert->certificate_type) }} CERTIFICATE - {{ $cert->certificate_number }}</title>
    <style>
        body { font-family: Georgia, serif; background: #f8fafc; margin: 0; padding: 24px; color: #0f172a; }
        .sheet { max-width: 820px; margin: 0 auto; background: #fff; border: 6px double #1e293b; padding: 40px 48px; }
        .header { text-align: center; border-bottom: 2px solid #cbd5e1; padding-bottom: 16px; margin-bottom: 28px; }
        .title { font-size: 24px; letter-spacing: 2px; text-transform: uppercase; margin: 16px 0 4px; color: #c2410c; }
        .body-text { font-size: 17px; line-height: 1.9; margin: 24px 0; }
        .meta { display: flex; justify-content: space-between; font-size: 13px; color: #475569; margin-bottom: 12px; }
        .sigs { display: flex; justify-content: space-between; margin-top: 64px; }
        .sig-line { border-top: 1px solid #334155; padding-top: 6px; min-width: 180px; text-align: center; font-size: 14px; }
        @media print { body { background: #fff; padding: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: right; max-width: 820px; margin: 0 auto 12px;">
        <button onclick="window.print()" style="padding: 8px 16px; cursor: pointer;">Print Certificate</button>
    </div>
    <div class="sheet">
        <div class="header">
            <h1 style="margin: 0; font-size: 26px;">{{ $school->name ?? Qs::getSystemName() }}</h1>
            <div>{{ $school->local_level_manual ?? Qs::getSetting('address') }}</div>
            <div class="title">{{ ucfirst($cert->certificate_type) }} Certificate</div>
        </div>
        <div class="meta">
            <span><strong>Certificate No:</strong> {{ $cert->certificate_number }}</span>
            <span><strong>Verification Code:</strong> {{ $cert->verification_code }}</span>
        </div>
        <p class="body-text">
            This is to certify that <strong>{{ $cert->studentRecord->user->name ?? '' }}</strong>
            (Admission No: <strong>{{ $cert->studentRecord->adm_no ?? '-' }}</strong>),
            @if($cert->studentRecord->father_name) son/daughter of <strong>{{ $cert->studentRecord->father_name }}</strong> @endif
            has been a bonafide student of <strong>{{ $cert->studentRecord->my_class->name ?? '-' }}</strong>
            {{ $cert->studentRecord->section ? '(' . $cert->studentRecord->section->name . ')' : '' }}
            during academic session <strong>{{ $cert->studentRecord->session ?? Qs::getCurrentSession() }}</strong>.
            To the best of our knowledge, their conduct and character have been commendable.
        </p>
        @if($cert->remarks)<p><strong>Remarks:</strong> {{ $cert->remarks }}</p>@endif
        <div class="sigs">
            <div class="sig-line">Issued Date: {{ $cert->issued_date_bs }} BS ({{ $cert->issued_date_ad ? $cert->issued_date_ad->format('Y-m-d') : '' }})</div>
            <div class="sig-line">Authorized Signature</div>
        </div>
    </div>
</body>
</html>
