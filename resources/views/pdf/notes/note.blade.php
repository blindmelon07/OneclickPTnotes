<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $note->label() }} — {{ $note->patient->name }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin-bottom: 0; }
        .subtitle { color: #555; margin-top: 2px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.meta td { padding: 4px 6px; border: 1px solid #ddd; vertical-align: top; }
        table.meta td.label { width: 160px; font-weight: bold; background: #f5f5f5; }
        .field { margin-bottom: 12px; }
        .field .label { font-weight: bold; margin-bottom: 2px; }
        .field .value { white-space: pre-wrap; }
        .signature-block { margin-top: 30px; border-top: 1px solid #999; padding-top: 10px; }
        .signature-block img { height: 60px; }
        .signature-meta { color: #555; margin-top: 4px; }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }}</h1>
    <p class="subtitle">{{ $note->label() }}</p>

    <table class="meta">
        <tr>
            <td class="label">Patient</td>
            <td>{{ $note->patient->name }}</td>
            <td class="label">Diagnosis</td>
            <td>{{ $note->patient->diagnosis }}</td>
        </tr>
        <tr>
            <td class="label">Doctor</td>
            <td>{{ $note->patient->doctor?->name }}</td>
            <td class="label">HHA</td>
            <td>{{ $note->patient->homeHealthAgency?->name }}</td>
        </tr>
        <tr>
            <td class="label">Insurance</td>
            <td>{{ $note->patient->insuranceCompany?->name }}</td>
            <td class="label">Cert period</td>
            <td>{{ $note->patient->cert_period }}</td>
        </tr>
        <tr>
            <td class="label">Visit date</td>
            <td>{{ optional($note->visit?->scheduled_at)->format('M j, Y') ?? $note->created_at->format('M j, Y') }}</td>
            <td class="label">Therapist</td>
            <td>{{ $note->author?->name }}</td>
        </tr>
    </table>

    @foreach (\App\Models\Note::dataFieldLabels() as $key => $label)
        <div class="field">
            <div class="label">{{ $label }}</div>
            <div class="value">{{ $note->data[$key] ?? '' }}</div>
        </div>
    @endforeach

    <div class="signature-block">
        @if ($note->signature_path)
            <img src="{{ $signatureDataUri }}" alt="Staff signature">
        @endif
        <div class="signature-meta">
            Signed by {{ $note->author?->name }} (staff) on {{ optional($note->signed_at)->format('M j, Y g:i A') }}
        </div>
    </div>

    <div class="signature-block">
        @if ($note->patient_signature_path)
            <img src="{{ $patientSignatureDataUri }}" alt="Patient signature">
        @endif
        <div class="signature-meta">
            Signed by {{ $note->patient->name }} (patient) on {{ optional($note->patient_signed_at)->format('M j, Y g:i A') }}
        </div>
    </div>
</body>
</html>
