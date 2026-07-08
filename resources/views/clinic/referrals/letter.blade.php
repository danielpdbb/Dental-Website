<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #0f172a; font-size: 13px; line-height: 1.55; }
        .head { border-bottom: 2px solid #2563eb; padding-bottom: 10px; margin-bottom: 20px; }
        .clinic { color: #2563eb; font-size: 20px; font-weight: bold; }
        .muted { color: #64748b; }
        .ref-no { text-align: right; color: #64748b; font-size: 12px; }
        .block { margin-top: 16px; }
        .sig { margin-top: 46px; }
        .sig .line { border-top: 1px solid #0f172a; width: 240px; padding-top: 4px; }
        .foot { margin-top: 34px; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 8px; }
    </style>
</head>
<body>
    @php
        $p = $referral->patient;
        $age = $p?->date_of_birth ? (int) $p->date_of_birth->age : null;
    @endphp

    <div class="head">
        <div class="clinic">Bonoan&rsquo;s Dental Clinic</div>
        <div class="muted">Bonoan, Dagupan City, Pangasinan · Philippines</div>
        <div class="ref-no">Referral No.: <strong>{{ $referral->letter_no }}</strong><br>{{ $referral->letter_issued_at?->format('F j, Y') }}</div>
    </div>

    <div class="block">
        <strong>{{ $referral->referred_to_name }}</strong><br>
        @if ($referral->referred_to_clinic){{ $referral->referred_to_clinic }}<br>@endif
        @if ($referral->referred_to_address){{ $referral->referred_to_address }}@endif
    </div>

    <div class="block">
        <strong>RE: Referral of {{ $p?->fullName() }}</strong>
        ({{ $age !== null ? $age.' years old' : 'age on file' }}{{ $p?->gender ? ', '.$p->gender : '' }})
    </div>

    <div class="block">Dear {{ \Illuminate\Support\Str::startsWith($referral->referred_to_name, ['Dr', 'dr']) ? $referral->referred_to_name : 'Dr. '.$referral->referred_to_name }},</div>

    <div class="block">
        I am respectfully referring our patient, <strong>{{ $p?->fullName() }}</strong>, for further evaluation and
        management{{ $referral->service ? ' in relation to '.$referral->service->name : '' }}.
    </div>

    <div class="block">
        <strong>Reason for referral:</strong><br>
        {{ $referral->reason }}
    </div>

    @if ($referral->letter_notes)
        <div class="block">
            <strong>Clinical notes / relevant history:</strong><br>
            {{ $referral->letter_notes }}
        </div>
    @endif

    @if ($p?->allergies?->isNotEmpty())
        <div class="block">
            <strong>Known allergies:</strong>
            {{ $p->allergies->map(fn ($a) => $a->name.' ('.$a->severity->label().')')->join(', ') }}
        </div>
    @endif

    <div class="block">
        Kindly extend to the patient the courtesy of your expertise. Should you need any additional records or
        radiographs, please do not hesitate to contact our clinic. We would appreciate a note on your findings
        and management for the continuity of the patient&rsquo;s care.
    </div>

    <div class="block">Thank you very much, and warm regards.</div>

    <div class="sig">
        <div class="line">
            <strong>{{ $referral->letterIssuer?->name ?? 'Attending Dentist' }}</strong><br>
            <span class="muted">Bonoan&rsquo;s Dental Clinic</span>
        </div>
    </div>

    <div class="foot">
        This referral letter was issued by Bonoan&rsquo;s Dental Clinic ({{ $referral->letter_no }}) on
        {{ $referral->letter_issued_at?->format('M j, Y g:i A') }}. Handled in accordance with the Data Privacy Act of 2012 (RA 10173).
    </div>
</body>
</html>
