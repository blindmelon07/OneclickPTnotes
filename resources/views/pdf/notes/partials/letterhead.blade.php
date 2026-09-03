@php($letterhead = config('notes.letterhead'))
@php($logo = $letterhead['logo_path'] ? public_path($letterhead['logo_path']) : null)
@php($visitedOn = $note->data['visit_date'] ?? $note->data['date'] ?? null)
@php($assistantDate = $visitedOn ? \Illuminate\Support\Carbon::parse($visitedOn) : ($note->visit?->scheduled_at ?? $note->created_at))

<table class="sig-block">
    <tr>
        @if ($showSupervisingTherapist ?? true)
        <td>
            @if ($note->isCountersigned() && \App\Models\Note::countersignaturePath())
                <img class="sig-image" src="{{ \App\Models\Note::countersignaturePath() }}" alt="Signature">
            @endif
            <div class="sig-name">{{ $letterhead['supervising_therapist'] }}</div>
            <div class="sig-role">{{ $letterhead['supervising_therapist_title'] }}</div>
            <div class="sig-date">
                Date: {{ $note->isCountersigned() ? $assistantDate->format('M j, Y') : '____________' }}
            </div>
        </td>
        @endif
        <td>
            @if ($note->signature_path && ($signatureDataUri ?? null))
                <img class="sig-image" src="{{ $signatureDataUri }}" alt="Signature">
            @endif
            <div class="sig-name">{{ $note->author?->name }}, PTA</div>
            <div class="sig-role">{{ $letterhead['assistant_title'] }}</div>
            <div class="sig-date">Date: {{ $assistantDate->format('M j, Y') }}</div>
        </td>
    </tr>
</table>

<div class="provided-by">THERAPY SERVICES PROVIDED BY:</div>

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
