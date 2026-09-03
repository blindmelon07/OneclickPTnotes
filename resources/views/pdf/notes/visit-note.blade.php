@php($data = $note->data)
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>PT / OT Visit Note — {{ $note->patient->name }}</title>
    @include('pdf.notes.partials.styles')
</head>
<body>
    <h1>PT / OT VISIT NOTE</h1>

    <table class="meta">
        <tr>
            <td class="label">Agency:</td>
            <td>{{ $data['agency_name'] ?? '' }}</td>
            <td class="label">Date:</td>
            <td>{{ $data['date'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Patient:</td>
            <td>{{ $data['patient_name'] ?? $note->patient->name }}</td>
            <td class="label">MR #:</td>
            <td>{{ $data['mr_number'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Vitals:</td>
            <td colspan="3">
                BP: {{ $data['bp'] ?? '' }} &nbsp;&nbsp; PR: {{ $data['pr'] ?? '' }} &nbsp;&nbsp; RR: {{ $data['rr'] ?? '' }}
            </td>
        </tr>
        <tr>
            <td class="label">Pain:</td>
            <td colspan="3">{{ $data['pain'] ?? '' }}</td>
        </tr>
    </table>

    <h2>Homebound status</h2>
    <table class="checks">
        @foreach (array_chunk(\App\Models\Note::homeboundStatuses(), 2) as $pair)
            <tr>
                @foreach ($pair as $status)
                    <td>
                        <span class="box {{ in_array($status, $data['homebound_statuses'] ?? [], true) ? 'on' : '' }}"></span>
                        {{ $status }}
                    </td>
                @endforeach
            </tr>
        @endforeach
        @if (! empty($data['homebound_other']))
            <tr><td colspan="2">Others: {{ $data['homebound_other'] }}</td></tr>
        @endif
    </table>

    <h2>Service/s provided</h2>
    <table class="checks">
        @foreach (array_chunk(\App\Models\Note::servicesProvided(), 2) as $pair)
            <tr>
                @foreach ($pair as $service)
                    <td>
                        <span class="box {{ in_array($service, $data['services_provided'] ?? [], true) ? 'on' : '' }}"></span>
                        {{ $service }}
                    </td>
                @endforeach
            </tr>
        @endforeach
        @if (! empty($data['services_other']))
            <tr><td colspan="2">Others: {{ $data['services_other'] }}</td></tr>
        @endif
    </table>

    <h2>Observations &amp; instructions during this visit</h2>
    <div class="value">{{ $data['observations'] ?? '' }}</div>

    <h2>Progress towards goal</h2>
    <div class="value">{{ $data['progress_towards_goal'] ?? '' }}</div>

    @include('pdf.notes.partials.letterhead')
</body>
</html>
