<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $request->ref_number }} — STRG Request</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #1a1a1a; padding: 20px; }

        .header { text-align: center; border-bottom: 3px solid #1F3864; padding-bottom: 12px; margin-bottom: 16px; }
        .header .logo-text { font-size: 18px; font-weight: bold; color: #1F3864; letter-spacing: 1px; }
        .header .sub { font-size: 10px; color: #555; margin-top: 3px; }
        .header h1 { font-size: 14px; margin-top: 8px; color: #1F3864; font-weight: bold; }

        .ref-badge { display: inline-block; background: #1F3864; color: white; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; margin: 8px 0; }

        .section { margin-bottom: 14px; }
        .section-title { background: #1F3864; color: white; padding: 5px 10px; font-weight: bold; font-size: 11px; margin-bottom: 6px; }

        table.info-table { width: 100%; border-collapse: collapse; }
        table.info-table td { padding: 5px 8px; border: 1px solid #ccc; vertical-align: top; }
        table.info-table td.label { background: #EEF2FF; font-weight: bold; width: 28%; white-space: nowrap; }
        table.info-table td.value { width: 22%; }

        table.vot-table { width: 100%; border-collapse: collapse; }
        table.vot-table th { background: #1F3864; color: white; padding: 6px 8px; text-align: left; font-size: 10px; }
        table.vot-table td { padding: 5px 8px; border: 1px solid #ccc; }
        table.vot-table tr:nth-child(even) td { background: #F0F4FF; }
        table.vot-table td.amount { text-align: right; }
        table.vot-table tfoot td { font-weight: bold; background: #DCE6F1; }

        .description-box { border: 1px solid #ccc; padding: 8px; min-height: 50px; background: #FAFAFA; }

        .signature-section { margin-top: 16px; border-top: 2px solid #1F3864; padding-top: 12px; }
        .signature-grid { display: table; width: 100%; }
        .sig-col { display: table-cell; width: 50%; padding: 0 10px; vertical-align: top; }
        .sig-box { border: 1px solid #999; min-height: 70px; background: #FAFAFA; text-align: center; padding: 4px; }
        .sig-img { max-width: 100%; max-height: 65px; }
        .sig-label { font-size: 9px; color: #666; margin-top: 4px; border-top: 1px solid #999; padding-top: 3px; }

        .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #888; border-top: 1px solid #ccc; padding-top: 8px; }
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 3px; font-size: 10px; font-weight: bold; }
        .status-pending { background: #FEF3C7; color: #92400E; }
        .status-approved { background: #D1FAE5; color: #065F46; }
        .status-declined { background: #FEE2E2; color: #991B1B; }
        
        .template-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.15;
            pointer-events: none;
        }
    </style>
</head>
<body>
    @if(isset($background_image))
        <img src="{{ $background_image }}" class="template-background" alt="Template Background">
    @endif
    
    <!-- Header -->
    <div class="header">
        <div class="logo-text">UNIVERSITI KUALA LUMPUR (UniKL)</div>
        <div class="sub">Student Travel Research Grant (STRG) System</div>
        <h1>STRG REQUEST FORM</h1>
        <div class="ref-badge">{{ $request->ref_number }}</div>
        <div style="font-size:10px; color:#555; margin-top:4px;">
            Submitted: {{ $request->submitted_at?->format('d F Y, H:i:s') ?? $request->created_at->format('d F Y, H:i:s') }}
            &nbsp;|&nbsp;
            Status: <strong>{{ $request->statusLabel() }}</strong>
        </div>
    </div>

    <!-- Submitter Information -->
    <div class="section">
        <div class="section-title">APPLICANT INFORMATION</div>
        <table class="info-table">
            <tr>
                <td class="label">Full Name</td>
                <td class="value">{{ $request->user->name }}</td>
                <td class="label">Staff ID</td>
                <td class="value">{{ $request->submitter_staff_id ?? $request->user->staff_id ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Email</td>
                <td class="value">{{ $request->user->email }}</td>
                <td class="label">Phone</td>
                <td class="value">{{ $request->submitter_phone ?? $request->user->phone ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Designation</td>
                <td class="value">{{ $request->submitter_designation ?? $request->user->designation ?? '—' }}</td>
                <td class="label">Employee Level</td>
                <td class="value">{{ $request->submitter_employee_level ?? $request->user->employee_level ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Department</td>
                <td class="value" colspan="3">{{ $request->submitter_department ?? $request->user->department ?? '—' }}</td>
            </tr>
        </table>
    </div>

    <!-- Request Details -->
    <div class="section">
        <div class="section-title">REQUEST DETAILS</div>
        <table class="info-table">
            <tr>
                <td class="label">Request Type</td>
                <td class="value">{{ $request->requestType->name }}</td>
                <td class="label">Priority</td>
                <td class="value">{{ $request->priorityLabel() }}</td>
            </tr>
            <tr>
                <td class="label">Deadline</td>
                <td class="value">{{ $request->deadline?->format('d F Y') ?? 'None specified' }}</td>
                <td class="label">Revision No.</td>
                <td class="value">{{ $request->revision_count > 0 ? '#' . $request->revision_count : 'Original' }}</td>
            </tr>
        </table>
    </div>

    <!-- Description / Justification -->
    <div class="section">
        <div class="section-title">JUSTIFICATION / DESCRIPTION</div>
        <div class="description-box">{{ $request->payload['description'] ?? 'No description provided.' }}</div>
    </div>

    <!-- VOT Items -->
    <div class="section">
        <div class="section-title">BUDGET / VOT BREAKDOWN</div>
        @php $votItems = $request->getVotItems(); @endphp
        @if(!empty($votItems))
            <table class="vot-table">
                <thead>
                    <tr>
                        <th style="width:15%">VOT Code</th>
                        <th style="width:55%">Description</th>
                        <th style="width:30%; text-align:right">Amount (RM)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($votItems as $item)
                        <tr>
                            <td>{{ $item['vot_code'] ?? '—' }}</td>
                            <td>{{ $item['description'] ?? '—' }}</td>
                            <td class="amount">{{ number_format((float)($item['amount'] ?? 0), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" style="text-align:right; padding-right:12px;">TOTAL</td>
                        <td class="amount">RM {{ number_format((float)$request->total_amount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        @else
            <p style="padding:8px; color:#888; font-style:italic;">No VOT items recorded.</p>
        @endif
    </div>

    <!-- Verification Trail (if available) -->
    @if($request->verifiedBy || $request->recommendedBy)
    <div class="section">
        <div class="section-title">VERIFICATION TRAIL</div>
        <table class="info-table">
            <tr>
                <td class="label">Verified By (Staff 1)</td>
                <td class="value">{{ $request->verifiedBy?->name ?? 'Pending' }}</td>
                <td class="label">Recommended By (Staff 2)</td>
                <td class="value">{{ $request->recommendedBy?->name ?? 'Pending' }}</td>
            </tr>
        </table>
    </div>
    @endif

    <!-- Rejection reason if applicable -->
    @if($request->rejection_reason)
    <div class="section">
        <div class="section-title" style="background:#991B1B;">RETURN / REJECTION REASON</div>
        <div class="description-box" style="border-color:#FCA5A5; background:#FFF5F5;">{{ $request->rejection_reason }}</div>
    </div>
    @endif

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="section-title">DECLARATION &amp; SIGNATURES</div>
        <p style="font-size:10px; color:#555; margin: 8px 0;">
            I hereby declare that the information provided in this form is true and accurate to the best of my knowledge.
        </p>

        <div class="signature-grid">
            <div class="sig-col">
                <strong style="font-size:10px; display:block; margin-bottom:4px;">APPLICANT SIGNATURE</strong>
                <div class="sig-box">
                    @if($request->signature_data)
                        <img src="{{ $request->signature_data }}" class="sig-img" alt="Applicant Signature"/>
                    @else
                        <span style="color:#aaa; font-size:9px; line-height:65px;">No signature on file</span>
                    @endif
                </div>
                <div class="sig-label">
                    {{ $request->user->name }}<br>
                    {{ $request->submitter_designation ?? $request->user->designation ?? '' }}<br>
                    Signed: {{ $request->signed_at?->format('d/m/Y H:i') ?? '—' }}
                </div>
            </div>

            <div class="sig-col">
                <strong style="font-size:10px; display:block; margin-bottom:4px;">AUTHORISED OFFICER</strong>
                <div class="sig-box" style="min-height:70px;">
                    <span style="color:#aaa; font-size:9px; line-height:65px;">For official use</span>
                </div>
                <div class="sig-label">
                    Name: ____________________________<br>
                    Designation: ____________________________<br>
                    Date: ____________________________
                </div>
            </div>
        </div>

        <!-- Staff 2 and Dean Signatures -->
        <div class="signature-grid" style="margin-top: 20px;">
            <div class="sig-col">
                <strong style="font-size:10px; display:block; margin-bottom:4px;">STAFF 2 SIGNATURE</strong>
                <div class="sig-box">
                    @if($request->staff2_signature_data)
                        <img src="{{ $request->staff2_signature_data }}" class="sig-img" alt="Staff 2 Signature"/>
                    @else
                        <span style="color:#aaa; font-size:9px; line-height:65px;">Pending signature</span>
                    @endif
                </div>
                <div class="sig-label">
                    @if($request->recommendedBy)
                        {{ $request->recommendedBy->name }}<br>
                        {{ $request->recommendedBy->designation ?? '' }}<br>
                        Signed: {{ $request->staff2_signed_at?->format('d/m/Y H:i') ?? '—' }}
                    @else
                        Pending Staff 2 Review<br>
                        Designation: —<br>
                        Signed: —
                    @endif
                </div>
            </div>

            <div class="sig-col">
                <strong style="font-size:10px; display:block; margin-bottom:4px;">DEAN SIGNATURE</strong>
                <div class="sig-box">
                    @if($request->dean_signature_data)
                        <img src="{{ $request->dean_signature_data }}" class="sig-img" alt="Dean Signature"/>
                    @else
                        <span style="color:#aaa; font-size:9px; line-height:65px;">Pending signature</span>
                    @endif
                </div>
                <div class="sig-label">
                    @if($request->dean_approved_by)
                        {{ $request->deanApprovedBy->name }}<br>
                        Dean<br>
                        Signed: {{ $request->dean_signed_at?->format('d/m/Y H:i') ?? '—' }}
                    @else
                        Pending Dean Review<br>
                        Designation: Dean<br>
                        Signed: —
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        Generated by UniKL STRG Portal &nbsp;|&nbsp; {{ now()->format('d F Y, H:i:s') }}
        &nbsp;|&nbsp; Reference: {{ $request->ref_number }}
        &nbsp;|&nbsp; This is a system-generated document.
    </div>
</body>
</html>
