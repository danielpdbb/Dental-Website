@php
    $patient = $rec->appointment->patient;
    $dentist = $rec->appointment->dentist;
@endphp
<div style="font-family: Arial, Helvetica, sans-serif; color:#0f172a; max-width:560px; margin:0 auto;">
    <h2 style="color:#2563eb;">Bonoan&rsquo;s Dental Clinic</h2>
    <p>Hi {{ $patient?->first_name ?? 'there' }},</p>

    <p>{{ $dentist?->name ?? 'Your dentist' }} has shared a treatment recommendation with you:</p>

    <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; background:#f8fafc;">
        <p style="margin:0 0 6px; font-size:13px; color:#64748b;">{{ $rec->source->label() }}</p>
        <p style="margin:0; font-size:16px; font-weight:bold;">{{ $rec->recommendation }}</p>
        @if ($rec->priority)
            <p style="margin:8px 0 0; font-size:13px;">Priority: <strong>{{ $rec->priority->label() }}</strong></p>
        @endif
        @if ($rec->follow_up_weeks)
            <p style="margin:4px 0 0; font-size:13px;">Suggested follow-up: in about {{ $rec->follow_up_weeks }} week(s).</p>
        @endif
        @if ($rec->suggested_at)
            <p style="margin:4px 0 0; font-size:13px;">Proposed schedule: <strong>{{ $rec->suggested_at->format('l, M j, Y · g:i A') }}</strong></p>
        @endif
    </div>

    <p style="margin-top:16px;">
        You can view and print this recommendation anytime from your
        <a href="{{ route('dashboard') }}" style="color:#2563eb;">patient dashboard</a>.
    </p>

    <p style="font-size:12px; color:#94a3b8; margin-top:24px;">
        This is a decision-support suggestion from your dental care team and is not a final diagnosis.
    </p>
</div>
