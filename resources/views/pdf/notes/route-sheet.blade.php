@php($data = $note->data)
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Route Sheet — {{ $note->patient->name }}</title>
    @include('pdf.notes.partials.styles')
</head>
<body>
    <h1>ROUTE SHEET</h1>

    <table class="meta">
        <tr>
            <td class="label">Discipline:</td>
            <td>PHYSICAL THERAPIST ASSISTANT</td>
            <td class="label">MR #:</td>
            <td>{{ $data['mr_number'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Week of:</td>
            <td>{{ $data['week_of'] ?? '' }} thru {{ $data['week_thru'] ?? '' }}</td>
            <td class="label">Agency:</td>
            <td>{{ $data['agency_name'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Patient:</td>
            <td colspan="3">{{ $data['patient_name'] ?? $note->patient->name }}</td>
        </tr>
    </table>

    <table class="grid">
        <thead>
            <tr>
                <th>Visit date</th>
                <th>Time in</th>
                <th>Time out</th>
                <th>Patient signature</th>
                <th>Comments</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $data['visit_date'] ?? '' }}</td>
                <td>{{ $data['time_in'] ?? '' }}</td>
                <td>{{ $data['time_out'] ?? '' }}</td>
                <td>
                    @if ($patientSignatureDataUri)
                        <img src="{{ $patientSignatureDataUri }}" alt="Patient signature" style="height: 46px;">
                    @endif
                </td>
                <td>{{ $data['comments'] ?? '' }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Visit codes</h2>
    <table class="checks">
        @foreach (\App\Models\Note::visitCodes() as $code => $description)
            <tr>
                <td colspan="2">
                    <span class="box {{ ($data['visit_code'] ?? null) === $code ? 'on' : '' }}"></span>
                    <strong>{{ $code }}</strong> = {{ $description }}
                </td>
            </tr>
        @endforeach
    </table>

    @include('pdf.notes.partials.letterhead')
</body>
</html>
