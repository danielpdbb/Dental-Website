<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1e293b; font-size: 12px; }
        .header { border-bottom: 2px solid #3B82F6; padding-bottom: 12px; margin-bottom: 18px; }
        .clinic { font-size: 20px; font-weight: bold; color: #1E3A5F; }
        .tag { color: #10B981; font-size: 11px; }
        .muted { color: #64748b; }
        h2 { font-size: 14px; margin: 18px 0 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { text-align: left; background: #f1f5f9; color: #475569; font-size: 10px; text-transform: uppercase; padding: 6px 8px; }
        td { padding: 7px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .meta td { border: none; padding: 2px 0; }
        .status { font-size: 10px; padding: 2px 6px; border-radius: 10px; background: #f1f5f9; }
        .footer { margin-top: 40px; font-size: 10px; color: #94a3b8; }
        .sign { margin-top: 50px; }
        .sign .line { border-top: 1px solid #94a3b8; width: 220px; padding-top: 4px; font-size: 11px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="clinic">Bonoan's Dental Clinic</div>
        <div class="tag">Your smile. Our passion. Our pride. — Bonoan, Dagupan City</div>
    </div>

    <table class="meta">
        <tr><td class="muted" style="width:120px;">Patient</td><td><strong>{{ $patient->fullName() }}</strong></td></tr>
        <tr><td class="muted">Date of birth</td><td>{{ $patient->date_of_birth?->format('M j, Y') ?? '—' }}</td></tr>
        <tr><td class="muted">Issued</td><td>{{ $issuedAt->format('M j, Y g:i A') }}</td></tr>
    </table>

    <h2>Recommended Dental Procedures</h2>

    @if ($recommendations->isEmpty())
        <p class="muted">No procedure recommendations on file.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Recommended procedure</th>
                    <th>Service</th>
                    <th>Dentist</th>
                    <th>Status</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recommendations as $rec)
                    <tr>
                        <td><strong>{{ $rec->recommendation }}</strong></td>
                        <td>{{ $rec->service?->name ?? '—' }}</td>
                        <td>{{ $rec->dentist?->name ?? '—' }}</td>
                        <td><span class="status">{{ $rec->status->label() }}</span></td>
                        <td>{{ $rec->notes ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="sign">
        <div class="line">Dentist's signature</div>
    </div>

    <div class="footer">
        This document lists procedures recommended for the patient's consideration. It is not a bill.
        Please consult the clinic to schedule any procedure.
    </div>
</body>
</html>
