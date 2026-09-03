@php($data = $note->data)
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Missed Visit Report — {{ $note->patient->name }}</title>
    @include('pdf.notes.partials.styles')
</head>
<body>
    <h1>MISSED VISIT REPORT</h1>

    <table class="meta">
        <tr>
            <td class="label">Date:</td>
            <td>{{ $data['date'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Agency:</td>
            <td>{{ $data['agency_name'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Patient:</td>
            <td>{{ $data['patient_name'] ?? $note->patient->name }}</td>
        </tr>
        <tr>
            <td class="label">MR #:</td>
            <td>{{ $data['mr_number'] ?? '' }}</td>
        </tr>
    </table>

    <h2>Reason for cancellation / missed visit</h2>
    <table class="checks">
        @foreach (array_chunk(\App\Models\Note::missedVisitReasons(), 2) as $pair)
            <tr>
                @foreach ($pair as $reason)
                    <td>
                        <span class="box {{ in_array($reason, $data['reasons'] ?? [], true) ? 'on' : '' }}"></span>
                        {{ $reason }}
                    </td>
                @endforeach
            </tr>
        @endforeach
        @if (! empty($data['reason_other']))
            <tr><td colspan="2">Others: {{ $data['reason_other'] }}</td></tr>
        @endif
    </table>

    <h2>Notification thru</h2>
    <table class="checks">
        <tr>
            <td><span class="box {{ ! empty($data['notified_by_fax']) ? 'on' : '' }}"></span> Fax</td>
            <td>
                <span class="box {{ ! empty($data['notified_by_phone']) ? 'on' : '' }}"></span>
                Phone, spoke with: {{ $data['spoke_with'] ?? '' }}
            </td>
        </tr>
    </table>

    <h2>Was the patient rescheduled?</h2>
    <table class="checks">
        <tr>
            <td>
                <span class="box {{ ($data['rescheduled'] ?? null) === 'yes' ? 'on' : '' }}"></span>
                Yes — approximate next visit date: {{ $data['next_visit_date'] ?? '' }}
            </td>
        </tr>
        <tr>
            <td>
                <span class="box {{ ($data['rescheduled'] ?? null) === 'no' ? 'on' : '' }}"></span>
                No — reason: {{ $data['not_rescheduled_reason'] ?? '' }}
            </td>
        </tr>
    </table>

    <h2>Comments</h2>
    <div class="value">{{ $data['comments'] ?? '' }}</div>

    @include('pdf.notes.partials.letterhead', ['showSupervisingTherapist' => false])

</body>
</html>
