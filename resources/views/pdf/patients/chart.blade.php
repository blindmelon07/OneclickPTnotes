<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Full chart — {{ $patient->name }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin-bottom: 0; }
        h2 { font-size: 15px; margin-top: 0; }
        .subtitle { color: #555; margin-top: 2px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.meta td { padding: 4px 6px; border: 1px solid #ddd; vertical-align: top; }
        table.meta td.label { width: 160px; font-weight: bold; background: #f5f5f5; }
        .note { page-break-before: always; padding-top: 10px; }
        .note:first-of-type { page-break-before: auto; }
        .field { margin-bottom: 12px; }
        .field .label { font-weight: bold; margin-bottom: 2px; }
        .field .value { white-space: pre-wrap; }
        .signature-block { margin-top: 20px; border-top: 1px solid #999; padding-top: 10px; }
        .signature-block img { height: 50px; }
        .signature-meta { color: #555; margin-top: 4px; }
        .addendum { margin-top: 10px; border: 1px solid #ddd; padding: 8px; background: #fafafa; }
        .addendum .meta { color: #555; font-size: 11px; margin-top: 4px; }
        .attachments-table th, .attachments-table td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }}</h1>
    <p class="subtitle">Full chart export — generated {{ now()->format('M j, Y g:i A') }}</p>

    <table class="meta">
        <tr>
            <td class="label">Patient</td>
            <td>{{ $patient->name }}</td>
            <td class="label">Diagnosis</td>
            <td>{{ $patient->diagnosis }}</td>
        </tr>
        <tr>
            <td class="label">Doctor</td>
            <td>{{ $patient->doctor?->name }}</td>
            <td class="label">HHA</td>
            <td>{{ $patient->homeHealthAgency?->name }}</td>
        </tr>
        <tr>
            <td class="label">Insurance</td>
            <td>{{ $patient->insuranceCompany?->name }}</td>
            <td class="label">Cert period</td>
            <td>{{ $patient->cert_period }}</td>
        </tr>
    </table>

    @foreach ($patient->notes as $note)
        <div class="note">
            <h2>{{ $note->label() }} — {{ optional($note->signed_at)->format('M j, Y') }}</h2>

            @if (in_array($note->type, \App\Models\Note::wizardTypes(), true))
                @foreach ($note->data ?? [] as $key => $value)
                    <div class="field">
                        <div class="label">{{ \Illuminate\Support\Str::headline($key) }}</div>
                        <div class="value">
                            @if (is_array($value))
                                {{ collect($value)->flatten()->filter()->implode(', ') ?: '—' }}
                            @else
                                {{ filled($value) ? $value : '—' }}
                            @endif
                        </div>
                    </div>
                @endforeach
            @else
                @foreach (\App\Models\Note::dataFieldLabels() as $key => $label)
                    <div class="field">
                        <div class="label">{{ $label }}</div>
                        <div class="value">{{ $note->data[$key] ?? '—' }}</div>
                    </div>
                @endforeach
            @endif

            <div class="signature-block">
                @if ($note->signature_path && $signatureDataUri($note->signature_path))
                    <img src="{{ $signatureDataUri($note->signature_path) }}" alt="Staff signature">
                @endif
                <div class="signature-meta">
                    Signed by {{ $note->author?->name }} (staff) on {{ optional($note->signed_at)->format('M j, Y g:i A') }}
                </div>
            </div>

            @if ($note->patient_signature_path)
                <div class="signature-block">
                    @if ($signatureDataUri($note->patient_signature_path))
                        <img src="{{ $signatureDataUri($note->patient_signature_path) }}" alt="Patient signature">
                    @endif
                    <div class="signature-meta">
                        Signed by {{ $patient->name }} (patient) on {{ optional($note->patient_signed_at)->format('M j, Y g:i A') }}
                    </div>
                </div>
            @endif

            @if ($note->addenda->isNotEmpty())
                <h3>Addenda</h3>
                @foreach ($note->addenda as $addendum)
                    <div class="addendum">
                        <div class="value">{{ $addendum->content }}</div>
                        <div class="meta">Added by {{ $addendum->author?->name ?? 'Deleted user' }} on {{ $addendum->created_at->format('M j, Y g:i A') }}</div>
                    </div>
                @endforeach
            @endif
        </div>
    @endforeach

    <div class="note">
        <h2>Attachments</h2>
        @if ($patient->attachments->isEmpty())
            <p>No attachments on file.</p>
        @else
            <table class="attachments-table">
                <tr>
                    <th>File</th>
                    <th>Category</th>
                    <th>Uploaded by</th>
                    <th>Date</th>
                </tr>
                @foreach ($patient->attachments as $attachment)
                    <tr>
                        <td>{{ $attachment->original_filename }}</td>
                        <td>{{ $attachment->categoryLabel() }}</td>
                        <td>{{ $attachment->uploadedBy?->name ?? '—' }}</td>
                        <td>{{ $attachment->created_at->format('M j, Y') }}</td>
                    </tr>
                @endforeach
            </table>
            <p>Original attachment files are available for download separately from the patient's chart.</p>
        @endif
    </div>
</body>
</html>
