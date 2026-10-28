@php($letterhead = config('notes.letterhead'))

<table class="sig-block">
    <tr>
        <td>
            <div class="sig-name">{{ $letterhead['supervising_therapist'] }}</div>
            <div class="sig-date">Date: {{ $signatureDate ? \Illuminate\Support\Carbon::parse($signatureDate)->format('M j, Y') : '' }}</div>
        </td>
        <td>
            <div class="sig-name">&nbsp;</div>
            <div class="sig-role">Physician's signature</div>
            <div class="sig-date">Date:</div>
        </td>
    </tr>
</table>

<table class="owned" width="100%">
    <tr>
        <td>This form is OWNED by {{ $letterhead['practice'] }}</td>
        <td style="text-align: center;">LOCATIONS: {{ $letterhead['locations'] }}</td>
        <td style="text-align: right;">{{ $letterhead['website'] }}</td>
    </tr>
</table>
