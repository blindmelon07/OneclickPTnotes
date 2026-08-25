---
paths:
  - 'app/Mail/**,resources/views/emails/**'
---

# Emails

## Markdown mail views need Content(markdown:), not Content(view:)
Any Mailable whose view uses `<x-mail::message>`/`<x-mail::table>` etc. (Laravel's built-in markdown mail components) MUST return `new Content(markdown: 'emails.foo.bar')` from `content()` — NOT `Content(view: ...)`. The `mail::` component namespace is only registered by the markdown-mail rendering pipeline; using `view:` throws `No hint path defined for [mail]` at send time. `Mail::fake()` (used in nearly all tests here) never renders the body, so this class of bug is invisible to the usual test — assert it renders for real, e.g. `(new SomeMailable($model))->render()`, see `NoteSigningTest`'s "the office notification email actually renders" test. Found because `NoteReadyForOffice` had this exact bug — see [[patient-note-clinical-documentation-workflow]].
