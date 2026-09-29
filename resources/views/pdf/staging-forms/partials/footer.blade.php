@php($letterhead = config('notes.letterhead'))
@php($logo = $letterhead['logo_path'] ? public_path($letterhead['logo_path']) : null)

<table class="sig-block">
    <tr>
        <td>
            <div class="sig-name">{{ $letterhead['supervising_therapist'] }}</div>
            <div class="sig-role">{{ $letterhead['supervising_therapist_title'] }}</div>
            <div class="sig-date">Date: {{ $signatureDate ? \Illuminate\Support\Carbon::parse($signatureDate)->format('M j, Y') : '____________' }}</div>
        </td>
        <td></td>
    </tr>
</table>

<table class="footer">
    <tr>
        <td class="footer-practice">
            <div class="practice">{{ $letterhead['practice'] }}</div>
            <div class="tagline">{{ $letterhead['tagline'] }}</div>
            <div class="contact">{{ strtoupper($letterhead['address']) }}</div>
            <div class="contact">PHONE NO: {{ $letterhead['phone'] }} &nbsp; FAX NO.: {{ $letterhead['fax'] }}</div>
        </td>
        <td class="footer-logo">
            @if ($logo && file_exists($logo))
                <img src="{{ $logo }}" alt="{{ $letterhead['practice'] }}">
            @endif
        </td>
        <td class="footer-contact">
            <div>Email Address: <span class="link">{{ $letterhead['email'] }}</span></div>
            <div>Website: <span class="link">{{ $letterhead['website'] }}</span></div>
        </td>
    </tr>
</table>
