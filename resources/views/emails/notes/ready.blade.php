<x-mail::message>
# {{ $note->label() }} — {{ $note->patient->name }}

A signed clinical note has been submitted and is attached as a PDF.

<x-mail::table>
| Field | Value |
| :- | :- |
| Patient | {{ $note->patient->name }} |
| Note type | {{ $note->label() }} |
| Signed by | {{ $note->author?->name ?? 'Unknown' }} |
| Signed at | {{ optional($note->signed_at)->format('M j, Y g:i A') }} |
</x-mail::table>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
