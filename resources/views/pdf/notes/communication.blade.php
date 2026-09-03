@php($data = $note->data)
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Communication Note — {{ $note->patient->name }}</title>
    @include('pdf.notes.partials.styles')
</head>
<body>
    <h1>COMMUNICATION NOTE</h1>

    <table class="meta">
        <tr>
            <td class="label">Date:</td>
            <td>{{ $data['date'] ?? '' }}</td>
            <td class="label">MR #:</td>
            <td>{{ $data['mr_number'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Agency:</td>
            <td>{{ $data['agency_name'] ?? '' }}</td>
            <td class="label">Patient:</td>
            <td>{{ $data['patient_name'] ?? $note->patient->name }}</td>
        </tr>
    </table>

    <div class="value" style="min-height: 260px;">{{ $data['message'] ?? '' }}</div>

    <p>PLEASE CALL ME IF YOU HAVE ANY QUESTIONS. THANK YOU.</p>

    <h2>Verbal orders received, verified and read back by</h2>
    <table class="meta">
        <tr>
            <td class="label">Name:</td>
            <td>{{ $data['verbal_orders_name'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Signature:</td>
            <td>_______________________________ &nbsp; Date: {{ $data['verbal_orders_date'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Physician signature:</td>
            <td>_______________________________ &nbsp; Date: {{ $data['physician_signature_date'] ?? '' }}</td>
        </tr>
    </table>

    @include('pdf.notes.partials.letterhead')
</body>
</html>
